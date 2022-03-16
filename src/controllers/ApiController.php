<?php
/**
 * Oh Dear plugin for Craft CMS 3.x
 *
 * Integrate Oh Dear into Craft CMS.
 *
 * @link      https://webhub.de
 * @copyright Copyright (c) 2019 webhub GmbH
 */

namespace webhubworks\ohdear\controllers;

use Craft;
use craft\web\Controller;
use webhubworks\ohdear\OhDear;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class ApiController extends Controller
{
    /**
     * List sites that are available for this API token.
     *
     * If an API token is present in the request query
     * it will be taken instead of the token that
     * may be present in the plugin settings.
     *
     * @see https://ohdear.app/swagger#/sites/get_sites
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionSites()
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $apiTokenParam = Craft::$app->request->getQueryParam('api-token', null);

        if ($apiTokenParam === null) {
            $sites = OhDear::$plugin->api->getSites();
        } else {
            $sites = OhDear::$plugin->settingsService->getSites(
                Craft::parseEnv($apiTokenParam)
            );
        }

        return $this->asJson([
            'sites' => $sites
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionSite()
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        return $this->asJson([
            'site' => OhDear::$plugin->api->getSite()
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionUptime()
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $request = \Craft::$app->request;
        $startedAt = $request->getRequiredQueryParam('startedAt');
        $endedAt = $request->getRequiredQueryParam('endedAt');
        $split = $request->getQueryParam('split', null);

        return $this->asJson([
            'uptime' => OhDear::$plugin->api->getUptime($startedAt, $endedAt, $split)
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionPaddedUptime()
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $request = \Craft::$app->request;
        $startedAt = $request->getRequiredQueryParam('startedAt');
        $endedAt = $request->getRequiredQueryParam('endedAt');
        $split = $request->getQueryParam('split', null);

        return $this->asJson([
            'uptime' => OhDear::$plugin->api->leftPadUptimeToMonday(
                OhDear::$plugin->api->getUptime($startedAt, $endedAt, $split)
            )
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionDowntime()
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $request = \Craft::$app->request;
        $startedAt = $request->getRequiredQueryParam('startedAt');
        $endedAt = $request->getRequiredQueryParam('endedAt');

        return $this->asJson([
            'downtime' => OhDear::$plugin->api->getDowntime($startedAt, $endedAt)
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionBrokenLinks()
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        return $this->asJson([
            'brokenLinks' => OhDear::$plugin->api->getBrokenLinks()
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionMixedContent()
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        return $this->asJson([
            'mixedContentItems' => OhDear::$plugin->api->getMixedContent()
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionCertificateHealth()
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        return $this->asJson([
            'certificateHealth' => OhDear::$plugin->api->getCertificateHealth()
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionApplicationHealthChecks()
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        return $this->asJson([
            'applicationHealthChecks' => OhDear::$plugin->api->getApplicationHealthChecks()
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionApplicationHealthCheckResults()
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $checkId = intval(
            \Craft::$app->request->getRequiredQueryParam('checkId')
        );

        return $this->asJson([
            'applicationHealthCheckResults' => OhDear::$plugin->api->getApplicationHealthCheckResults($checkId)
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionCronChecks()
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        return $this->asJson([
            'cronChecks' => OhDear::$plugin->api->getCronChecks()
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionCurrentPerformance()
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        return $this->asJson([
            'currentPerformance' => OhDear::$plugin->api->getCurrentPerformance()
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionPerformance()
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $request = \Craft::$app->request;
        $start = $request->getRequiredQueryParam('start');
        $end = $request->getRequiredQueryParam('end');
        $groupBy = $request->getQueryParam('groupBy');

        return $this->asJson([
            'performance' => OhDear::$plugin->api->getPerformance($start, $end, $groupBy),
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionDisableCheck()
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $checkId = intval(
            \Craft::$app->request->getRequiredBodyParam('checkId')
        );

        return $this->asJson([
            'check' => OhDear::$plugin->api->disableCheck($checkId)
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionEnableCheck()
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $checkId = intval(
            \Craft::$app->request->getRequiredBodyParam('checkId')
        );

        return $this->asJson([
            'check' => OhDear::$plugin->api->enableCheck($checkId)
        ]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionRequestRun()
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $checkId = intval(
            \Craft::$app->request->getRequiredBodyParam('checkId')
        );

        try {
            return $this->asJson([
                'check' => OhDear::$plugin->api->requestRun($checkId)
            ]);
        } catch (\Exception $e) {
            $error = json_decode($e->getMessage(), true);
            return $this->asErrorJson($error['message'] ?? 'An error occurred');
        }
    }
}
