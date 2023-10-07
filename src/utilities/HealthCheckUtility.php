<?php

namespace webhubworks\ohdear\utilities;

use craft\base\Utility;
use craft\helpers\DateTimeHelper;
use webhubworks\ohdear\health\checks\QueueCheck;
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
        $queueHealthHeartbeat = \Craft::$app->getCache()->get(QueueCheck::CACHE_KEY);
        $queueHealthHeartbeatString = $queueHealthHeartbeat === false ? 'Did not run yet. Did you schedule the queue health check command?' : $queueHealthHeartbeat;
        $date = $queueHealthHeartbeat ? DateTimeHelper::toDateTime($queueHealthHeartbeat)->format('Y-m-d H:i:s e') : null;
        $checkResults = json_encode(json_decode(OhDear::$plugin->health->getCheckResults()->toJson()), JSON_PRETTY_PRINT);
        $heading1 = \Craft::t('ohdear', 'Latest heartbeat from Queue');
        $heading2 = \Craft::t('ohdear', 'Current check results');
        return <<<HTML
<h2>$heading1</h2>
<p>Queue Health Check docs on <a href="https://github.com/webhubworks/craft-ohdear/wiki/Queue-Health-Check" target="_blank">Github</a>.</p>
<pre><code style="background-color: #282a35;color: #50FA7B;padding: .5rem;border-radius: .25rem">$queueHealthHeartbeatString\n$date</code></pre>
<h2>$heading2</h2>
<pre><code style="background-color: #282a35;color: #50FA7B;padding: .5rem;border-radius: .25rem">$checkResults</code></pre>
HTML;
    }
}
