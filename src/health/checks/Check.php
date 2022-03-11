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

        return basename(static::class);
    }

    public function getGenericCrashResult(string $errorMessage = "An unhandled error occured") {
        return new CheckResult(
            name: $this->getName(),
            label: $this->getName(),
            status: CheckResult::STATUS_CRASHED,
            notificationMessage: $errorMessage,
        );
    }

    abstract public function run(): CheckResult;
}
