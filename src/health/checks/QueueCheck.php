<?php

namespace webhubworks\ohdear\health\checks;

use craft\helpers\DateTimeHelper;
use DateTime;
use OhDear\HealthCheckResults\CheckResult;

class QueueCheck extends Check
{
    const CACHE_KEY = 'ohdear-queue-check-heartbeat';

    protected int $failWhenTestJobTakesLongerThanMinutes = 5;

    public function failWhenHealthJobTakesLongerThanMinutes(int $minutes): self
    {
        if ($minutes < 1) {
            throw new \InvalidArgumentException('The minutes parameter must be greater than 0.');
        }

        $this->failWhenTestJobTakesLongerThanMinutes = $minutes;

        return $this;
    }

    public function run(): CheckResult
    {
        $result = (new CheckResult(
            name: 'Queue',
            label: 'Queue Health',
        ));

        $latestHeartbeat = \Craft::$app->getCache()->get(self::CACHE_KEY);

        if ($latestHeartbeat === false) {
            return $result->shortSummary('Not run yet.')
                ->meta([
                    'latestHeartbeat' => null,
                    'failWhenTestJobTakesLongerThanMinutes' => $this->failWhenTestJobTakesLongerThanMinutes,
                ])
                ->notificationMessage('There is no queue heartbeat yet. Either the queue did not run yet or the cache has recently been cleared.')
                ->status(CheckResult::STATUS_WARNING);
        }

        $failWhenTestJobTakesLongerThanSeconds = $this->failWhenTestJobTakesLongerThanMinutes * 60;

        $secondsSinceLastHeartbeat = (int) DateTimeHelper::currentUTCDateTime()->format('U') - $latestHeartbeat;
        $minutesSinceLastHeartbeat = (float) number_format( $secondsSinceLastHeartbeat / 60, 2);

        if ($secondsSinceLastHeartbeat > $failWhenTestJobTakesLongerThanSeconds) {
            return $result->shortSummary('Not running.')
                ->meta([
                    'latestHeartbeat' => (new DateTime("@$latestHeartbeat"))->format('Y-m-d H:i:se'),
                    'minutesSinceLastHeartbeat' => $minutesSinceLastHeartbeat,
                    'failWhenTestJobTakesLongerThanMinutes' => $this->failWhenTestJobTakesLongerThanMinutes,
                ])
                ->notificationMessage("The last run of the queue was more than {$this->failWhenTestJobTakesLongerThanMinutes} minutes ago.")
                ->status(CheckResult::STATUS_FAILED);
        }

        return $result->shortSummary('Running.')
            ->meta([
                'latestHeartbeat' => (new DateTime("@$latestHeartbeat"))->format('Y-m-d H:i:se'),
                'minutesSinceLastHeartbeat' => $minutesSinceLastHeartbeat,
                'failWhenTestJobTakesLongerThanMinutes' => $this->failWhenTestJobTakesLongerThanMinutes,
            ])
            ->notificationMessage("The last run of the queue was less than {$this->failWhenTestJobTakesLongerThanMinutes} minutes ago.")
            ->status(CheckResult::STATUS_OK);
    }
}
