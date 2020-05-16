<?php
/**
 * Oh Dear plugin for Craft CMS 3.x
 *
 * Integrate Oh Dear into Craft CMS.
 *
 * @link      https://webhub.de
 * @copyright Copyright (c) 2019 webhub GmbH
 */

namespace webhub\ohdear\console\controllers;

use craft\helpers\Console;
use Exception;
use OhDear\PhpSdk\Resources\MaintenancePeriod;
use webhub\ohdear\OhDear;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\console\widgets\Table;

class MaintenanceController extends Controller
{
    /**
     * Retrieve a list of all maintenance periods for the site.
     *
     * @return int
     *
     * @author    webhub GmbH
     * @package   OhDear
     * @since     1.0.0
     */
    public function actionList()
    {
        $periods = OhDear::$plugin->ohDearService->maintenancePeriods();

        if (empty($periods)) {
            $this->stdout("No maintenance periods for this site." . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        usort($periods, function ($a, $b) {
            return strtotime($a->startsAt) > strtotime($b->startsAt);
        });

        try {
            $this->displayTable($periods);
        } catch (Exception $e) {
            $this->stdout($e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Start a maintenance period for the site.
     *
     * @param int $stopAfter Stop the maintenance period after X amount of seconds (defaults to one hour)
     * @return int
     *
     * @author    webhub GmbH
     * @package   OhDear
     * @since     1.0.0
     */
    public function actionStart(int $stopAfter = 60 * 60)
    {
        $period = OhDear::$plugin->ohDearService->startMaintenancePeriod($stopAfter);

        $this->stdout("Maintenance period started." . PHP_EOL, Console::FG_GREEN);

        try {
            $this->displayTable([$period]);
        } catch (Exception $e) {
            $this->stdout($e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Stop the active maintenance period for the site.
     *
     * @return int
     *
     * @author    webhub GmbH
     * @package   OhDear
     * @since     1.0.0
     */
    public function actionStop()
    {
        OhDear::$plugin->ohDearService->stopMaintenancePeriod();

        $this->stdout("Maintenance period stopped." . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * @param array $periods
     * @throws Exception
     */
    private function displayTable($periods)
    {
        $rows = array_map(function ($period) {
            $startTime = strtotime($period->startsAt);
            $endTime = strtotime($period->endsAt);

            if (time() > $endTime) {
                $status = 'past';
            } elseif (time() < $startTime) {
                $status = 'scheduled';
            } else {
                $status = 'active';
            }

            /** @var MaintenancePeriod $period */
            return [
                $period->id,
                $period->siteId,
                $period->startsAt,
                $period->endsAt,
                $status
            ];
        }, $periods);

        echo Table::widget([
            'headers' => $headers = ['ID', 'Site ID', 'Starts at', 'Ends at', 'Status'],
            'rows' => $rows
        ]);
    }

}
