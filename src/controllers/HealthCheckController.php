<?php

namespace webhubworks\ohdear\controllers;

use craft\helpers\DateTimeHelper;
use craft\web\Controller;
use OhDear\HealthCheckResults\CheckResults;
use webhubworks\ohdear\health\checks\Check;
use webhubworks\ohdear\OhDear;

class HealthCheckController extends Controller
{

    protected $allowAnonymous = ['results'];

    public function actionResults()
    {
        $checkResults = array_map(function(Check $check) {
            return $check->run();
        }, OhDear::$plugin->health->registeredChecks());

        return (new CheckResults(null, $checkResults))->toJson();
    }
}
