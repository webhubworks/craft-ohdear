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
use OhDear\PhpSdk\OhDear as OhDearSdk;
use OhDear\PhpSdk\Resources\Site;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use webhubworks\ohdear\OhDear;
use OhDear\PhpSdk\Exceptions\UnauthorizedException;
use OhDear\PhpSdk\Exceptions\FailedActionException;
use OhDear\PhpSdk\Exceptions\NotFoundException;
use OhDear\PhpSdk\Exceptions\ValidationException;
use OhDear\PhpSdk\Resources\User as OhDearUser;
use OhDear\PhpSdk\Resources\Site as OhDearSite;

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
        return ! empty($this->apiToken) && ! empty($this->selectedSiteId);
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
        if (! $this->isValid()) {
            return null;
        }

        return OhDear::$plugin->api->getSite($this->selectedSiteId);
    }

    public function getHealthReportUrl(string $healthReportUri): ?string
    {
        try {
            $site = $this->getSite();
            if ($site instanceof Site) {
                return implode("/", [$site->url, $healthReportUri]);
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
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
            $user = (new OhDearSdk($this[$attribute]))->me();
            if (! ($user instanceof OhDearUser)) {
                throw new \Exception('Invalid API response. Please contact support.');
            }
        } catch (UnauthorizedException $e) {
            $this->addError('apiToken', 'API authentication failed.');
        } catch (\Exception $e) {
            $this->addError('apiToken', $e->getMessage());
        }
    }

    public function validSelectedSiteId($attribute, $params): void
    {
        if (! $this->isValid()) {
            return;
        }

        try {
            $site = (new OhDearSdk($this->apiToken))->site($this[$attribute]);
            if (! ($site instanceof OhDearSite)) {
                throw new \Exception('Invalid API response. Please contact support.');
            }
        } catch (UnauthorizedException $e) {
            $this->addError('selectedSiteId', 'API authentication failed.');
        } catch (\Exception $e) {
            $this->addError('selectedSiteId', $e->getMessage());
        }
    }
}
