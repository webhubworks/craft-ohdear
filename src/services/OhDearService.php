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
use OhDear\PhpSdk\OhDear as OhDearSdk;
use OhDear\PhpSdk\Resources\BrokenLink;
use OhDear\PhpSdk\Resources\CertificateHealth;
use OhDear\PhpSdk\Resources\Check;
use OhDear\PhpSdk\Resources\MaintenancePeriod;
use OhDear\PhpSdk\Resources\MixedContentItem;
use OhDear\PhpSdk\Resources\Site;
use OhDear\PhpSdk\Resources\Uptime;
use Spatie\Url\Url;
use webhubworks\ohdear\OhDear;

/**
 * @author    webhub GmbH
 * @package   OhDear
 * @since     1.0.0
 *
 * @property-read array $brokenLinks
 * @property-read CertificateHealth $certificateHealth
 * @property-read Site $site
 * @property-read array $mixedContent
 */
class OhDearService extends Component
{
    private OhDearSdk $ohDearClient;
    private $siteId;
    private $apiToken;

    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->siteId = intval(OhDear::$plugin->getSettings()->getSelectedSiteId());
        $this->apiToken = OhDear::$plugin->getSettings()->getApiToken();

        $this->ohDearClient = new OhDearSdk($this->apiToken);
    }

    public function createMaintenancePeriod(string $startsAt, string $endsAt): MaintenancePeriod
    {
        return $this->ohDearClient->createSiteMaintenance($this->siteId, $startsAt, $endsAt);
    }

    public function deleteMaintenancePeriod(int $maintenancePeriodId)
    {
        $this->ohDearClient->deleteSiteMaintenance($maintenancePeriodId);
    }

    public function startMaintenancePeriod(int $stopMaintenanceAfterSeconds = 60 * 60): MaintenancePeriod
    {
        return $this->ohDearClient->startSiteMaintenance($this->siteId, $stopMaintenanceAfterSeconds);
    }

    public function stopMaintenancePeriod()
    {
        $this->ohDearClient->stopSiteMaintenance($this->siteId);
    }

    public function maintenancePeriods(): array
    {
        return $this->ohDearClient->maintenancePeriods($this->siteId);
    }

    public function getSites(?string $apiToken = null): array
    {
        return $this->ohDearClient->sites();
    }

    public function getSite(): Site
    {
        return $this->ohDearClient->site($this->siteId);
    }

    public function getUptime(string $startedAt, string $endedAt, string $split = 'month'): array
    {
        return $this->ohDearClient->uptime($this->siteId, $startedAt, $endedAt, $split);
    }

    public function leftPadUptimeToMonday(array $uptimes): array
    {
        if (!count($uptimes)) {
            return $uptimes;
        }

        $firstUptimeDate = Carbon::parse($uptimes[0]->datetime);

        $daysToPad = $firstUptimeDate->isoWeekday() - 1;

        $pad = [];

        for ($i = $daysToPad; $i > 0; $i--) {
            $pad[] = new Uptime([
                'datetime' => $firstUptimeDate->copy()->subDays($i)->toDateTimeString(),
                'uptimePercentage' => 0,
            ]);
        }

        return [
            ...$pad,
            ...$uptimes,
        ];
    }

    public function getDowntime(string $startedAt, string $endedAt): array
    {
        return $this->ohDearClient->downtime($this->siteId, $startedAt, $endedAt);
    }

    public function getBrokenLinks(): array
    {
        return array_map(function(BrokenLink $brokenLink) {
            return [
                'crawledUrl' => $brokenLink->crawledUrl,
                'foundOnUrl' => $brokenLink->foundOnUrl,
                'statusCode' => $brokenLink->statusCode,
                'element' => $this->findElementByBrokenLink($brokenLink),
            ];
        }, $this->ohDearClient->brokenLinks($this->siteId));
    }

    public function getMixedContent(): array
    {
        return array_map(function(MixedContentItem $mixedContentItem) {
            return [
                'mixedContentUrl' => $mixedContentItem->mixedContentUrl,
                'foundOnUrl' => $mixedContentItem->foundOnUrl,
                'elementName' => $mixedContentItem->elementName,
                'element' => $this->findElementByMixedContentItem($mixedContentItem),
            ];
        }, $this->ohDearClient->mixedContent($this->siteId));
    }

    public function getCertificateHealth(): CertificateHealth
    {
        return $this->ohDearClient->certificateHealth($this->siteId);
    }

    public function getApplicationHealthChecks(): array
    {
        return $this->ohDearClient->applicationHealthChecks($this->siteId);
    }

    public function getApplicationHealthCheckResults(int $applicationHealthCheckId): array
    {
        return $this->ohDearClient->applicationHealthCheckResults($this->siteId, $applicationHealthCheckId);
    }

    public function getCronChecks(): array
    {
        return $this->ohDearClient->cronChecks($this->siteId);
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

    public function getPerformance(string $start, string $end, ?string $groupBy = null): array
    {
        if (is_null($groupBy)) {
            return $this->ohDearClient->performanceRecords($this->siteId, $start, $end);
        }

        return $this->ohDearClient->performanceRecords($this->siteId, $start, $end, $groupBy);
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
        return $this->ohDearClient->requestRun($checkId);
    }

    private function transformElement(?ElementInterface $element): ?array
    {
        try {
            if ($element instanceof Entry) {
                return [
                    'id' => intval($element->id),
                    'title' => $element->title,
                    'status' => $element->status,
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
    private function findElementByMixedContentItem(MixedContentItem $mixedContentItem): ?array
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
