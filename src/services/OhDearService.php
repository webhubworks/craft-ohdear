<?php

namespace webhubworks\ohdear\services;

use Carbon\Carbon;
use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\MatrixBlock;
use craft\errors\SiteNotFoundException;
use craft\helpers\Search;
use Exception;
use OhDear\PhpSdk\Dto\ApplicationHealthCheck;
use OhDear\PhpSdk\Dto\ApplicationHealthCheckHistoryItem;
use OhDear\PhpSdk\Dto\BrokenLink;
use OhDear\PhpSdk\Dto\CertificateHealth;
use OhDear\PhpSdk\Dto\Check;
use OhDear\PhpSdk\Dto\MaintenancePeriod;
use OhDear\PhpSdk\Dto\MixedContent;
use OhDear\PhpSdk\Dto\Monitor;
use OhDear\PhpSdk\Dto\Uptime;
use OhDear\PhpSdk\Dto\UptimeMetric\HttpUptimeMetric;
use OhDear\PhpSdk\Enums\UptimeMetricsSplit;
use OhDear\PhpSdk\Enums\UptimeSplit;
use OhDear\PhpSdk\OhDear as OhDearSdk;
use Spatie\Url\Url;
use webhubworks\ohdear\OhDear;

/**
 * @author    webhub GmbH
 * @package   OhDear
 * @since     1.0.0
 *
 * @property-read array $brokenLinks
 * @property-read CertificateHealth $certificateHealth
 * @property-read array $mixedContent
 */
class OhDearService extends Component
{
    private OhDearSdk $ohDearClient;
    private int $monitorId;
    private string $apiToken;

    public function __construct($config = [])
    {
        parent::__construct($config);
        
        if (! OhDear::$plugin->getSettings()->hasApiCredentials()) {
            throw new Exception('Oh Dear credentials are not set. Please check your settings.');
        }

        $this->monitorId = intval(OhDear::$plugin->getSettings()->getSelectedSiteId());
        $this->apiToken = OhDear::$plugin->getSettings()->getApiToken();

        $this->ohDearClient = new OhDearSdk($this->apiToken);
    }

    public function createMaintenancePeriod(string $startsAt, string $endsAt): MaintenancePeriod
    {
        return $this->ohDearClient->createMaintenancePeriod([
            $this->monitorId,
            $startsAt,
            $endsAt
        ]);
    }

    public function deleteMaintenancePeriod(int $maintenancePeriodId): void
    {
        $this->ohDearClient->deleteMaintenancePeriod($maintenancePeriodId);
    }

    public function startMaintenancePeriod(int $stopMaintenanceAfterSeconds = 60 * 60): MaintenancePeriod
    {
        return $this->ohDearClient->startMaintenancePeriod($this->monitorId, $stopMaintenanceAfterSeconds);
    }

    public function stopMaintenancePeriod(): void
    {
        $this->ohDearClient->stopMaintenancePeriod($this->monitorId);
    }
    
    /**
     * @return MaintenancePeriod[]
     */
    public function maintenancePeriods(): array
    {
        return (array)$this->ohDearClient->maintenancePeriods($this->monitorId);
    }
    
    /**
     * @return Monitor[]
     */
    public function getMonitors(?string $apiToken = null): array
    {
        return $this->ohDearClient->monitors();
    }

    public function getMonitor(): Monitor
    {
        return $this->ohDearClient->monitor($this->monitorId);
    }
    
    /**
     * @param Carbon $startedAt
     * @param Carbon $endedAt
     * @param UptimeSplit|null $splitBy
     * @return Uptime[]
     */
    public function getUptime(Carbon $startedAt, Carbon $endedAt, ?UptimeSplit $splitBy = null): array
    {
        return $splitBy
            ? $this->ohDearClient->uptime($this->monitorId, $startedAt->toDateTimeString(), $endedAt->toDateTimeString(), $splitBy)
            : $this->ohDearClient->uptime($this->monitorId, $startedAt->toDateTimeString(), $endedAt->toDateTimeString());
    }
    
