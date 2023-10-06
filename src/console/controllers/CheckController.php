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
use OhDear\PhpSdk\Resources\Check;
use webhubworks\ohdear\OhDear;
use yii\console\Controller;
use yii\console\ExitCode;

class CheckController extends Controller
{
    use HandlesConsoleErrors;

    /**
     * Specify the check type. Can be one of 'uptime', 'broken-links', 'mixed-content', 'certificate-health' or 'certificate-transparency' or a comma separated list of them.
     */
    public $type;

    /** @var string[] */
    private array $types;

    /** @var string[] */
    private array $availableTypes = ['uptime', 'broken-links', 'mixed-content', 'certificate-health', 'certificate-transparency'];

    /** @var Check[] */
    private array $checks;

    private string $invalidTypeOptionMessage = "Invalid type option, must be on of 'uptime', 'broken-links', 'mixed-content', 'certificate-health' or 'certificate-transparency' or a comma separated list of them." . PHP_EOL;

    /**
     * @param string $actionID
     * @return array|string[]
     */
    public function options($actionID)
    {
        return [
            'type',
        ];
    }

    public function optionAliases(): array
    {
        return ['t' => 'type'];
    }

    public function beforeAction($action): bool
    {
        if (! parent::beforeAction($action)) {
            return false;
        }

        $this->types = $this->parseTypes();

        try {
            $this->checks = $this->getChecksByType($this->types);
        } catch (\Exception $e) {
            $this->stdout("✗ ", Console::FG_RED);
            $this->stdout("Oh Dear: " . $this->parseErrorMessage($e) . PHP_EOL);
            return false;
        }

        return true; // or false to not run the action
    }


    /**
     * Requests a new run for one or multiple checks specified by their check type.
     *
     * @author    webhub GmbH
     * @package   OhDear
     * @since     1.0.0
     */
    public function actionRequestRun(): int
    {
        foreach ($this->checks as $check) {
            try {
                OhDear::$plugin->api->requestRun($check->id);
                $this->stdout("✓ ", Console::FG_GREEN);
                $this->stdout("New {$check->label} check run requested" . PHP_EOL);
            } catch (\Exception $e) {
                $this->stdout("✗ ", Console::FG_RED);
                $this->stdout("{$check->label} request failed: " . $this->parseErrorMessage($e) . PHP_EOL);
            }
        }

        return ExitCode::OK;
    }

    /**
     * Enable one or multiple checks specified by their check type.
     *
     * @author    webhub GmbH
     * @package   OhDear
     * @since     1.0.0
     */
    public function actionEnable(): int
    {
        foreach ($this->checks as $check) {
            try {
                OhDear::$plugin->api->enableCheck($check->id);
                $this->stdout("Enabled ", Console::FG_GREEN);
                $this->stdout("{$check->label} check" . PHP_EOL);
            } catch (\Exception $e) {
                $this->stdout("✗ ", Console::FG_RED);
                $this->stdout("Enabling {$check->label} check failed: " . $this->parseErrorMessage($e) . PHP_EOL);
            }
        }

        return ExitCode::OK;
    }

    /**
     * Disable one or multiple checks specified by their check type.
     *
     * @author    webhub GmbH
     * @package   OhDear
     * @since     1.0.0
     */
    public function actionDisable(): int
    {
        foreach ($this->checks as $check) {
            try {
                OhDear::$plugin->api->disableCheck($check->id);
                $this->stdout("Disabled ", Console::FG_YELLOW);
                $this->stdout("{$check->label} check" . PHP_EOL);
            } catch (\Exception $e) {
                $this->stdout("✗ ", Console::FG_RED);
                $this->stdout("Disabling {$check->label} check failed: " . $this->parseErrorMessage($e) . PHP_EOL);
            }

        }

        return ExitCode::OK;
    }

    /**
     * Returns all available types if the type option is not set.
     *
     * Whitelists the type option with the available types.
     *
     * Prints a warning if the type option is empty.
     */
    private function parseTypes(): array
    {
        if ($this->type === null) {
            return $this->transformCase($this->availableTypes);
        }

        if (! $this->type) {
            $this->stdout($this->invalidTypeOptionMessage, Console::FG_YELLOW);
            return [];
        }

        $types = $this->whitelistTypes(explode(',', $this->type));

        $types = $this->transformCase($types);

        if (empty($types)) {
            $this->stdout($this->invalidTypeOptionMessage, Console::FG_YELLOW);
            return [];
        }

        return $types;
    }

    /**
     * Transforms all items in an array to lower snake case.
     */
    private function transformCase(array $array): array
    {
        return array_map(function ($item) {
            return str_replace('-', '_', mb_strtolower($item));
        }, $array);
    }

    /**
     * Returns only array entries listed in a whitelist
     * @see https://andy-carter.com/blog/simple-php-function-to-whitelist-array-keys
     */
    private function whitelistTypes(array $types): array
    {
        return array_intersect(
            $types,
            $this->availableTypes
        );
    }

    /**
     * Returns all site checks whose type names are in the types
     * array.
     *
     * @return Check[]
     */
    private function getChecksByType(array $types): array
    {
        if (empty($types)) {
            return [];
        }

        return array_filter(OhDear::$plugin->api->getSite()->checks, function ($check) use ($types) {
            return in_array($check->type, $types);
        });
    }
}
