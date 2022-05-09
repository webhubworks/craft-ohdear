<?php
/**
 * Oh Dear plugin for Craft CMS 3.x
 *
 * Integrate Oh Dear into Craft CMS.
 *
 * @link      https://webhub.de
 * @copyright Copyright (c) 2019 webhub GmbH
 */

namespace webhubworks\ohdear\models;

use Craft;
use craft\base\Model;
use OhDear\PhpSdk\Resources\Site;
use webhubworks\ohdear\OhDear;

/**
 * OhDear Settings Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    webhub GmbH
 * @package   OhDear
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $apiToken = '';

    /**
     * @var string
     */
    public $selectedSiteId = '';

    /**
     * @var ?string
     */
    public $healthCheckSecret = null;

    /**
     * @var array
     */
    public $healthChecks = [];

    /**
     * Determines if the plugin has an API key and
     * a selected site ID.
     */
    public function isValid(): bool
    {
        return !empty($this->apiToken) && !empty($this->selectedSiteId);
    }

    /**
     * Parse the site ID if it is an env variable, otherwise
     * just return the value.
     */
    public function getSelectedSiteId(): string
    {
        return Craft::parseEnv($this->selectedSiteId);
    }

    /**
     * Parse the API token if it is an env variable, otherwise
     * just return the value.
     */
    public function getApiToken(): string
    {
        return Craft::parseEnv($this->apiToken);
    }

    public function getSite(): ?Site
    {
        if (!$this->isValid()) {
            return null;
        }

        return OhDear::$plugin->api->getSite($this->selectedSiteId);
    }

    public function getHealthReportUrl(string $healthReportUri): ?string
    {
        $site = $this->getSite();

        if ($site instanceof Site) {
            return implode("/", [$site->url, $healthReportUri]);
        }

        return null;
    }

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     */
    public function rules(): array
    {
        return [
            [['apiToken', 'selectedSiteId'], 'trim'],
            [['apiToken', 'selectedSiteId'], 'default', 'value' => ''],
            ['selectedSiteId', 'required', 'when' => function($model) {
                return !empty($model->apiToken);
            }],
        ];
    }
}
