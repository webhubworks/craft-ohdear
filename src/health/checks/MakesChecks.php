<?php

namespace webhubworks\ohdear\health\checks;

trait MakesChecks
{
    public static function availableUpdates(): AvailableUpdatesCheck
    {
        return AvailableUpdatesCheck::new();
    }

    public static function devMode(): DevModeCheck
    {
        return DevModeCheck::new();
    }

    public static function environment(): EnvironmentCheck
    {
        return EnvironmentCheck::new();
    }

    public static function failedJobs(): FailedJobsCheck
    {
        return FailedJobsCheck::new();
    }

    public static function serverRequirements(): ServerRequirementsCheck
    {
        return ServerRequirementsCheck::new();
    }

    public static function usedDiskSpace(): UsedDiskSpaceCheck
    {
        return UsedDiskSpaceCheck::new();
    }

    public static function queueHealth(): QueueCheck
    {
        return QueueCheck::new();
    }
}
