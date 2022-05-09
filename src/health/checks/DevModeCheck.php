<?php

namespace webhubworks\ohdear\health\checks;

use OhDear\HealthCheckResults\CheckResult;

class DevModeCheck extends Check
{
    protected bool $expected = false;

    public function expectToBeDisabled(bool $bool = true): self
    {
        return $this->expectToBe(!$bool);
    }

    public function expectToBeEnabled(bool $bool = true): self
    {
        return $this->expectToBe($bool);
    }

    public function expectToBe(bool $bool): self
    {
        $this->expected = $bool;

        return $this;
    }

    public function run(): CheckResult
    {
        $actual = \Craft::$app->config->general->devMode;

        $result = (new CheckResult(
            name: 'DevMode',
            label: 'Dev Mode',
            shortSummary: $this->convertToWord($actual),
            meta: [
                'actual' => $actual,
                'expected' => $this->expected,
            ],
        ));

        if ($this->expected !== $actual) {
            return $result->status(CheckResult::STATUS_FAILED)
                ->notificationMessage("The Dev Mode was expected to be `{$this->convertToWord((bool)$this->expected)}`, but actually was `{$this->convertToWord((bool)$actual)}`");
        }

        return $result->status(CheckResult::STATUS_OK);
    }

    protected function convertToWord(bool $boolean): string
    {
        return $boolean ? 'Dev Mode is on' : 'Dev Mode is off';
    }
}
