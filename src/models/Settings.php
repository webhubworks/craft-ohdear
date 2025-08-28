<?php
/**
 * Oh Dear plugin for Craft CMS 4.x
 *
 * Integrate Oh Dear into Craft CMS.
 *
 * @link      https://webhub.de
 * @copyright Copyright (c) 2019 webhub GmbH
 */

namespace webhubworks\ohdear\models;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;
use OhDear\PhpSdk\OhDear as OhDearSdk;
use OhDear\PhpSdk\Resources\Site;
use webhubworks\ohdear\OhDear;
use OhDear\PhpSdk\Exceptions\UnauthorizedException;
use OhDear\PhpSdk\Resources\User as OhDearUser;
use OhDear\PhpSdk\Resources\Site as OhDearSite;

/**
 * @author    webhub GmbH
 * @package   OhDear
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public string $pluginName = 'Oh Dear';

    public string $apiToken = '';

    public string $selectedSiteId = '';

    public bool $showNavBadges = false;

    public ?string $healthCheckSecret = null;

    public array $healthChecks = [];

    public function behaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => ['apiToken', 'selectedSiteId'],
            ],
        ];
    }

    /**
     * Determines if the plugin has an API key and
     * a selected site ID.
     */
    public function hasApiCredentials(): bool
    {
        return ! empty($this->apiToken) && ! empty($this->selectedSiteId);
    }

    public function getApiToken(): string
    {
        return App::parseEnv($this->apiToken);
    }

    public function getSelectedSiteId(): string
    {
        return App::parseEnv($this->selectedSiteId);
    }

    public function getHealthReportUrl(string $healthReportUri): ?string
    {
        try {
            $site = OhDear::$plugin->api->getMonitor();
            if ($site instanceof Site) {
                return implode('/', [
                    rtrim($site->url, '/'),
                    ltrim($healthReportUri, '/'),
                ]);
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function rules(): array
    {
        return [
            ['pluginName', 'string'],
            ['pluginName', 'default', 'value' => 'Oh Dear'],
            [['showNavBadges'], 'boolean'],
            [['apiToken', 'selectedSiteId'], 'trim'],
            [['apiToken', 'selectedSiteId'], 'default', 'value' => ''],
            ['selectedSiteId', 'required', 'when' => function ($model) {
                return ! empty($model->apiToken);
            }],
            [['apiToken'], 'validApiToken'],
            [['selectedSiteId'], 'validSelectedSiteId'],
        ];
    }

    public function validApiToken($attribute, $params): void
    {
        try {
            OhDear::$plugin->settingsService->getMe($this->{$attribute});
        } catch (UnauthorizedException $e) {
            $this->addError('apiToken', Craft::t('ohdear', 'API authentication failed.'));
        } catch (\Exception $e) {
            $this->addError('apiToken', $e->getMessage());
        }
    }

    public function validSelectedSiteId($attribute, $params): void
    {
        try {
            OhDear::$plugin->settingsService->getSite(
                $this->apiToken,
                (int)$this->{$attribute}
            );
        } catch (UnauthorizedException $e) {
            $this->addError('selectedSiteId', Craft::t('ohdear', 'API authentication failed.'));
        } catch (\Exception $e) {
            $this->addError('selectedSiteId', $e->getMessage());
        }
    }
}
