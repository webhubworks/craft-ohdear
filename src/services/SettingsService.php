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
use OhDear\PhpSdk\Dto\Monitor;
use OhDear\PhpSdk\Dto\User;

/**
 * @author    webhub GmbH
 * @package   OhDear
 * @since     1.0.0
 */
class SettingsService extends Component
{
    public function getMonitor(string $apiToken, int $monitorId): Monitor
    {
        return (new OhDearSdk($apiToken))->monitor($monitorId);
    }
    
    /**
     * @return iterable|Monitor[]
     */
    public function getMonitors(string $apiToken): array
    {
        return (new OhDearSdk($apiToken))->monitors();
    }
    
    public function getMe(string $apiToken): User
    {
        return (new OhDearSdk($apiToken))->me();
    }
}
