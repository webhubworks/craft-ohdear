<?php

namespace webhubworks\ohdear\health\exceptions;

use Exception;

class DuplicateCheckNamesFound extends Exception
{
    public static function make(array $duplicateCheckNames): self
    {
        $duplicateCheckNamesString = array_map(fn (string $name) => "`{$name}`", $duplicateCheckNames);

        $duplicateCheckNamesString = implode(', ', $duplicateCheckNamesString);

        return new self("You registered checks with a non-unique name: {$duplicateCheckNamesString}. Each check should be unique. If you really want to use the same check class twice, make sure to call `name()` on them to ensure that they all have unique names.");
    }
}
