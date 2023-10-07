<?php

namespace webhubworks\ohdear\console\controllers;

use craft\helpers\Queue;
use webhubworks\ohdear\health\jobs\QueueHealthJob;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\BaseConsole;

class QueueHealthController extends Controller
{
    /**
     * Dispatches a lean test queue job. Used in conjunction with the queue health check. Docs: https://github.com/webhubworks/craft-ohdear/wiki/Queue-Health-Check
     */
    public function actionRun(): int
    {
        Queue::push(new QueueHealthJob());

        $this->stdout('Queue health check job dispatched.' . PHP_EOL, BaseConsole::FG_GREEN);

        return ExitCode::OK;
    }
}
