<?php
/**
 * @link      https://webhub.de
 * @copyright Copyright (c) 2019 webhub GmbH
 */

namespace webhubworks\ohdear\controllers;

use Carbon\Carbon;
use Craft;
use craft\helpers\App;
use craft\web\Controller;
use OhDear\PhpSdk\Enums\UptimeMetricsSplit;
use OhDear\PhpSdk\Exceptions\NotFoundException;
use OhDear\PhpSdk\Exceptions\UnauthorizedException;
use webhubworks\ohdear\OhDear;
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
     * @throws BadRequestHttpException
     */
    public function actionSites(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $apiTokenParam = Craft::$app->request->getQueryParam('api-token', null);

        try {
            if ($apiTokenParam === null) {
                $sites = OhDear::$plugin->api->getMonitors();
            } else {
                $sites = OhDear::$plugin->settingsService->getSites(
                    App::parseEnv($apiTokenParam)
                );
            }

            return $this->asJson([
                'sites' => $sites,
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionSite(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        try {
            return $this->asJson([
                'site' => OhDear::$plugin->api->getMonitor(),
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionUptime(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $request = \Craft::$app->request;
        $startedAt = $request->getRequiredQueryParam('startedAt');
        $endedAt = $request->getRequiredQueryParam('endedAt');
        $split = $request->getQueryParam('split');

        try {
            return $this->asJson([
                'uptime' => OhDear::$plugin->api->getUptime(
                    Carbon::parse($startedAt),
                    Carbon::parse($endedAt),
                    UptimeMetricsSplit::tryFrom($split ?? "")
                ),
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionPaddedUptime(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $request = \Craft::$app->request;
        $startedAt = $request->getRequiredQueryParam('startedAt');
        $endedAt = $request->getRequiredQueryParam('endedAt');
        $split = $request->getQueryParam('split');

        try {
            return $this->asJson([
                'uptime' => OhDear::$plugin->api->leftPadUptimeToMonday(
                    OhDear::$plugin->api->getUptime(
                        Carbon::parse($startedAt),
                        Carbon::parse($endedAt),
                        UptimeMetricsSplit::tryFrom($split ?? "")
                    )
                ),
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionDowntime(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $request = \Craft::$app->request;
        $startedAt = $request->getRequiredQueryParam('startedAt');
        $endedAt = $request->getRequiredQueryParam('endedAt');

        try {
            return $this->asJson([
                'downtime' => OhDear::$plugin->api->getDowntime(
                    Carbon::parse($startedAt),
                    Carbon::parse($endedAt),
                ),
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionBrokenLinks(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        try {
            return $this->asJson([
                'brokenLinks' => OhDear::$plugin->api->getBrokenLinks(),
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionMixedContent(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        try {
            return $this->asJson([
                'mixedContentItems' => OhDear::$plugin->api->getMixedContent(),
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionCertificateHealth(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        try {
            return $this->asJson([
                'certificateHealth' => OhDear::$plugin->api->getCertificateHealth(),
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionApplicationHealthChecks(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        try {
            return $this->asJson([
                'applicationHealthChecks' => OhDear::$plugin->api->getApplicationHealthChecks(),
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionLatestLighthouseReport(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        try {
            return $this->asJson([
                'latestLighthouseReport' => OhDear::$plugin->api->getLatestLighthouseReport(),
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionApplicationHealthCheckResults(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $checkId = intval(
            \Craft::$app->request->getRequiredQueryParam('checkId')
        );

        try {
            return $this->asJson([
                'applicationHealthCheckResults' => OhDear::$plugin->api->getApplicationHealthCheckResults($checkId),
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionCronChecks(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        try {
            return $this->asJson([
                'cronChecks' => OhDear::$plugin->api->getCronChecks(),
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionCurrentPerformance(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        try {
            return $this->asJson([
                'currentPerformance' => OhDear::$plugin->api->getCurrentPerformance(),
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionPerformance(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $request = \Craft::$app->request;
        $start = $request->getRequiredQueryParam('start');
        $end = $request->getRequiredQueryParam('end');
        $groupBy = $request->getQueryParam('groupBy');

        try {
            return $this->asJson([
                'performance' => OhDear::$plugin->api->getPerformance(
                    Carbon::parse($start),
                    Carbon::parse($end),
                    UptimeMetricsSplit::tryFrom($groupBy ?? "")
                ),
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionDisableCheck(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $checkId = intval(
            \Craft::$app->request->getRequiredBodyParam('checkId')
        );

        try {
            return $this->asJson([
                'check' => OhDear::$plugin->api->disableCheck($checkId),
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionEnableCheck(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $checkId = intval(
            \Craft::$app->request->getRequiredBodyParam('checkId')
        );

        try {
            return $this->asJson([
                'check' => OhDear::$plugin->api->enableCheck($checkId),
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionRequestRun(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $checkId = intval(
            \Craft::$app->request->getRequiredBodyParam('checkId')
        );

        try {
            return $this->asJson([
                'check' => OhDear::$plugin->api->requestRun($checkId),
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    private function handleError($e): Response
    {
        return match (get_class($e)) {
            NotFoundException::class => $this->handleInvalidSiteIdError(),
            UnauthorizedException::class => $this->handleInvalidApiTokenError(),
            default => $this->handleGenericError($e),
        };

    }

    private function handleInvalidApiTokenError(): Response
    {
        $this->response->setStatusCode(403);
        return $this->asJson([
            'error' => 'The Oh Dear API token is invalid.',
        ]);
    }

    private function handleInvalidSiteIdError(): Response
    {
        $this->response->setStatusCode(404);
        return $this->asJson([
            'error' => 'The Oh Dear site could not be found. Please check your site ID.',
        ]);
    }

    private function handleGenericError(\Exception $e): Response
    {
        $this->response->setStatusCode(500);

        $json = json_decode($e->getMessage());

        if (is_object($json) && isset($json->message)) {
            return $this->asJson([
                'error' => $json->message,
            ]);
        }

        return $this->asJson([
            'error' => $e->getMessage(),
        ]);
    }
}