    /**
     * @param Uptime[] $uptimes
     * @return Uptime[]
     */
    public function leftPadUptimeToMonday(array $uptimes): array
    {
        if (!count($uptimes)) {
            return $uptimes;
        }

        $firstUptimeDate = Carbon::parse($uptimes[0]->datetime);

        $daysToPad = $firstUptimeDate->isoWeekday() - 1;

        $pad = [];

        for ($i = $daysToPad; $i > 0; $i--) {
            $pad[] = new Uptime(
                $firstUptimeDate->copy()->subDays($i)->toDateTimeString(),
                0
            );
        }

        return [
            ...$pad,
            ...$uptimes,
        ];
    }
    
    public function getDowntime(Carbon $startedAt, Carbon $endedAt): array
    {
        return $this->ohDearClient->downtime($this->monitorId, $startedAt->toDateTimeString(), $endedAt->toDateTimeString());
    }
    
    /**
     * @throws SiteNotFoundException
     */
    public function getBrokenLinks(): array
    {
        $brokenLinks = [];
        
        /** @var BrokenLink $brokenLink */
        foreach ($this->ohDearClient->brokenLinks($this->monitorId) as $brokenLink) {
            $brokenLinks[] = [
                'crawledUrl' => $brokenLink->crawledUrl,
                'foundOnUrl' => $brokenLink->foundOnUrl,
                'statusCode' => $brokenLink->statusCode,
                'element' => $this->findElementByBrokenLink($brokenLink),
            ];
        }
        
        return $brokenLinks;
    }
    
    /**
     * @throws SiteNotFoundException
     */
    public function getMixedContent(): array
    {
        $mixedContent = [];
        
        /** @var MixedContent $mixedContentItem */
        foreach ($this->ohDearClient->mixedContent($this->monitorId) as $mixedContentItem) {
            $mixedContent[] = [
                'mixedContentUrl' => $mixedContentItem->mixedContentUrl,
                'foundOnUrl' => $mixedContentItem->foundOnUrl,
                'elementName' => $mixedContentItem->elementName,
                'element' => $this->findElementByMixedContentItem($mixedContentItem),
            ];
        }
        
        return $mixedContent;
    }

    public function getCertificateHealth(): CertificateHealth
    {
        return $this->ohDearClient->certificateHealth($this->monitorId);
    }
    
    /**
     * @return ApplicationHealthCheck[]
     */
    public function getApplicationHealthChecks(): array
    {
        return $this->ohDearClient->applicationHealthChecks($this->monitorId);
    }
    
    /**
     * @return ApplicationHealthCheckHistoryItem[]
     */
    public function getApplicationHealthCheckResults(int $applicationHealthCheckId): array
    {
        return $this->ohDearClient->applicationHealthCheckHistory($this->monitorId, $applicationHealthCheckId);
    }

    public function getCronChecks(): array
    {
        return (array)$this->ohDearClient->cronCheckDefinitions($this->monitorId);
    }

    /**
     * Returns the average total time of the last 10 minutes in ms.
     * Returns null if there are no records.
     */
    public function getCurrentPerformance(): ?int
    {
        $lastTenMinutes = $this->getPerformance(Carbon::now()->subMinutes(10), Carbon::now());

        $totalTimes_s = array_filter(array_column($lastTenMinutes, 'totalTimeInSeconds'));
        if (count($totalTimes_s) === 0) {
            return null;
        }
        $avgTotalTime_s = array_sum($totalTimes_s) / count($totalTimes_s);
        $avgTotalTime_ms = $avgTotalTime_s * 1000;

        return (int)$avgTotalTime_ms;
    }
    
    /**
     * @param string $start
     * @param string $end
     * @param UptimeMetricsSplit|null $splitBy
     * @return HttpUptimeMetric[]
     */
    public function getPerformance(string $start, string $end, ?UptimeMetricsSplit $splitBy = null): array
    {
        return $splitBy ?
            $this->ohDearClient->httpUptimeMetrics($this->monitorId, $start, $end, $splitBy) :
            $this->ohDearClient->httpUptimeMetrics($this->monitorId, $start, $end);
    }

