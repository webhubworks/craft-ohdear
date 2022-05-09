<?php

namespace webhubworks\ohdear\services;

use OhDear\HealthCheckResults\CheckResults;
use webhubworks\ohdear\health\checks\Check;
use webhubworks\ohdear\health\exceptions\DuplicateCheckNamesFound;
use webhubworks\ohdear\health\exceptions\InvalidCheck;
use webhubworks\ohdear\OhDear;
use yii\base\Component;

class HealthCheckService extends Component
{
    /** @var array<int, Check> */
    protected array $checks = [];

    /** @var array<int, string> */
    public array $inlineStylesheets = [];

    public function __construct($config = [])
    {
        parent::__construct($config);

        $checks = OhDear::$plugin->settings->healthChecks;

        if (is_array($checks)) {
            $this->addChecks($checks);
        }
    }

    public function getCheckResults(): CheckResults
    {
        $checkResults = array_map(function(Check $check) {
            try {
                return $check->run();
            } catch (\Throwable $e) {
                return $check->getGenericCrashResult($e->getMessage());
            }
        }, $this->registeredChecks());

        return (new CheckResults(null, $checkResults));
    }

    /** @param array<int, Check> $checks */
    protected function addChecks(array $checks): self
    {
        $this->ensureCheckInstances($checks);

        $this->checks = array_merge($this->checks, $checks);

        $this->guardAgainstDuplicateCheckNames();

        return $this;
    }

    /** @return array<int, Check> */
    public function registeredChecks(): array
    {
        return $this->checks;
    }

    /** @param array<int,mixed> $checks */
    protected function ensureCheckInstances(array $checks): void
    {
        foreach ($checks as $check) {
            if (!$check instanceof Check) {
                throw InvalidCheck::doesNotExtendCheck($check);
            }
        }
    }

    protected function guardAgainstDuplicateCheckNames(): void
    {
        $checkNames = array_map(fn(Check $check) => $check->getName(), $this->checks);

        $uniqueCheckNames = array_unique($checkNames);

        $duplicateCheckNames = array_diff_assoc($checkNames, $uniqueCheckNames);

        if (count($duplicateCheckNames)) {
            throw DuplicateCheckNamesFound::make($duplicateCheckNames);
        }
    }
}
