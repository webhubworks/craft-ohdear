<?php

namespace webhubworks\ohdear\health\exceptions;

use Exception;
use Symfony\Component\Process\Process;

class ComposerCommandFailed extends Exception
{
    public static function invalidJsonOutput(Process $process, string $label): self
    {
        return new self(sprintf(
            '%s failed (exit %d): %s',
            $label,
            $process->getExitCode() ?? -1,
            trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'no output',
        ));
    }

    public static function emptyOutput(Process $process, string $label): self
    {
        return new self(sprintf(
            '%s failed (exit %d): %s',
            $label,
            $process->getExitCode() ?? -1,
            trim($process->getErrorOutput()) ?: 'no output',
        ));
    }

    public static function unexpectedOutput(string $label, string $output): self
    {
        return new self(sprintf('%s returned unexpected output: %s', $label, $output));
    }

    public static function setupFailed(string $reason): self
    {
        return new self(sprintf('Could not prepare composer.phar: %s', $reason));
    }
}
