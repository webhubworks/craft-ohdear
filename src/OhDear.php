<?php
/**
 * Oh Dear plugin for Craft CMS 4.x
 *
 * Integrate Oh Dear into Craft CMS.
 *
 * @link      https://webhub.de
 * @copyright Copyright (c) 2019 webhub GmbH
 */

namespace webhubworks\ohdear;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\elements\User;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\TemplateEvent;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\services\Dashboard;
use craft\services\UserPermissions;
use craft\services\Utilities;
use craft\web\UrlManager;
use craft\web\View;
use Spatie\Url\Url;
use webhubworks\ohdear\assetbundles\ohdear\OhDearAsset;
use webhubworks\ohdear\models\Settings;
use webhubworks\ohdear\plugin\CpNavItem;
use webhubworks\ohdear\plugin\CpRoutes;
use webhubworks\ohdear\plugin\Permissions;
use webhubworks\ohdear\services\BadgeCountService;
use webhubworks\ohdear\services\HealthCheckService;
use webhubworks\ohdear\services\OhDearService;
use webhubworks\ohdear\services\SettingsService;
use webhubworks\ohdear\utilities\HealthCheckUtility;
use webhubworks\ohdear\widgets\OhDearWidget;
use yii\base\Event;
use yii\web\View as ViewAlias;

/**
 * @author    webhub GmbH
 * @package   OhDear
 * @since     1.0.0
 *
 * @property HealthCheckService $health
 * @property SettingsService $settingsService
 * @property OhDearService $api
 * @property BadgeCountService $badgeCount
 * @property mixed $cpNavItem
 * @property Settings $settings
 * @method Settings getSettings()
 */
class OhDear extends Plugin
{
    const HEALTH_REPORT_URI = 'ohdear/api/health-check-results';

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * OhDear::$plugin
     */
    public static OhDear $plugin;

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     */
    public string $schemaVersion = '1.0.0';

    public bool $hasCpSettings = true;

    public bool $hasCpSection = true;

    public bool $isPreCraft34 = false;

    public static ?Settings $settings = null;

    public function init(): void
    {
        // Set the controllerNamespace based on whether this is a console request
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'webhubworks\\ohdear\\console\\controllers';
        }

        $this->isPreCraft34 = version_compare(Craft::$app->getVersion(), '3.4', '<');

        parent::init();
        self::$plugin = $this;
        $this->name = self::$plugin->getSettings()->pluginName;

        $this->setComponents([
            'settingsService' => SettingsService::class,
            'api' => OhDearService::class,
            'health' => HealthCheckService::class,
            'badgeCount' => BadgeCountService::class,
        ]);

        $this->registerPermissions();
        $this->registerUtilityTypes();
        $this->registerWidgets();
        $this->registerUrlRules();
        $this->registerCpRoutes();
        $this->registerEntryEditRedirectOverride();

