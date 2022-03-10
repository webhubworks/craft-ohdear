<?php

namespace webhubworks\ohdear\controllers;

use craft\helpers\DateTimeHelper;
use craft\web\Controller;
use OhDear\HealthCheckResults\CheckResults;
use webhubworks\ohdear\health\checks\Check;
use webhubworks\ohdear\OhDear;
use yii\web\ForbiddenHttpException;

class HealthCheckController extends Controller
{
    protected $allowAnonymous = ['results'];

    public function actionResults()
    {
        $this->ensureSecretIsValid();

        $checkResults = array_map(function (Check $check) {
            try {
                return $check->run();
            } catch (\Throwable $e) {
                return $check->getGenericCrashResult($e->getMessage());
            }
        }, OhDear::$plugin->health->registeredChecks());

        return (new CheckResults(null, $checkResults))->toJson();
    }

    private function ensureSecretIsValid()
    {
        $secretHeader = $this->request->headers->get('oh-dear-health-check-secret');

        if (is_null($secretHeader)) {
            throw new ForbiddenHttpException('Invalid secret');
        }

        if ($secretHeader !== OhDear::$plugin->settings->healthCheckSecret) {
            throw new ForbiddenHttpException('Invalid secret');
        }
    }
}
