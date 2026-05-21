<?php

namespace webhubworks\ohdear\health\checks;

use Illuminate\Support\Collection;
use OhDear\HealthCheckResults\CheckResult;
use webhubworks\ohdear\health\exceptions\ComposerCommandFailed;
use yii\base\Exception;

class CveCheck extends Check implements Cacheable
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
            $advisoriesPerPackage = $auditResult['advisories'] ?? [];

            $advisoriesPerPackage = collect($advisoriesPerPackage)->map(function (array $advisories, string $packageName) {

                $whyResult = $this->getWhyResult($packageName);
                $packageInformation = $this->getPackageInformation($packageName);

                $requiredByPackage = $whyResult[0];
                $requiredByPackageVersion = $whyResult[1];
                $installedVersionConstraint = trim($whyResult[4], "()");
                $installedVersion = $packageInformation['versions'][0] ?? null;

                return [
                    'advisories' => $advisories,
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
                name: 'SecurityVulnerabilities',
                label: 'Security Vulnerabilities',
                notificationMessage: $e->getMessage(),
                shortSummary: 'Check could not run',
                status: CheckResult::STATUS_WARNING,
            );
        }

        return (new CheckResult(
            name: 'SecurityVulnerabilities',
            label: 'Security Vulnerabilities',
            notificationMessage: $this->getNotificationMessage($advisoriesPerPackage),
            shortSummary: $advisoriesPerPackage->isEmpty() ? 'Secure' : 'Insecure',
            status: $this->getCheckStatus($advisoriesPerPackage),
            meta: $advisoriesPerPackage->mapWithKeys(fn ($package, $packageName) => [
                $packageName => $this->getMetaValueForPackage($package),
            ])->toArray(),
        ));
    }

    private function getMetaValueForPackage(array $package): string
    {
        if (empty($package['advisories'])) {
            return 'No security vulnerabilities';
        }

        if (count($package['advisories']) === 1) {
            return '1 security vulnerability';
        }

        return count($package['advisories']) . ' security vulnerabilities';
    }

    private function getNotificationMessage(Collection $advisoriesPerPackage): string
    {
        if ($advisoriesPerPackage->isEmpty()) {
            return 'No security vulnerabilities found.';
        }

        if ($advisoriesPerPackage->count() === 1) {
            return '1 package with security vulnerabilities found!';
        }

        return $advisoriesPerPackage->count() . ' packages with security vulnerabilities found!';
    }

    private function getCheckStatus(\Tightenco\Collect\Support\Collection|Collection $advisoriesPerPackage): string
    {
        if ($advisoriesPerPackage->isEmpty()) {
            return CheckResult::STATUS_OK;
        }

        return CheckResult::STATUS_FAILED;
    }
}
