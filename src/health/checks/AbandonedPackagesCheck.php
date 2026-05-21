<?php

namespace webhubworks\ohdear\health\checks;

use Illuminate\Support\Collection;
use OhDear\HealthCheckResults\CheckResult;
use webhubworks\ohdear\health\exceptions\ComposerCommandFailed;
use yii\base\Exception;

class AbandonedPackagesCheck extends Check implements Cacheable
{

    use CachesResult;
    use RunsComposer;

    /**
     * @throws Exception
     */
    protected function compute(): CheckResult
    {
        try {
            $auditResult = $this->getAuditResult();
            $abandonedPackages = $auditResult['abandoned'] ?? [];

            $abandonedPackages = collect($abandonedPackages)->map(function (string $newPackage, string $abandonedPackage) {
                $whyResult = $this->getWhyResult($abandonedPackage);
                $packageInformation = $this->getPackageInformation($abandonedPackage);

                $requiredByPackage = $whyResult[0];
                $requiredByPackageVersion = $whyResult[1];
                $installedVersionConstraint = trim($whyResult[4], "()");
                $installedVersion = $packageInformation['versions'][0] ?? null;

                return [
                    'installedVersion' => $installedVersion,
                    'installedVersionConstraint' => $installedVersionConstraint,
                    'requiredBy' => [
                        'packageName' => $requiredByPackage,
                        'installedVersion' => $requiredByPackageVersion,
                    ]
                ];
            });
        } catch (ComposerCommandFailed $e) {
            return new CheckResult(
                name: 'AbandonedPackages',
                label: 'Abandoned Packages',
                notificationMessage: $e->getMessage(),
                shortSummary: 'Check could not run',
                status: CheckResult::STATUS_WARNING,
            );
        }

        return (new CheckResult(
            name: 'AbandonedPackages',
            label: 'Abandoned Packages',
            notificationMessage: $this->getNotificationMessage($abandonedPackages),
            shortSummary: $abandonedPackages->isEmpty() ? 'All maintained' : 'Some abandoned',
            status: $this->getCheckStatus($abandonedPackages),
            meta: $this->getMetaValueForPackages($abandonedPackages),
        ));
    }

    private function getMetaValueForPackages(Collection $packages): array
    {
        return $packages->mapWithKeys(fn ($package, $packageName) => [
            $packageName => [
                'requiredBy' => $package['requiredBy'],
            ],
        ])->toArray();
    }

    private function getNotificationMessage(Collection $abandonedPackages): string
    {
        if ($abandonedPackages->isEmpty()) {
            return 'No abandoned packages found.';
        }

        if ($abandonedPackages->count() === 1) {
            return '1 abandoned package found!';
        }

        return $abandonedPackages->count() . ' abandoned packages found!';
    }

    private function getCheckStatus(\Tightenco\Collect\Support\Collection|Collection $abandonedPackages): string
    {
        if ($abandonedPackages->isEmpty()) {
            return CheckResult::STATUS_OK;
        }

        return CheckResult::STATUS_FAILED;
    }
}
