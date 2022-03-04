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

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    webhub GmbH
 * @package   OhDear
 * @since     1.0.0
 */
class OhDearWidgetAsset extends AssetBundle
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
            'js/OhDearWidget.js',
        ];

        $this->css = [
            'css/OhDearWidget.css',
        ];

        parent::init();
    }
}
