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

use craft\base\Component;
use craft\elements\GlobalSet;
use OhDear\PhpSdk\OhDear as OhDearSdk;
use OhDear\PhpSdk\Resources\BrokenLink;
use OhDear\PhpSdk\Resources\CertificateHealth;
use OhDear\PhpSdk\Resources\Check;
use OhDear\PhpSdk\Resources\MixedContentItem;
use OhDear\PhpSdk\Resources\Site;
use Spatie\Url\Url;
use webhub\ohdear\OhDear;
use yii\base\Exception;

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
class SettingsService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * @param string $apiToken
     * @return array
     */
    public function getSites($apiToken)
    {
        return (new OhDearSdk($apiToken))->sites();
    }
}
