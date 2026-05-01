<?php

namespace webhubworks\ohdear\health\checks;

use Craft;
use craft\helpers\App;
use OhDear\HealthCheckResults\CheckResult;
use Symfony\Component\Process\Process;

class BackupHealthCheck extends Check
{
    private const BACKUP_PLUGIN_HANDLE = 'backup';

    private const MISSING_COMMAND_MESSAGE = 'The `backup/monitor` craft command is not available. Install https://github.com/webhubworks/craft-backup to enable this check.';

    public function run(): CheckResult
    {
        if (Craft::$app->plugins->getPlugin(self::BACKUP_PLUGIN_HANDLE) === null) {
            return $this->checkCouldNotRun(self::MISSING_COMMAND_MESSAGE);
        }

        $rootPath = Craft::getAlias('@root');
        $craftBinary = $rootPath . DIRECTORY_SEPARATOR . 'craft';

        if (! is_file($craftBinary)) {
            return $this->checkCouldNotRun("The Craft CLI binary could not be found at {$craftBinary}.");
        }

        $process = new Process([
            App::phpExecutable() ?? 'php',
            $craftBinary,
            'backup/monitor',
            '--color=0',
        ], $rootPath);
        $process->setTimeout(60);
        $process->run();

        $stdout = trim($process->getOutput());
        $stderr = trim($process->getErrorOutput());
        $decoded = json_decode($stdout, true);

        if (! is_array($decoded) || ! isset($decoded['status'])) {
            if ($this->looksLikeUnknownCommand($stdout, $stderr)) {
                return $this->checkCouldNotRun(self::MISSING_COMMAND_MESSAGE);
            }

            return $this->checkCouldNotRun(
                "The `backup/monitor` craft command did not return a valid response.",
                [
                    'exitCode' => $process->getExitCode(),
                    'stdout' => $stdout,
                    'stderr' => $stderr,
                ]
            );
        }

        $isOk = $decoded['status'] === 'ok';

        return new CheckResult(
            name: 'BackupHealth',
            label: 'Backup Health',
            notificationMessage: $this->buildNotificationMessage($decoded, $isOk),
            shortSummary: $isOk ? 'Healthy' : 'Unhealthy',
            status: $isOk ? CheckResult::STATUS_OK : CheckResult::STATUS_FAILED,
            meta: $this->buildMeta($decoded),
        );
    }

    private function checkCouldNotRun(string $message, array $meta = []): CheckResult
    {
        return new CheckResult(
            name: 'BackupHealth',
            label: 'Backup Health',
            notificationMessage: $message,
            shortSummary: 'Check could not run',
            status: CheckResult::STATUS_FAILED,
            meta: $meta,
        );
    }

    private function looksLikeUnknownCommand(string $stdout, string $stderr): bool
    {
        $haystack = strtolower($stdout . "\n" . $stderr);

        return str_contains($haystack, 'unknown command')
            || str_contains($haystack, 'no such command');
    }

    private function buildNotificationMessage(array $decoded, bool $isOk): string
    {
        if ($isOk) {
            return 'All configured backup targets are healthy.';
        }

        if (isset($decoded['reason']) && is_string($decoded['reason']) && $decoded['reason'] !== '') {
            return $decoded['reason'];
        }

        $reasons = [];
        foreach ($decoded['checks'] ?? [] as $check) {
            if (($check['status'] ?? null) !== 'failure' || empty($check['reason'])) {
                continue;
            }
            $target = is_string($check['target'] ?? null) && $check['target'] !== '' ? $check['target'] : null;
            $reasons[] = $target !== null ? "[{$target}] {$check['reason']}" : $check['reason'];
        }

        $reasons = array_values(array_unique($reasons));

        if ($reasons !== []) {
            return implode(' ', $reasons);
        }

        return 'One or more backup targets are unhealthy.';
    }

    private function buildMeta(array $decoded): array
    {
        $meta = [];

        foreach ($decoded['checks'] ?? [] as $check) {
            $target = $check['target'] ?? null;
            if (! is_string($target) || $target === '') {
                continue;
            }
            $meta[$target] = $check;
        }

        if (isset($decoded['reason']) && is_string($decoded['reason']) && $decoded['reason'] !== '') {
            $meta['reason'] = $decoded['reason'];
        }

        return $meta;
    }
}
