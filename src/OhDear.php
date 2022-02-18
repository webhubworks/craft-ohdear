<?php
/**
 * Oh Dear plugin for Craft CMS 3.x
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
use craft\i18n\PhpMessageSource;
use craft\services\Dashboard;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\View;
use Spatie\Url\Url;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use webhubworks\ohdear\models\Settings;
use webhubworks\ohdear\services\OhDearService as OhDearServiceService;
use webhubworks\ohdear\services\SettingsService;
use webhubworks\ohdear\widgets\OhDearWidget;
use yii\base\Event;
use yii\base\Exception;

/**
 * @author    webhub GmbH
 * @package   OhDear
 * @since     1.0.0
 *
 * @property  OhDearServiceService $ohDearService
 * @property mixed $cpNavItem
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class OhDear extends Plugin
{
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
    public $schemaVersion = '1.0.0';

    /**
     * @var bool Whether the plugin has a settings page in the control panel
     */
    public $hasCpSettings = true;

    /**
     * @var bool Whether the plugin has its own section in the control panel
     */
    public $hasCpSection = true;

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
            'settingsService' => SettingsService::class
        ]);

        $this->registerPermissions();

        $this->registerUrlRules();

        $this->registerCpRoutes();

        $this->registerWidgets();

        $this->registerPermissions();

        $this->registerEntryEditRedirectOverride();
    }

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return Settings
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'ohdear/settings',
            [
                'settings' => $this->getSettings()
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
                    'ohdear:plugin-settings' => [
                        'label' => Craft::t('ohdear', 'Manage plugin settings'),
                    ],
                ];
            }
        );
    }

    /**
     * @inheritdoc
     */
    protected function cpNavIconPath()
    {
        return Craft::getAlias('@vendor/webhubworks/craft-ohdear/src/resources/icons/ohdear.svg');
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem()
    {
        $cpNavItem = parent::getCpNavItem();

        /** @var User|null $currentUser */
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser === null) {
            return $cpNavItem;
        }

        if (!$this->settings->isValid()) {
            return $cpNavItem;
        }

        if (!$currentUser->can('accessPlugin-ohdear')) {
            return $cpNavItem;
        }

        $cpNavItem['subnav'] = [
            'overview' => [
                'url' => 'ohdear/overview',
                'label' => Craft::t('ohdear', 'Overview'),
            ],
            'uptime' => [
                'url' => 'ohdear/uptime',
                'label' => Craft::t('ohdear', 'Uptime'),
            ],
            'broken-links' => [
                'url' => 'ohdear/broken-links',
                'label' => Craft::t('ohdear', 'Broken Links'),
            ],
            'mixed-content' => [
                'url' => 'ohdear/mixed-content',
                'label' => Craft::t('ohdear', 'Mixed Content'),
            ],
            'certificate-health' => [
                'url' => 'ohdear/certificate-health',
                'label' => Craft::t('ohdear', 'Certificate Health'),
            ]
        ];

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

    private function registerCpRoutes()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['ohdear'] = ['template' => 'ohdear/overview'];
                $event->rules['ohdear/overview'] = ['template' => 'ohdear/overview'];
                $event->rules['ohdear/uptime'] = ['template' => 'ohdear/uptime'];
                $event->rules['ohdear/broken-links'] = ['template' => 'ohdear/broken-links'];
                $event->rules['ohdear/mixed-content'] = ['template' => 'ohdear/mixed-content'];
                $event->rules['ohdear/certificate-health'] = ['template' => 'ohdear/certificate-health'];
            }
        );
    }

    private function registerUrlRules()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['ohdear/api/sites'] = 'ohdear/api/sites';
                $event->rules['ohdear/api/site'] = 'ohdear/api/site';
                $event->rules['ohdear/api/uptime'] = 'ohdear/api/uptime';
                $event->rules['ohdear/api/downtime'] = 'ohdear/api/downtime';
                $event->rules['ohdear/api/broken-links'] = 'ohdear/api/broken-links';
                $event->rules['ohdear/api/mixed-content'] = 'ohdear/api/mixed-content';
                $event->rules['ohdear/api/certificate-health'] = 'ohdear/api/certificate-health';
                $event->rules['ohdear/api/disable-check'] = 'ohdear/api/disable-check';
                $event->rules['ohdear/api/enable-check'] = 'ohdear/api/enable-check';
                $event->rules['ohdear/api/request-run'] = 'ohdear/api/request-run';
            }
        );
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
