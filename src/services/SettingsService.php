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
use OhDear\PhpSdk\Resources\Site;
use OhDear\PhpSdk\Resources\User;

/**
 * @author    webhub GmbH
 * @package   OhDear
 * @since     1.0.0
 */
class SettingsService extends Component
{
    public function getSite(string $apiToken, int $siteId): Site
    {
        return (new OhDearSdk($apiToken))->site($siteId);
    }

    public function getSites(string $apiToken): array
    {
        return (new OhDearSdk($apiToken))->sites();
    }

    public function getMe(string $apiToken): User
    {
        return (new OhDearSdk($apiToken))->me();
    }
}