        Craft::$app->onInit(function () {
            if (Craft::$app->request->isCpRequest && Craft::$app->user->getIdentity()) {
                $this->registerFrontendPermissions();
                OhDearAsset::registerLangFile();
            }
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate(
            'ohdear/settings',
            [
                'settings' => $this->getSettings(),
                'healthReportUrl' => $this->getSettings()->getHealthReportUrl('actions/ohdear/health-check/results'),
            ]
        );
    }

    private function registerPermissions(): void
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                Permissions::handle($event);
            }
        );
    }

    /**
     * @inheritdoc
     */
    protected function cpNavIconPath(): ?string
    {
        return Craft::getAlias('@vendor/webhubworks/craft-ohdear/src/resources/icons/ohdear.svg');
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): ?array
    {
        $cpNavItem = parent::getCpNavItem();

        return CpNavItem::get($cpNavItem, $this->settings, $this->badgeCount);
    }

    private function registerWidgets(): void
    {
        if (! $this->settings->hasApiCredentials()) {
            return;
        }

        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = OhDearWidget::class;
            }
        );
    }

    private function registerUtilityTypes(): void
    {
        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function (RegisterComponentTypesEvent $event) {
                /** @var User|null $currentUser */
                $currentUser = Craft::$app->getUser()->getIdentity();

                if ($currentUser->can('ohdear:view-utility')) {
                    $event->types[] = HealthCheckUtility::class;
                }
            }
        );
    }

    private function registerCpRoutes(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                CpRoutes::handle($event);
            }
        );
    }

    private function registerUrlRules(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules[self::HEALTH_REPORT_URI] = 'ohdear/health-check/results';
            }
        );
    }

    private function registerFrontendPermissions(): void
    {
        $js = <<<JS
window.OhDear = window.OhDear || {};
window.OhDear.permissions = window.OhDear.permissions || {};
JS;

        /** @var User|null $currentUser */
        $currentUser = Craft::$app->getUser()->getIdentity();

        $checkPermissions = is_null($currentUser) ? null : [
            'uptime' => [
                'view' => $currentUser->can('ohdear:view-uptime'),
                'toggle' => $currentUser->can('ohdear:toggle-uptime-check'),
                'request' => $currentUser->can('ohdear:request-uptime-check'),
            ],
            'broken_links' => [
                'view' => $currentUser->can('ohdear:view-broken-links'),
                'toggle' => $currentUser->can('ohdear:toggle-broken-links-check'),
                'request' => $currentUser->can('ohdear:request-broken-links-check'),
            ],
            'mixed_content' => [
                'view' => $currentUser->can('ohdear:view-mixed-content'),
                'toggle' => $currentUser->can('ohdear:toggle-mixed-content-check'),
                'request' => $currentUser->can('ohdear:request-mixed-content-check'),
            ],
            'certificate_health' => [
                'view' => $currentUser->can('ohdear:view-certificate-health'),
                'toggle' => $currentUser->can('ohdear:toggle-certificate-health-check'),
                'request' => $currentUser->can('ohdear:request-certificate-health-check'),
            ],
            'application_health' => [
                'view' => $currentUser->can('ohdear:view-application-health'),
                'toggle' => $currentUser->can('ohdear:toggle-application-health-check'),
                'request' => $currentUser->can('ohdear:request-application-health-check'),
            ],
            'performance' => [
                'view' => $currentUser->can('ohdear:view-performance'),
                'toggle' => $currentUser->can('ohdear:toggle-performance-check'),
                'request' => $currentUser->can('ohdear:request-performance-check'),
            ],
        ];

        $json = Json::encode($checkPermissions, JSON_UNESCAPED_UNICODE);

        $js .= <<<JS
window.OhDear.permissions = $json;
JS;

        Craft::$app->view->registerJs($js, ViewAlias::POS_BEGIN);
    }

    /**
     * Modifies element edit page redirect input if
     * - redirectInput helper exists
     * - request referrer is available
     * - request is coming from our plugin's broken link or
     *   mixed content pages
     */
    private function registerEntryEditRedirectOverride(): void
    {
        if (Craft::$app->getRequest()->getIsCpRequest() && method_exists(Html::class, 'redirectInput')) {
            if (is_string(Craft::$app->getRequest()->getReferrer())) {
                Event::on(View::class,
                    View::EVENT_AFTER_RENDER_PAGE_TEMPLATE,
                    function (TemplateEvent $event) {
                        $cpTrigger = Craft::$app->config->general->cpTrigger;

                        if ($event->template === 'entries/_edit') {
                            $referrerUrl = Url::fromString(Craft::$app->getRequest()->getReferrer());
                            if ($referrerUrl->getPath() === "/{$cpTrigger}/ohdear/broken-links") {
                                $event->output = preg_replace('/<input type="hidden" name="redirect" value=".*">/', Html::redirectInput("/{$cpTrigger}/ohdear/broken-links"), $event->output);
                            }
                            if ($referrerUrl->getPath() === "/{$cpTrigger}/ohdear/mixed-content") {
                                $event->output = preg_replace('/<input type="hidden" name="redirect" value=".*">/', Html::redirectInput("/{$cpTrigger}/ohdear/mixed-content"), $event->output);
                            }
                        }
                    }
                );
            }
        }
    }
}
