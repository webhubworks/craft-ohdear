<?php

namespace webhubworks\ohdear\health\jobs;

use craft\helpers\DateTimeHelper;
use craft\queue\BaseJob;
use webhubworks\ohdear\health\checks\QueueCheck;

class QueueHealthJob extends BaseJob
{
    public function execute($queue): void
    {
        \Craft::$app->getCache()->set(
            QueueCheck::CACHE_KEY,
            DateTimeHelper::currentUTCDateTime()->format('U'),
        );
    }
}
