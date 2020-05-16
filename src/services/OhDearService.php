<?php
/**
 * Oh Dear plugin for Craft CMS 3.x
 *
 * Integrate Oh Dear into Craft CMS.
 *
 * @link      https://webhub.de
 * @copyright Copyright (c) 2019 webhub GmbH
 */

namespace webhub\ohdear\services;

use Craft;
use craft\base\Component;
use craft\elements\GlobalSet;
use OhDear\PhpSdk\OhDear as OhDearSdk;
use OhDear\PhpSdk\Resources\BrokenLink;
use OhDear\PhpSdk\Resources\CertificateHealth;
use OhDear\PhpSdk\Resources\Check;
use OhDear\PhpSdk\Resources\MaintenancePeriod;
use OhDear\PhpSdk\Resources\MixedContentItem;
use OhDear\PhpSdk\Resources\Site;
use Spatie\Url\Url;
use webhub\ohdear\OhDear;

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

        $this->siteId = intval(OhDear::$plugin->getSettings()['selectedSiteId']);
        $this->apiToken = OhDear::$plugin->getSettings()['apiToken'];

        $this->ohDearClient = new OhDearSdk($this->apiToken);
    }
    // Public Methods
    // =========================================================================

    /**
     * @param string $startsAt Y:m:d H:i
     * @param string $endsAt Y:m:d H:i
     * @return MaintenancePeriod
     */
    public function createMaintenancePeriod(string $startsAt, string $endsAt)
    {
        return $this->ohDearClient->createSiteMaintenance($this->siteId, $startsAt, $endsAt);
    }

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
                'element' => $this->findElementByLink($brokenLink->foundOnUrl)
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
                'element' => $this->findElementByLink($mixedContentItem->foundOnUrl)
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

    private function findElementByLink(string $link)
    {

        // TODO: Was ist mit einem Link der bspw. aus einem GlobalSet kommt. Dann liefert der Code einen falschen Treffer.

        $uri = ltrim(Url::fromString($link)->getPath(), '/');

        $match = Craft::$app->getElements()->getElementByUri($uri);

        if ($match === null) {
            $searchQuery = str_replace(['://', '/', '-', '.', '?', '#'], [' ', ' ', ' ', ' ', ' ', ' '], $link);
            $match = GlobalSet::find()->search($searchQuery)->orderBy('score')->one();
        }
        /*if ($match === null) {
          another element type
        }*/
        switch (get_class($match)) {
            case 'craft\elements\Entry':
                return [
                    'id' => intval($match->id),
                    'title' => $match->title,
                    'status' => $match->status,
                    'cpEditUrl' => $match->cpEditUrl,
                    'dateUpdated' => $match->dateUpdated->format(DATE_ISO8601)
                ];
            case 'craft\elements\GlobalSet':
                return [
                    'id' => intval($match->id),
                    'title' => $match->name,
                    'status' => $match->status,
                    'cpEditUrl' => $match->cpEditUrl,
                    'dateUpdated' => $match->dateUpdated->format(DATE_ISO8601)
                ];
            /*case 'anothere element type'*/
            default:
                return null;
        }

    }
}
