<?php
/**
 * Oh Dear plugin for Craft CMS 4.x
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
 * @author    webhub GmbH
 * @package   OhDear
 * @since     1.0.0
 */
class OhDearAsset extends AssetBundle
{
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = "@webhubworks/ohdear/assetbundles/ohdear/dist";

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

    /**
     * Save plugin translations into the browser window object.
     *
     * @return void
     */
    public static function registerLangFile()
    {
        $currentLanguage = Craft::$app->language;

        $js = <<<JS
window.OhDear = window.OhDear || {};
window.OhDear.translations = window.OhDear.translations || {};
JS;

        $path = rtrim(Yii::getAlias("@webhubworks/ohdear/translations/{$currentLanguage}/ohdear.php"));

        if (file_exists($path)) {
            $craftJson = Json::encode(require $path, JSON_UNESCAPED_UNICODE);

            $js .= <<<JS
window.OhDear.translations = $craftJson;
JS;
        }

        Craft::$app->view->registerJs($js, ViewAlias::POS_BEGIN);
    }
}
