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
use webhubworks\ohdear\models\Settings;
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
 * @property mixed $cpNavItem
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class OhDear extends Plugin
{
    const HEALTH_REPORT_URI = 'ohdear/api/health-check-results';

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * OhDear::$plugin
     *
     * @var OhDear
     */
    public static $plugin;

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    /**
     * @var bool Whether the plugin has a settings page in the control panel
     */
    public bool $hasCpSettings = true;

    /**
     * @var bool Whether the plugin has its own section in the control panel
     */
    public bool $hasCpSection = true;

    /**
     * @var bool Whether the Craft version has been facelifted.
     */
    public $isPreCraft34 = false;

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * OhDear::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        // Set the controllerNamespace based on whether this is a console request
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'webhubworks\\ohdear\\console\\controllers';
        }

        $this->isPreCraft34 = version_compare(Craft::$app->getVersion(), '3.4', '<');

        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'settingsService' => SettingsService::class,
            'api' => OhDearService::class,
            'health' => HealthCheckService::class,
        ]);

        $this->registerPermissions();

        $this->registerCheckPermissions();

        $this->registerUrlRules();

        $this->registerCpRoutes();

        $this->registerWidgets();

        $this->registerUtilityTypes();

        $this->registerPermissions();

        $this->registerEntryEditRedirectOverride();
    }

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return Settings
     */
    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
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

    private function registerPermissions()
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $event->permissions['Oh Dear'] = [
                    'heading' => 'Oh Dear',
                    'permissions' => [
                        'ohdear:plugin-settings' => [
                            'label' => Craft::t('ohdear', 'Manage plugin settings'),
                        ],
                        'ohdear:view-overview' => [
                            'label' => Craft::t('ohdear', 'View overview page'),
                        ],
                        'ohdear:view-uptime' => [
                            'label' => Craft::t('ohdear', 'View uptime'),
                            'nested' => [
                                'ohdear:toggle-uptime-check' => [
                                    'label' => Craft::t('ohdear', 'Toggle uptime check'),
                                ],
                                'ohdear:request-uptime-check' => [
                                    'label' => Craft::t('ohdear', 'Request uptime check'),
                                ],
                            ],
                        ],
                        'ohdear:view-broken-links' => [
                            'label' => Craft::t('ohdear', 'View broken links'),
                            'nested' => [
                                'ohdear:toggle-broken-links-check' => [
                                    'label' => Craft::t('ohdear', 'Toggle broken links check'),
                                ],
                                'ohdear:request-broken-links-check' => [
                                    'label' => Craft::t('ohdear', 'Request broken links check'),
                                ],
                            ],
                        ],
                        'ohdear:view-mixed-content' => [
                            'label' => Craft::t('ohdear', 'View mixed content'),
                            'nested' => [
                                'ohdear:toggle-mixed-content-check' => [
                                    'label' => Craft::t('ohdear', 'Toggle mixed content check'),
                                ],
                                'ohdear:request-mixed-content-check' => [
                                    'label' => Craft::t('ohdear', 'Request mixed content check'),
                                ],
                            ],
                        ],
                        'ohdear:view-certificate-health' => [
                            'label' => Craft::t('ohdear', 'View certificate health'),
                            'nested' => [
                                'ohdear:toggle-certificate-health-check' => [
                                    'label' => Craft::t('ohdear', 'Toggle certificate health check'),
                                ],
                                'ohdear:request-certificate-health-check' => [
                                    'label' => Craft::t('ohdear', 'Request certificate health check'),
                                ],
                            ],
                        ],
                        'ohdear:view-application-health' => [
                            'label' => Craft::t('ohdear', 'View application health'),
                            'nested' => [
                                'ohdear:toggle-application-health-check' => [
                                    'label' => Craft::t('ohdear', 'Toggle application health check'),
                                ],
                                'ohdear:request-application-health-check' => [
                                    'label' => Craft::t('ohdear', 'Request application health check'),
                                ],
                            ],
                        ],
                        'ohdear:view-performance' => [
                            'label' => Craft::t('ohdear', 'View performance'),
                            'nested' => [
                                'ohdear:toggle-performance-check' => [
                                    'label' => Craft::t('ohdear', 'Toggle performance check'),
                                ],
                                'ohdear:request-performance-check' => [
                                    'label' => Craft::t('ohdear', 'Request performance check'),
                                ],
                            ],
                        ],
                        'ohdear:view-utility' => [
                            'label' => Craft::t('ohdear', 'View application health utility'),
                        ]
                    ],
                ];
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

        /** @var User|null $currentUser */
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser === null) {
            return $cpNavItem;
        }

        if (! $currentUser->can('ohdear:view-overview')) {
            return null;
        }

        if (! $this->settings->isValid()) {
            return $cpNavItem;
        }

        if (! $currentUser->can('accessPlugin-ohdear')) {
            return $cpNavItem;
        }

        if ($currentUser->can('ohdear:view-overview')) {
            $cpNavItem['subnav']['overview'] = [
                'url' => 'ohdear/overview',
                'label' => Craft::t('ohdear', 'Overview'),
            ];
        }

        if ($currentUser->can('ohdear:view-uptime')) {
            $cpNavItem['subnav']['uptime'] = [
                'url' => 'ohdear/uptime',
                'label' => Craft::t('ohdear', 'Uptime'),
            ];
        }

        if ($currentUser->can('ohdear:view-broken-links')) {
            $cpNavItem['subnav']['broken-links'] = [
                'url' => 'ohdear/broken-links',
                'label' => Craft::t('ohdear', 'Broken Links'),
            ];
        }

        if ($currentUser->can('ohdear:view-mixed-content')) {
            $cpNavItem['subnav']['mixed-content'] = [
                'url' => 'ohdear/mixed-content',
                'label' => Craft::t('ohdear', 'Mixed Content'),
            ];
        }

        if ($currentUser->can('ohdear:view-certificate-health')) {
            $cpNavItem['subnav']['certificate-health'] = [
                'url' => 'ohdear/certificate-health',
                'label' => Craft::t('ohdear', 'Certificate Health'),
            ];
        }

        if ($currentUser->can('ohdear:view-application-health')) {
            $cpNavItem['subnav']['application-health'] = [
                'url' => 'ohdear/application-health',
                'label' => Craft::t('ohdear', 'Application Health'),
            ];
        }

        if ($currentUser->can('ohdear:view-performance')) {
            $cpNavItem['subnav']['performance'] = [
                'url' => 'ohdear/performance',
                'label' => Craft::t('ohdear', 'Performance'),
            ];
        }

        return $cpNavItem;
    }

    private function registerWidgets()
    {
        if ($this->settings->isValid()) {
            Event::on(
                Dashboard::class,
                Dashboard::EVENT_REGISTER_WIDGET_TYPES,
                function (RegisterComponentTypesEvent $event) {
                    $event->types[] = OhDearWidget::class;
                }
            );
        }
    }

    private function registerUtilityTypes()
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

    private function registerCpRoutes()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                /** @var User|null $currentUser */
                $currentUser = Craft::$app->getUser()->getIdentity();

                if (is_null($currentUser)) {
                    return;
                }

                if ($currentUser->can('ohdear:view-overview')) {
                    $event->rules['ohdear'] = ['template' => 'ohdear/overview'];
                    $event->rules['ohdear/overview'] = ['template' => 'ohdear/overview'];
                }
                if ($currentUser->can('ohdear:view-uptime')) {
                    $event->rules['ohdear/uptime'] = ['template' => 'ohdear/uptime'];
                }
                if ($currentUser->can('ohdear:view-broken-links')) {
                    $event->rules['ohdear/broken-links'] = ['template' => 'ohdear/broken-links'];
                }
                if ($currentUser->can('ohdear:view-mixed-content')) {
                    $event->rules['ohdear/mixed-content'] = ['template' => 'ohdear/mixed-content'];
                }
                if ($currentUser->can('ohdear:view-certificate-health')) {
                    $event->rules['ohdear/certificate-health'] = ['template' => 'ohdear/certificate-health'];
                }
                if ($currentUser->can('ohdear:view-application-health')) {
                    $event->rules['ohdear/application-health'] = ['template' => 'ohdear/application-health'];
                }
                if ($currentUser->can('ohdear:view-performance')) {
                    $event->rules['ohdear/performance'] = ['template' => 'ohdear/performance'];
                }
            }
        );
    }

    private function registerUrlRules()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules[self::HEALTH_REPORT_URI] = 'ohdear/health-check/results';
            }
        );
    }

    private function registerCheckPermissions()
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
    private function registerEntryEditRedirectOverride()
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
