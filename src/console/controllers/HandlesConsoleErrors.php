<?php

namespace webhubworks\ohdear\console\controllers;

trait HandlesConsoleErrors
{
    private function parseErrorMessage(\Exception $e): string
    {
        $json = json_decode($e->getMessage());

        if (is_object($json) && isset($json->message)) {
            return $json->message;
        }

        return $e->getMessage();
    }
}
