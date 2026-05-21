<?php

namespace webhubworks\ohdear\health\checks;

use OhDear\HealthCheckResults\CheckResult;

/**
 * Marker for checks whose results are populated out-of-band — typically by the
 * `ohdear/health-check/refresh` console command running on cron — and only
 * read from cache when the health-check endpoint is hit.
 */
interface Cacheable
{
    /**
     * Compute the check result and store it in the cache.
     */
    public function refresh(): CheckResult;
}
