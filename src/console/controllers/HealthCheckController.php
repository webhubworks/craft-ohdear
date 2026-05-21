<?php

namespace webhubworks\ohdear\console\controllers;

use craft\helpers\Console;
use Throwable;
use webhubworks\ohdear\health\checks\Cacheable;
use webhubworks\ohdear\OhDear;
use yii\console\Controller;
use yii\console\ExitCode;

class HealthCheckController extends Controller
{
    /**
     * Refresh cached results for every registered health check that opted
     * in via `->cachedViaCron($staleAfterSeconds)`. Intended to be run on
     * cron, more frequently than the configured staleness threshold.
     *
     *     * /30 * * * * cd /path/to/site && php craft ohdear/health-check/refresh
     */
    public function actionRefresh(): int
    {
        $cacheable = array_filter(
            OhDear::$plugin->health->registeredChecks(),
            fn($check) => $check instanceof Cacheable,
        );

        if (empty($cacheable)) {
            $this->stdout('No cacheable health checks are registered.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $hadFailure = false;

        foreach ($cacheable as $check) {
            $name = $check->getName();
            $start = microtime(true);

            try {
                $check->refresh();
                $elapsed = microtime(true) - $start;
                $this->stdout('✓ ', Console::FG_GREEN);
                $this->stdout(sprintf("Refreshed %s in %.2fs" . PHP_EOL, $name, $elapsed));
            } catch (Throwable $e) {
                $hadFailure = true;
                $this->stdout('✗ ', Console::FG_RED);
                $this->stdout(sprintf("Failed to refresh %s: %s" . PHP_EOL, $name, $e->getMessage()));
            }
        }

        return $hadFailure ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }
}
