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

    // Public Methods
    // =========================================================================

    /**
     * Determines if the plugin has an API key and
     * a selected site ID.
     *
     * @return bool
     */
    public function isValid()
    {
        return !empty($this->apiToken) && !empty($this->selectedSiteId);
    }

    /**
     * Parse the API token if it is an env variable, otherwise
     * just return the value.
     *
     * @return string
     */
    public function getApiToken(): string
    {
        return Craft::parseEnv($this->apiToken);
    }

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['apiToken', 'selectedSiteId'], 'trim'],
            [['apiToken', 'selectedSiteId'], 'default', 'value' => ''],
            ['selectedSiteId', 'required', 'when' => function ($model) {
                return !empty($model->apiToken);
            }],
            ['selectedSiteId', 'number'],
        ];
    }
}
