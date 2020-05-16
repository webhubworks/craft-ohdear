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
use OhDear\PhpSdk\Resources\Check;
use webhub\ohdear\OhDear;
use yii\console\Controller;
use yii\console\ExitCode;

class CheckController extends Controller
{

    /**
     * @var string Specify the check type. Can be one of 'uptime', 'broken-links', 'mixed-content', 'certificate-health' or 'certificate-transparency' or a comma separated list of them.
     */
    public $type;

    /**
     * @var string[]
     */
    private $types;

    /**
     * @var string[]
     */
    private $availableTypes = ['uptime', 'broken-links', 'mixed-content', 'certificate-health', 'certificate-transparency'];

    /**
     * @var Check[]
     */
    private $checks;

    /**
     * @param string $actionID
     * @return array|string[]
     */
    public function options($actionID)
    {
        return [
            'type'
        ];
    }

    public function optionAliases()
    {
        return ['t' => 'type'];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (!$this->parseTypes()) {
            return false;
        }

        $this->checks = array_filter(OhDear::$plugin->ohDearService->getSite()->checks, function ($check) {
            /** @var Check $check */
            return in_array($check->type, $this->types);
        });

        return true; // or false to not run the action
    }

    /**
     * Requests a new run for one or multiple checks specified by their check type.
     *
     * @return int
     *
     * @author    webhub GmbH
     * @package   OhDear
     * @since     1.0.0
     */
    public function actionRequestRun()
    {
        foreach ($this->checks as $check) {
            OhDear::$plugin->ohDearService->requestRun($check->id);
            $this->stdout("✓ ", Console::FG_GREEN);
            $this->stdout("New {$check->label} check run requested" . PHP_EOL);
        }

        return ExitCode::OK;
    }

    /**
     * Enable one or multiple checks specified by their check type.
     *
     * @return int
     *
     * @author    webhub GmbH
     * @package   OhDear
     * @since     1.0.0
     */
    public function actionEnable()
    {
        foreach ($this->checks as $check) {
            OhDear::$plugin->ohDearService->enableCheck($check->id);
            $this->stdout("Enabled ", Console::FG_GREEN);
            $this->stdout("{$check->label} check" . PHP_EOL);
        }

        return ExitCode::OK;
    }

    /**
     * Disable one or multiple checks specified by their check type.
     *
     * @return int
     *
     * @author    webhub GmbH
     * @package   OhDear
     * @since     1.0.0
     */
    public function actionDisable()
    {
        foreach ($this->checks as $check) {
            OhDear::$plugin->ohDearService->disableCheck($check->id);
            $this->stdout("Disabled ", Console::FG_YELLOW);
            $this->stdout("{$check->label} check" . PHP_EOL);
        }

        return ExitCode::OK;
    }

    /**
     * Whitelists the type option with the available types and
     * transforms them to lower snake case.
     *
     * Prints a warning if the type option is empty.
     *
     * @return bool true if successful
     */
    private function parseTypes()
    {
        if (!$this->type) {
            $this->stdout("Invalid type option, must be on of 'uptime', 'broken-links', 'mixed-content', 'certificate-health' or 'certificate-transparency' or a comma separated list of them." . PHP_EOL, Console::FG_YELLOW);
            return false;
        }

        $this->types = $this->whitelistTypes(explode(',', $this->type));

        $this->types = array_map(function ($type) {
            return str_replace('-', '_', mb_strtolower($type));
        }, $this->types);

        if (empty($this->types)) {
            $this->stdout("Invalid type option, must be on of 'uptime', 'broken-links', 'mixed-content', 'certificate-health' or 'certificate-transparency' or a comma separated list of them." . PHP_EOL, Console::FG_YELLOW);
            return false;
        }

        return true;
    }

    /**
     * Returns only array entries listed in a whitelist
     * @see https://andy-carter.com/blog/simple-php-function-to-whitelist-array-keys
     *
     * @param array $types
     * @return array
     */
    private function whitelistTypes($types)
    {
        return array_intersect(
            $types,
            $this->availableTypes
        );
    }
}
