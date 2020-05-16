<?php
/**
 * Oh Dear plugin for Craft CMS 3.x
 *
 * Integrate Oh Dear into Craft CMS.
 *
 * @link      https://webhub.de
 * @copyright Copyright (c) 2019 webhub GmbH
 */

namespace webhub\ohdear;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\elements\User;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\TemplateEvent;
use craft\helpers\Html;
use craft\services\Dashboard;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\View;
use Spatie\Url\Url;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use webhub\ohdear\models\Settings;
use webhub\ohdear\services\OhDearService as OhDearServiceService;
use webhub\ohdear\services\SettingsService;
use webhub\ohdear\widgets\OhDearWidget;
use yii\base\Event;
use yii\base\Exception;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    webhub GmbH
 * @package   OhDear
 * @since     1.0.0
 *
 * @property  OhDearServiceService $ohDearService
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class OhDear extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * OhDear::$plugin
     *
     * @var OhDear
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

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

    // Public Methods
    // =========================================================================

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
            $this->controllerNamespace = 'webhub\\ohdear\\console\\controllers';
        }

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

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return Model|null
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
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'oh-dear/settings',
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
                    'oh-dear:plugin-settings' => [
                        'label' => 'Manage plugin settings',
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
        return Craft::getAlias('@vendor/webhub/oh-dear/src/resources/icons/oh-dear.svg');
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

        if (!$currentUser->can('accessPlugin-oh-dear')) {
            return $cpNavItem;
        }

        $cpNavItem['subnav'] = [
            'overview' => [
                'url' => 'oh-dear/overview',
                'label' => 'Overview',
            ],
            'uptime' => [
                'url' => 'oh-dear/uptime',
                'label' => 'Uptime',
            ],
            'broken-links' => [
                'url' => 'oh-dear/broken-links',
                'label' => 'Broken Links',
            ],
            'mixed-content' => [
                'url' => 'oh-dear/mixed-content',
                'label' => 'Mixed Content',
            ],
            'certificate-health' => [
                'url' => 'oh-dear/certificate-health',
                'label' => 'Certificate Health',
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
                $event->rules['oh-dear'] = ['template' => 'oh-dear/overview'];
                $event->rules['oh-dear/overview'] = ['template' => 'oh-dear/overview'];
                $event->rules['oh-dear/uptime'] = ['template' => 'oh-dear/uptime'];
                $event->rules['oh-dear/broken-links'] = ['template' => 'oh-dear/broken-links'];
                $event->rules['oh-dear/mixed-content'] = ['template' => 'oh-dear/mixed-content'];
                $event->rules['oh-dear/certificate-health'] = ['template' => 'oh-dear/certificate-health'];
            }
        );
    }

    private function registerUrlRules()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['oh-dear/api/sites'] = 'oh-dear/api/sites';
                $event->rules['oh-dear/api/site'] = 'oh-dear/api/site';
                $event->rules['oh-dear/api/uptime'] = 'oh-dear/api/uptime';
                $event->rules['oh-dear/api/downtime'] = 'oh-dear/api/downtime';
                $event->rules['oh-dear/api/broken-links'] = 'oh-dear/api/broken-links';
                $event->rules['oh-dear/api/mixed-content'] = 'oh-dear/api/mixed-content';
                $event->rules['oh-dear/api/certificate-health'] = 'oh-dear/api/certificate-health';
                $event->rules['oh-dear/api/disable-check'] = 'oh-dear/api/disable-check';
                $event->rules['oh-dear/api/enable-check'] = 'oh-dear/api/enable-check';
                $event->rules['oh-dear/api/request-run'] = 'oh-dear/api/request-run';
            }
        );
    }

    private function registerEntryEditRedirectOverride()
    {
        Event::on(View::class,
            View::EVENT_AFTER_RENDER_PAGE_TEMPLATE,
            function (TemplateEvent $event) {
                if ($event->template === 'entries/_edit') {
                    $referrerUrl = Url::fromString(Craft::$app->getRequest()->getReferrer());
                    if ($referrerUrl->getPath() === '/admin/oh-dear/broken-links') {
                        $event->output = preg_replace('/<input type="hidden" name="redirect" value=".*">/', Html::redirectInput('/admin/oh-dear/broken-links'), $event->output);
                    }
                    if ($referrerUrl->getPath() === '/admin/oh-dear/mixed-content') {
                        $event->output = preg_replace('/<input type="hidden" name="redirect" value=".*">/', Html::redirectInput('/admin/oh-dear/mixed-content'), $event->output);
                    }
                }
            }
        );
    }
}
