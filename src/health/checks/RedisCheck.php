<?php

namespace webhubworks\ohdear\health\checks;

use OhDear\HealthCheckResults\CheckResult;
use yii\base\InvalidConfigException;
use yii\redis\Connection;

class RedisCheck extends Check
{
    public function run(): CheckResult
    {
        $result = (new CheckResult(
            name: 'Redis',
            label: 'Redis Connection',
        ));

        try {
            /** @var Connection $redis */
            $redis = \Yii::$app->get('redis');
            $connected = $redis->ping();

            if ($connected) {
                return $result->status(CheckResult::STATUS_OK)
                    ->notificationMessage('Connection to Redis is healthy.')
                    ->shortSummary('Redis connected.');
            }

            return $result->status(CheckResult::STATUS_FAILED)
                ->notificationMessage('Connection to Redis is unhealthy.')
                ->shortSummary('Redis not connected.');

        } catch (InvalidConfigException $e) {
            return $result->status(CheckResult::STATUS_CRASHED)
                ->notificationMessage('Redis connection could not be established due to config error.')
                ->shortSummary('Redis not connected.');
        }
    }
}
