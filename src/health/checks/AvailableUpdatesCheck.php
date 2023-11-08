<?php

namespace webhubworks\ohdear\health\checks;

use OhDear\HealthCheckResults\CheckResult;

class AvailableUpdatesCheck extends Check
{
    protected int $warningThreshold = 3;

    public function warnWhenTotalAvailableUpdatesIsAtLeast(int $threshold): self
    {
        $this->warningThreshold = $threshold;

        return $this;
    }

    public function run(): CheckResult
    {
        $updates = \Craft::$app->updates->getUpdates(true);
        $totalUpdates = $updates->getTotal();
        $hasCritical = $updates->getHasCritical();

        $result = (new CheckResult(
            name: 'AvailableUpdates',
            label: 'Available Updates',
            shortSummary: $totalUpdates,
            meta: [
                'threshold' => $this->warningThreshold,
                'totalUpdates' => $totalUpdates,
                'hasCriticalUpdate' => $hasCritical,
            ],
        ));

        if ($hasCritical) {
            return $result->status(CheckResult::STATUS_FAILED)
                ->notificationMessage('There are one or more critical Craft or plugin updates available!');
        }
        
        if ($totalUpdates >= $this->warningThreshold) {
            return $result->status(CheckResult::STATUS_WARNING)
                ->notificationMessage($totalUpdates === 1 ? "There is a Craft or plugin update available!" : "There are {$totalUpdates} Craft or plugin updates available!");
        }

        return $result->status(CheckResult::STATUS_OK)
            ->notificationMessage($totalUpdates === 0 ? 'Everything is up-to-date.' : 'Available updates: ' . $totalUpdates);
    }
}
