<?php

namespace webhubworks\ohdear\health\checks;

use OhDear\HealthCheckResults\CheckResult;

abstract class Check
{
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

    abstract public function run(): CheckResult;
}
