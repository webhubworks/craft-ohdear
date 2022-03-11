<?php

namespace webhubworks\ohdear\health\checks;

use OhDear\HealthCheckResults\CheckResult;

class FailedJobsCheck extends Check
{
    private int $warningThreshold = 0;
    private int $errorThreshold = 10;

    public function failWhenJobCountIsAbove(int $count): self
    {
        $this->errorThreshold = $count;

        return $this;
    }

    public function warnWhenJobCountIsAbove(int $count): self
    {
        $this->warningThreshold = $count;

        return $this;
    }

    public function run(): CheckResult
    {
        $failedJobCount = \Craft::$app->getQueue()->getTotalFailed();

        $result = (new CheckResult(
            name: 'Queue',
            label: 'Failed jobs',
            shortSummary: $failedJobCount,
            meta: [
                'failedJobCount' => $failedJobCount,
                'warningThreshold' => $this->warningThreshold,
                'errorThreshold' => $this->errorThreshold,
            ],
        ));

        if ($failedJobCount > $this->warningThreshold) {
            return $result->status(CheckResult::STATUS_WARNING)
                ->notificationMessage("Failed jobs in the queue: $failedJobCount");
        }

        if ($failedJobCount > $this->errorThreshold) {
            return $result->status(CheckResult::STATUS_FAILED)
                ->notificationMessage("Failed jobs in the queue: $failedJobCount");
        }

        return $result->status(CheckResult::STATUS_OK)->notificationMessage('All jobs could be completed.');
    }
}
