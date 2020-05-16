<?php
/**
 * Oh Dear plugin for Craft CMS 3.x
 *
 * Integrate Oh Dear into Craft CMS.
 *
 * @link      https://webhub.de
 * @copyright Copyright (c) 2019 webhub GmbH
 */

namespace webhub\ohdear\controllers;

use Craft;
use craft\web\Controller;
use webhub\ohdear\OhDear;
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

        $apiToken = Craft::$app->request->getQueryParam('api-token', null);

        if ($apiToken === null) {
            $sites = OhDear::$plugin->ohDearService->getSites();
        } else {
            $sites = OhDear::$plugin->settingsService->getSites($apiToken);
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
            'site' => OhDear::$plugin->ohDearService->getSite()
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
        $startedAt = $request->getRequiredQueryParam('filter.started_at');
        $endedAt = $request->getRequiredQueryParam('filter.ended_at');
        $split = $request->getQueryParam('split', null);

        return $this->asJson([
            'uptime' => OhDear::$plugin->ohDearService->getUptime($startedAt, $endedAt, $split)
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
        $startedAt = $request->getRequiredQueryParam('filter.started_at');
        $endedAt = $request->getRequiredQueryParam('filter.ended_at');

        return $this->asJson([
            'downtime' => OhDear::$plugin->ohDearService->getDowntime($startedAt, $endedAt)
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
            'brokenLinks' => OhDear::$plugin->ohDearService->getBrokenLinks()
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
            'mixedContentItems' => OhDear::$plugin->ohDearService->getMixedContent()
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
            'certificateHealth' => OhDear::$plugin->ohDearService->getCertificateHealth()
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
            'check' => OhDear::$plugin->ohDearService->disableCheck($checkId)
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
            'check' => OhDear::$plugin->ohDearService->enableCheck($checkId)
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

        return $this->asJson([
            'check' => OhDear::$plugin->ohDearService->requestRun($checkId)
        ]);
    }
}
