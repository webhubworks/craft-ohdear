<?php

namespace webhubworks\ohdear\controllers;

use craft\web\Controller;
use webhubworks\ohdear\OhDear;
use yii\web\ForbiddenHttpException;

class HealthCheckController extends Controller
{
    protected array|int|bool $allowAnonymous = ['results'];

    public function actionResults()
    {
        $this->ensureSecretIsValid();

        return OhDear::$plugin->health->getCheckResults()->toJson();
    }

    private function ensureSecretIsValid()
    {
        if (\Craft::$app->getUser()->getIdentity() && \Craft::$app->getUser()->getIdentity()->admin) {
            return;
        }

        $secretHeader = $this->request->headers->get('oh-dear-health-check-secret');

        if (is_string(OhDear::$plugin->settings->healthCheckSecret) && empty(OhDear::$plugin->settings->healthCheckSecret)) {
            throw new ForbiddenHttpException('No secret configured');
        }

        if (is_null($secretHeader)) {
            throw new ForbiddenHttpException('Invalid secret');
        }

        if ($secretHeader !== OhDear::$plugin->settings->healthCheckSecret) {
            throw new ForbiddenHttpException('Invalid secret');
        }
    }
}
