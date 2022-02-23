<?php

namespace webhubworks\ohdear\health\checks;

use OhDear\HealthCheckResults\CheckResult;

class EnvironmentCheck extends Check
{
    protected string $expectedEnvironment = 'production';

    public function expectEnvironment(string $expectedEnvironment): self
    {
        $this->expectedEnvironment = $expectedEnvironment;

        return $this;
    }

    public function run(): CheckResult
    {
        $actualEnvironment = \Craft::$app->config->env;

        $result = (new CheckResult(
            name: 'Environment',
            label: 'App environment',
            shortSummary: $actualEnvironment,
        ))
            ->meta([
                'actual' => $actualEnvironment,
                'expected' => $this->expectedEnvironment,
            ]);

        if ($this->expectedEnvironment !== $actualEnvironment) {
            return $result->status(CheckResult::STATUS_FAILED)
                ->notificationMessage("The environment was expected to be `{$this->expectedEnvironment}`, but actually was `{$actualEnvironment}`");
        }

        return $result->status(CheckResult::STATUS_OK)
            ->notificationMessage('The environment is as expected.');
    }
}
