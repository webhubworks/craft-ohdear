<?php

namespace webhubworks\ohdear\health\checks;

use OhDear\HealthCheckResults\CheckResult;
use OhDear\HealthCheckResults\CheckResults;
use Spatie\Regex\Regex;
use Symfony\Component\Process\Process;
use webhubworks\ohdear\health\checks\Check;

class UsedDiskSpaceCheck extends Check
{
    protected int $warningThreshold = 70;
    protected int $errorThreshold = 90;

    public function warnWhenUsedSpaceIsAbovePercentage(int $percentage): self
    {
        $this->warningThreshold = $percentage;

        return $this;
    }

    public function failWhenUsedSpaceIsAbovePercentage(int $percentage): self
    {
        $this->errorThreshold = $percentage;

        return $this;
    }

    public function run(): CheckResult
    {
        $diskSpaceUsedPercentage = $this->getDiskUsagePercentage();

        $result = new CheckResult(
            name: 'UsedDiskSpace',
            label: 'Used disk space',
            shortSummary: "{$diskSpaceUsedPercentage}%",
            meta: ['used_disk_space_percentage' => $diskSpaceUsedPercentage]
        );

        if ($diskSpaceUsedPercentage > $this->errorThreshold) {
            return $result
                ->status(CheckResult::STATUS_FAILED)
                ->notificationMessage("The disk is almost full ({$diskSpaceUsedPercentage}% used).");
        }

        if ($diskSpaceUsedPercentage > $this->warningThreshold) {
            return $result
                ->status(CheckResult::STATUS_WARNING)
                ->notificationMessage("The disk is almost full ({$diskSpaceUsedPercentage}% used).");
        }

        return $result->status(CheckResult::STATUS_OK)
            ->notificationMessage('The disk has plenty of space left.');
    }

    protected function getDiskUsagePercentage(): int
    {
        $process = Process::fromShellCommandline('df -P .');

        $process->run();

        $output = $process->getOutput();

        return (int)Regex::match('/(\d*)%/', $output)->group(1);
    }
}
