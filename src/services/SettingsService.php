<?php
/**
 * Oh Dear plugin for Craft CMS 4.x
 *
 * Integrate Oh Dear into Craft CMS.
 *
 * @link      https://webhub.de
 * @copyright Copyright (c) 2019 webhub GmbH
 */

namespace webhubworks\ohdear\services;

use craft\base\Component;
use OhDear\PhpSdk\OhDear as OhDearSdk;
use webhubworks\ohdear\OhDear;

/**
 * https://craftcms.com/docs/plugins/services
 *
 * @author    webhub GmbH
 * @package   OhDear
 * @since     1.0.0
 */
class SettingsService extends Component
{
    /**
     * @param string $apiToken
     * @return array
     */
    public function getSites($apiToken)
    {
        return (new OhDearSdk($apiToken))->sites();
    }
}
