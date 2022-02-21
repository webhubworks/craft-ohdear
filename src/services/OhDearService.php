<?php
/**
 * Oh Dear plugin for Craft CMS 3.x
 *
 * Integrate Oh Dear into Craft CMS.
 *
 * @link      https://webhub.de
 * @copyright Copyright (c) 2019 webhub GmbH
 */

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
use Spatie\Url\Url;
use webhubworks\ohdear\OhDear;

/**
 * OhDearService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
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
    /**
     * @var OhDearSdk
     */
    private $ohDearClient;
    private $siteId;
    private $apiToken;

    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->siteId = intval(OhDear::$plugin->getSettings()->getSelectedSiteId());
        $this->apiToken = OhDear::$plugin->getSettings()->getApiToken();

        $this->ohDearClient = new OhDearSdk($this->apiToken);
    }

    /**
     * @param string $startsAt Y:m:d H:i
     * @param string $endsAt Y:m:d H:i
     * @return MaintenancePeriod
     */
    public function createMaintenancePeriod(string $startsAt, string $endsAt)
    {
        return $this->ohDearClient->createSiteMaintenance($this->siteId, $startsAt, $endsAt);
    }

    /**
     * @param int $maintenancePeriodId
     * @return void
     */
    public function deleteMaintenancePeriod(int $maintenancePeriodId)
    {
        $this->ohDearClient->deleteSiteMaintenance($maintenancePeriodId);
    }

    /**
     * @param int|null $stopMaintenanceAfterSeconds
     * @return MaintenancePeriod
     */
    public function startMaintenancePeriod(int $stopMaintenanceAfterSeconds = 60 * 60): MaintenancePeriod
    {
        return $this->ohDearClient->startSiteMaintenance($this->siteId, $stopMaintenanceAfterSeconds);
    }

    /**
     * @return void
     */
    public function stopMaintenancePeriod()
    {
        $this->ohDearClient->stopSiteMaintenance($this->siteId);
    }

    /**
     * @return array
     */
    public function maintenancePeriods()
    {
        return $this->ohDearClient->maintenancePeriods($this->siteId);
    }

    /**
     * @param string|null $apiToken
     * @return array
     */
    public function getSites($apiToken = null)
    {
        return $this->ohDearClient->sites();
    }

    /**
     * @return Site
     */
    public function getSite()
    {
        return $this->ohDearClient->site($this->siteId);
    }

    /**
     * @param string $startedAt - YYYYMMDDHHmmss
     * @param string $endedAt - YYYYMMDDHHmmss
     * @param string $split - hour|day|month
     * @return array
     */
    public function getUptime($startedAt, $endedAt, $split = 'month')
    {
        return $this->ohDearClient->uptime($this->siteId, $startedAt, $endedAt, $split);
    }

    /**
     * @param string $startedAt - YYYYMMDDHHmmss
     * @param string $endedAt - YYYYMMDDHHmmss
     * @return array
     */
    public function getDowntime($startedAt, $endedAt)
    {
        return $this->ohDearClient->downtime($this->siteId, $startedAt, $endedAt);
    }

    /**
     * @return array
     */
    public function getBrokenLinks()
    {
        return array_map(function (BrokenLink $brokenLink) {
            return [
                'crawledUrl' => $brokenLink->crawledUrl,
                'foundOnUrl' => $brokenLink->foundOnUrl,
                'statusCode' => $brokenLink->statusCode,
                'element' => $this->findElementByBrokenLink($brokenLink)
            ];
        }, $this->ohDearClient->brokenLinks($this->siteId));
    }

    /**
     * @return array
     */
    public function getMixedContent()
    {
        return array_map(function (MixedContentItem $mixedContentItem) {
            return [
                'mixedContentUrl' => $mixedContentItem->mixedContentUrl,
                'foundOnUrl' => $mixedContentItem->foundOnUrl,
                'elementName' => $mixedContentItem->elementName,
                'element' => $this->findElementByMixedContentItem($mixedContentItem)
            ];
        }, $this->ohDearClient->mixedContent($this->siteId));
    }

    /**
     * @return CertificateHealth
     */
    public function getCertificateHealth()
    {
        return $this->ohDearClient->certificateHealth($this->siteId);
    }

    /**
     * Returns the average total time of the last 10 minutes in ms.
     * Returns null if there are no records.
     *
     * @return int|null
     */
    public function getCurrentPerformance()
    {
        $lastTenMinutes = $this->getPerformance(Carbon::now()->subMinutes(10), Carbon::now());

        $totalTimes_s = array_filter(array_column($lastTenMinutes['data']->attributes, 'total_time_in_seconds'));
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
     * @param string $timeframe
     * @return array
     */
    public function getPerformance(string $start, string $end, ?string $timeframe = null)
    {
        if (is_null($timeframe)) {
            return $this->ohDearClient->performanceRecords($this->siteId, $start, $end);
        }

        return $this->ohDearClient->performanceRecords($this->siteId, $start, $end, $timeframe);
    }

    /**
     * @param int $checkId
     * @return Check
     */
    public function disableCheck($checkId)
    {
        return $this->ohDearClient->disableCheck($checkId);
    }

    /**
     * @param int $checkId
     * @return Check
     */
    public function enableCheck($checkId)
    {
        return $this->ohDearClient->enableCheck($checkId);
    }

    /**
     * @param int $checkId
     * @return Check
     */
    public function requestRun($checkId)
    {
        return $this->ohDearClient->requestRun($checkId);
    }

    /**
     * @param ElementInterface|null $element
     * @return array|null
     */
    private function transformElement($element)
    {

        try {

            if ($element instanceof Entry) {
                return [
                    'id' => intval($element->id),
                    'title' => $element->title,
                    'status' => $element->status,
                    'cpEditUrl' => $element->cpEditUrl,
                    'dateUpdated' => $element->dateUpdated->format(DATE_ISO8601)
                ];
            }

            if ($element instanceof GlobalSet) {
                return [
                    'id' => intval($element->id),
                    'title' => $element->name,
                    'status' => $element->status,
                    'cpEditUrl' => $element->cpEditUrl,
                    'dateUpdated' => $element->dateUpdated->format(DATE_ISO8601)
                ];
            }

            if ($element instanceof MatrixBlock) {
                return [
                    'id' => intval($element->id),
                    'title' => $element->owner->title ?? $element->owner->name ?? 'Element',
                    'status' => $element->status,
                    'cpEditUrl' => $element->owner->cpEditUrl ?? '#!',
                    'dateUpdated' => $element->dateUpdated->format(DATE_ISO8601)
                ];
            }

            if ($element instanceof Asset) {
                return [
                    'id' => intval($element->id),
                    'title' => $element->owner->title ?? $element->owner->name ?? 'Element',
                    'status' => $element->status,
                    'cpEditUrl' => $element->cpEditUrl ?? '#!',
                    'dateUpdated' => $element->dateUpdated->format(DATE_ISO8601)
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
     * @param MixedContentItem $mixedContentItem
     * @return array|null
     * @throws SiteNotFoundException
     */
    private function findElementByMixedContentItem(MixedContentItem $mixedContentItem)
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
     * @param BrokenLink $brokenLink
     * @return array|null
     * @throws SiteNotFoundException
     */
    private function findElementByBrokenLink(BrokenLink $brokenLink)
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
     * @param string $crawledUrl
     * @return ElementInterface|null
     * @throws SiteNotFoundException
     */
    private function findElementBySearchIndex(string $crawledUrl)
    {
        $cleanKeywords = Search::normalizeKeywords($crawledUrl);

        $elementId = (new Query)
            ->select(['elementId'])
            ->from([Table::SEARCHINDEX])
            ->where(['keywords' => ' ' . $cleanKeywords . ' '])
            ->andWhere(['siteId' => Craft::$app->sites->getCurrentSite()->id])
            ->scalar();

        return Craft::$app->elements->getElementById($elementId);
    }

    /**
     * Tries to find an element by its URI.
     *
     * @param string $link
     * @return ElementInterface|null
     */
    private function findElementByUri(string $link)
    {

        $uri = ltrim(Url::fromString($link)->getPath(), '/');

        return Craft::$app->getElements()->getElementByUri($uri);

    }
}