    public function disableCheck(int $checkId): Check
    {
        return $this->ohDearClient->disableCheck($checkId);
    }

    public function enableCheck(int $checkId): Check
    {
        return $this->ohDearClient->enableCheck($checkId);
    }

    public function requestRun(int $checkId): Check
    {
        return $this->ohDearClient->requestCheckRun($checkId);
    }

    private function transformElement(?ElementInterface $element): ?array
    {
        try {
            if ($element instanceof Entry) {
                return [
                    'id' => intval($element->id),
                    'title' => $element->title,
                    'status' => $element->getStatus(),
                    'cpEditUrl' => $element->cpEditUrl,
                    'dateUpdated' => $element->dateUpdated->format(DATE_ISO8601),
                ];
            }

            if ($element instanceof GlobalSet) {
                return [
                    'id' => intval($element->id),
                    'title' => $element->name,
                    'status' => $element->status,
                    'cpEditUrl' => $element->cpEditUrl,
                    'dateUpdated' => $element->dateUpdated->format(DATE_ISO8601),
                ];
            }

            if ($element instanceof MatrixBlock) {
                return [
                    'id' => intval($element->id),
                    'title' => $element->owner->title ?? $element->owner->name ?? 'Element',
                    'status' => $element->status,
                    'cpEditUrl' => $element->owner->cpEditUrl ?? '#!',
                    'dateUpdated' => $element->dateUpdated->format(DATE_ISO8601),
                ];
            }

            if ($element instanceof Asset) {
                return [
                    'id' => intval($element->id),
                    'title' => $element->owner->title ?? $element->owner->name ?? 'Element',
                    'status' => $element->status,
                    'cpEditUrl' => $element->cpEditUrl ?? '#!',
                    'dateUpdated' => $element->dateUpdated->format(DATE_ISO8601),
                ];
            }
        } catch (Exception $exception) {
            return null;
        }

        return null;
    }

    /**
     * Tries to find an element that could contain the
     * provided mixed content item.
     *
     * @throws SiteNotFoundException
     */
    private function findElementByMixedContentItem(MixedContent $mixedContentItem): ?array
    {
        $element = $this->findElementBySearchIndex($mixedContentItem->mixedContentUrl);

        if ($element === null) {
            $element = $this->findElementByUri($mixedContentItem->foundOnUrl);
        }

        if ($element === null) {
            return null;
        }

        return $this->transformElement($element);
    }

    /**
     * Tries to find an element that could contain the
     * provided broken link.
     *
     * @throws SiteNotFoundException
     */
    private function findElementByBrokenLink(BrokenLink $brokenLink): ?array
    {
        $element = $this->findElementBySearchIndex($brokenLink->crawledUrl);

        if ($element === null) {
            $element = $this->findElementByUri($brokenLink->foundOnUrl);
        }

        if ($element === null) {
            return null;
        }

        return $this->transformElement($element);
    }

    /**
     * Tries to find an Element by querying the search index
     * directly.
     *
     * @throws SiteNotFoundException
     */
    private function findElementBySearchIndex(string $crawledUrl): ?ElementInterface
    {
        $cleanKeywords = Search::normalizeKeywords($crawledUrl);

        $elementId = (new Query())
            ->select(['elementId'])
            ->from([Table::SEARCHINDEX])
            ->where(['keywords' => ' ' . $cleanKeywords . ' '])
            ->andWhere(['siteId' => Craft::$app->sites->getCurrentSite()->id])
            ->scalar();

        return Craft::$app->elements->getElementById($elementId);
    }

    private function findElementByUri(string $link): ?ElementInterface
    {
        $uri = ltrim(Url::fromString($link)->getPath(), '/');
        return Craft::$app->getElements()->getElementByUri($uri);
    }
}
