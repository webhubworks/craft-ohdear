<?php

namespace webhubworks\ohdear\health\checks;

use Craft;
use DateTimeImmutable;
use DateTimeZone;
use OhDear\HealthCheckResults\CheckResult;
use Throwable;

/**
 * Read-from-cache behavior for checks whose results are refreshed
 * out-of-band via the `ohdear/health-check/refresh` console command.
 *
 * Opt in per check via ->cachedViaCron($staleAfterSeconds). Until enabled,
 * compute() runs inline on every endpoint hit (legacy behavior).
 */
trait CachesResult
{
    private ?int $staleAfterSeconds = null;

    /**
     * Enable cron-refreshed caching for this check. The cached result is
     * considered fresh for $staleAfterSeconds; older entries are still
     * returned but the status is downgraded to STATUS_WARNING with a
     * staleSince entry in meta. The console refresh command should run
     * more frequently than $staleAfterSeconds to keep entries fresh.
     */
    public function cachedViaCron(int $staleAfterSeconds): static
    {
        if ($staleAfterSeconds < 1) {
            throw new \InvalidArgumentException('staleAfterSeconds must be greater than 0.');
        }

        $this->staleAfterSeconds = $staleAfterSeconds;

        return $this;
    }

    final public function run(): CheckResult
    {
        if ($this->staleAfterSeconds === null) {
            return $this->compute();
        }

        $cached = Craft::$app->getCache()->get($this->getCacheKey());

        if (!is_array($cached) || !($cached['result'] ?? null) instanceof CheckResult) {
            return $this->notYetComputedResult();
        }

        $computedAt = (int) ($cached['computedAt'] ?? 0);
        $ageSeconds = time() - $computedAt;
        $result = $cached['result'];

        if ($ageSeconds > $this->staleAfterSeconds) {
            return $this->markStale($result, $computedAt, $ageSeconds);
        }

        return $this->annotateWithComputedAt($result, $computedAt);
    }

    public function refresh(): CheckResult
    {
        $result = $this->compute();

        Craft::$app->getCache()->set(
            $this->getCacheKey(),
            ['result' => $result, 'computedAt' => time()],
            $this->staleAfterSeconds !== null ? $this->staleAfterSeconds * 2 : 86400,
        );

        return $result;
    }

    abstract protected function compute(): CheckResult;

    private function getCacheKey(): string
    {
        return 'ohdear-check-result:' . $this->getName();
    }

    private function notYetComputedResult(): CheckResult
    {
        return new CheckResult(
            name: $this->getName(),
            label: $this->getName(),
            notificationMessage: sprintf(
                'No cached result yet. Schedule `craft ohdear/health-check/refresh` on cron to populate this check.',
            ),
            shortSummary: 'Not yet computed',
            status: CheckResult::STATUS_WARNING,
        );
    }

    private function markStale(CheckResult $cached, int $computedAt, int $ageSeconds): CheckResult
    {
        $meta = $this->mergeMeta($cached, [
            'staleSince' => $this->formatTimestamp($computedAt),
            'ageSeconds' => $ageSeconds,
        ]);

        return new CheckResult(
            name: $cached->name,
            label: $cached->label,
            notificationMessage: sprintf(
                '%s (stale: last refreshed %s)',
                $cached->notificationMessage,
                $this->formatTimestamp($computedAt),
            ),
            shortSummary: $cached->shortSummary,
            status: CheckResult::STATUS_WARNING,
            meta: $meta,
        );
    }

    private function annotateWithComputedAt(CheckResult $cached, int $computedAt): CheckResult
    {
        return new CheckResult(
            name: $cached->name,
            label: $cached->label,
            notificationMessage: $cached->notificationMessage,
            shortSummary: $cached->shortSummary,
            status: $cached->status,
            meta: $this->mergeMeta($cached, [
                'lastRefreshedAt' => $this->formatTimestamp($computedAt),
            ]),
        );
    }

    private function mergeMeta(CheckResult $cached, array $extra): array
    {
        try {
            $meta = (array) ($cached->meta ?? []);
        } catch (Throwable) {
            $meta = [];
        }

        return array_merge($meta, $extra);
    }

    private function formatTimestamp(int $timestamp): string
    {
        return (new DateTimeImmutable('@' . $timestamp))
            ->setTimezone(new DateTimeZone(date_default_timezone_get()))
            ->format('Y-m-d H:i:s');
    }
}
