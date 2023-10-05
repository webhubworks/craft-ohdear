<?php

namespace webhubworks\ohdear\services;

use Craft;
use craft\base\Component;
use webhubworks\ohdear\OhDear;

class BadgeCountService extends Component
{
    public function getMixedContentCount(): ?int
    {
        return Craft::$app->getCache()->getOrSet('ohdear-mixed-content-count', function () {
            try {
                $mixedContentCount = count(OhDear::$plugin->api->getMixedContent());
                return $mixedContentCount > 0 ? $mixedContentCount : null;
            } catch (\Exception $e) {
                return null;
            }
        }, 60);
    }

    public function getBrokenLinksCount(): ?int
    {
        return Craft::$app->getCache()->getOrSet('ohdear-broken-links-count', function () {
            try {
                $brokenLinkCount = count(OhDear::$plugin->api->getBrokenLinks());
                return $brokenLinkCount > 0 ? $brokenLinkCount : null;
            } catch (\Exception $e) {
                return null;
            }
        }, 60);
    }

    public function getTotalCount(): ?int
    {
        $counts = array_filter([
            $this->getMixedContentCount(),
            $this->getBrokenLinksCount()
        ]);

        return empty($counts) ? null : array_sum($counts);
    }
}
