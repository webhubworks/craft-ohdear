<?php

namespace webhubworks\ohdear\health\checks;

use OhDear\HealthCheckResults\CheckResult;

abstract class Check
{
    use MakesChecks;

    protected ?string $name = null;

    final public function __construct()
    {
    }

    public static function new(): static
    {
        return new static();
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        return (new \ReflectionClass(static::class))->getShortName();
    }

    public function getGenericCrashResult(string $errorMessage = "An unhandled error occurred"): CheckResult
    {
        return new CheckResult(
            name: $this->getName(),
            label: $this->getName(),
            notificationMessage: $errorMessage,
            status: CheckResult::STATUS_CRASHED,
        );
    }

    abstract public function run(): CheckResult;
}
