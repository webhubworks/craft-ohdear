<?php
/**
 * Oh Dear plugin for Craft CMS 3.x
 *
 * Integrate Oh Dear into Craft CMS.
 *
 * @link      https://webhub.de
 * @copyright Copyright (c) 2019 webhub GmbH
 */

namespace webhubworks\ohdear\assetbundles\ohdear;

use Craft;
use craft\helpers\Json;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use Yii;
use yii\web\View as ViewAlias;

/**
 * OhDearAsset AssetBundle
 *
 * AssetBundle represents a collection of asset files, such as CSS, JS, images.
 *
 * Each asset bundle has a unique name that globally identifies it among all asset bundles used in an application.
 * The name is the [fully qualified class name](http://php.net/manual/en/language.namespaces.rules.php)
 * of the class representing it.
 *
 * An asset bundle can depend on other asset bundles. When registering an asset bundle
 * with a view, all its dependent asset bundles will be automatically registered.
 *
 * http://www.yiiframework.com/doc-2.0/guide-structure-assets.html
 *
 * @author    webhub GmbH
 * @package   OhDear
 * @since     1.0.0
 */
class OhDearAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * Initializes the bundle.
     */
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = "@webhubworks/ohdear/assetbundles/ohdear/dist";

        static::registerLangFile();

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'js/OhDear.js',
        ];

        $this->css = [
            'css/OhDear.css',
        ];

        parent::init();
    }

    // TODO: Check if file exists
    // TODO: Check if JS object exists
    public static function registerLangFile()
    {
        $currentLanguage = Craft::$app->language;

        $path = rtrim(Yii::getAlias("@webhubworks/ohdear/translations/{$currentLanguage}/ohdear.php"));

        $craftJson = Json::encode(require $path, JSON_UNESCAPED_UNICODE);

        $js = <<<JS
window.Craft.translations.ohdear = $craftJson;
JS;
        Craft::$app->view->registerJs($js, ViewAlias::POS_BEGIN);
    }
}
