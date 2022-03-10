<?php

namespace webhubworks\ohdear\utilities;

use craft\base\Utility;
use webhubworks\ohdear\OhDear;

class HealthCheckUtility extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return \Craft::t('ohdear', 'Application Health');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'app-health';
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        // 😅
        $results = json_encode(json_decode(OhDear::$plugin->health->getCheckResults()->toJson()), JSON_PRETTY_PRINT);
        $heading = \Craft::t('ohdear', 'Current check results');
        return <<<HTML
<h2>$heading</h2>
<pre><code style="background-color: #282a35;color: #50FA7B;padding: .5rem;border-radius: .25rem">$results</code></pre>
HTML;
    }
}
