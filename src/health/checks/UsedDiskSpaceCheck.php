<?php

namespace webhubworks\ohdear\health\checks;

use OhDear\HealthCheckResults\CheckResult;
use OhDear\HealthCheckResults\CheckResults;
use Spatie\Regex\Regex;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException;
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
        $result = new CheckResult(
            name: 'UsedDiskSpace',
            label: 'Used disk space',
        );

        try {
            $diskSpaceUsedPercentage = $this->getDiskUsagePercentage();
        } catch (\Throwable $e) {
            return $result
                ->shortSummary("Cannot evaluate")
                ->meta(['error' => $e->getMessage()])
                ->status(CheckResult::STATUS_CRASHED)
                ->notificationMessage("Error evaluating disk space on server.");
        }

        $result->shortSummary = "{$diskSpaceUsedPercentage}%";
        $result->meta = [
            'usedDiskSpacePercentage' => $diskSpaceUsedPercentage,
            'warningThreshold' => $this->warningThreshold,
            'errorThreshold' => $this->errorThreshold,
        ];

        if ($diskSpaceUsedPercentage > $this->errorThreshold) {
            return $result
                ->status(CheckResult::STATUS_FAILED)
                ->notificationMessage("The disk is almost full ({$diskSpaceUsedPercentage}% used).");
        }

        if ($diskSpaceUsedPercentage > $this->warningThreshold) {
            return $result
                ->status(CheckResult::STATUS_WARNING)
                ->notificationMessage("The disk is quite full ({$diskSpaceUsedPercentage}% used).");
        }

        return $result->status(CheckResult::STATUS_OK)
            ->notificationMessage('The disk has plenty of space left.');
    }

    /**
     * @return int
     * @throws \Spatie\Regex\Exceptions\RegexFailed
     * @throws LogicException               When proc_open is not installed
     * @throws RuntimeException             When process can't be launched
     * @throws RuntimeException             When process is already running
     * @throws ProcessTimedOutException     When process timed out
     * @throws ProcessSignaledException     When process stopped after receiving signal
     * @throws LogicException               In case a callback is provided and output has been disabled
     * @throws LogicException               In case the output has been disabled
     * @throws LogicException               In case the process is not started
     */
    protected function getDiskUsagePercentage(): int
    {
        $process = Process::fromShellCommandline('df -P .');

        $process->run();

        $output = $process->getOutput();

        return (int)Regex::match('/(\d*)%/', $output)->group(1);
    }
}
