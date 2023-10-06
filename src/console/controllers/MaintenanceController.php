<?php
/**
 * Oh Dear plugin for Craft CMS 4.x
 *
 * Integrate Oh Dear into Craft CMS.
 *
 * @link      https://webhub.de
 * @copyright Copyright (c) 2019 webhub GmbH
 */

namespace webhubworks\ohdear\console\controllers;

use craft\helpers\Console;
use craft\helpers\DateTimeHelper;
use Exception;
use OhDear\PhpSdk\Resources\MaintenancePeriod;
use webhubworks\ohdear\OhDear;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\console\widgets\Table;

class MaintenanceController extends Controller
{
    use HandlesConsoleErrors;

    /**
     * Retrieve a list of all maintenance periods for the site.
     *
     * @author    webhub GmbH
     * @package   OhDear
     * @since     1.0.0
     */
    public function actionList(): int
    {
        try {
            $periods = OhDear::$plugin->api->maintenancePeriods();

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
        } catch (Exception $e) {
            $this->stdout($this->parseErrorMessage($e) . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Start a maintenance period for the site.
     *
     * @param int $stopAfter Stop the maintenance period after X amount of seconds (defaults to one hour)
     *
     * @throws \Throwable
     * @package   OhDear
     * @since     1.0.0
     * @author    webhub GmbH
     */
    public function actionStart(int $stopAfter = 60 * 60): int
    {
        try {
            $period = OhDear::$plugin->api->startMaintenancePeriod($stopAfter);

            $this->stdout("Maintenance period started." . PHP_EOL, Console::FG_GREEN);

            $this->displayTable([$period]);

            return ExitCode::OK;
        } catch (Exception $e) {
            $this->stdout($this->parseErrorMessage($e) . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Stop the active maintenance period for the site.
     *
     * @author    webhub GmbH
     * @package   OhDear
     * @since     1.0.0
     */
    public function actionStop(): int
    {
        try {
            OhDear::$plugin->api->stopMaintenancePeriod();

            $this->stdout("Maintenance period stopped." . PHP_EOL, Console::FG_GREEN);

            return ExitCode::OK;
        } catch (Exception $e) {
            $this->stdout($this->parseErrorMessage($e) . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Stop the active maintenance period for the site.
     *
     * @author    webhub GmbH
     * @package   OhDear
     * @since     1.0.0
     */
    public function actionDelete(int $id): int
    {
        try {
            OhDear::$plugin->api->deleteMaintenancePeriod($id);

            $this->stdout("Maintenance period deleted." . PHP_EOL, Console::FG_GREEN);

            return ExitCode::OK;
        } catch (Exception $e) {
            $this->stdout($this->parseErrorMessage($e) . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * @throws \Throwable
     */
    private function displayTable(array $periods): void
    {
        $rows = array_map(function ($period) {
            $startTime = strtotime($period->startsAt);
            $startTime = DateTimeHelper::toDateTime($startTime);
            $endTime = strtotime($period->endsAt);
            $endTime = DateTimeHelper::toDateTime($endTime);


            /** @var MaintenancePeriod $period */
            return [
                $period->id,
                $period->siteId,
                $startTime->format('Y-m-d H:i'),
                $endTime ? $endTime->format('Y-m-d H:i') : '∞',
            ];
        }, $periods);

        $this->stdout('Note: Times are displayed in your Oh Dear team timezone.' . PHP_EOL, Console::FG_YELLOW);

        echo Table::widget([
            'headers' => ['ID', 'Site ID', 'Starts at', 'Ends at'],
            'rows' => $rows,
        ]);
    }
}
