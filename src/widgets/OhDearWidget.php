<?php
/**
 * Oh Dear plugin for Craft CMS 3.x
 *
 * Integrate Oh Dear into Craft CMS.
 *
 * @link      https://webhub.de
 * @copyright Copyright (c) 2019 webhub GmbH
 */

namespace webhubworks\ohdear\widgets;

use Craft;
use craft\base\Widget;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;

/**
 * Oh Dear Widget
 *
 * Dashboard widgets allow you to display information in the Admin CP Dashboard.
 * Adding new types of widgets to the dashboard couldn’t be easier in Craft
 *
 * https://craftcms.com/docs/plugins/widgets
 *
 * @author    webhub GmbH
 * @package   OhDear
 * @since     1.0.0
 *
 * @property mixed $bodyHtml
 * @property mixed $settingsHtml
 */
class OhDearWidget extends Widget
{
    /**
     * @var string
     */
    public $period = 'month';

    /**
     * @var
     */
    public $checks;

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return 'Oh Dear';
    }

    /**
     * @inheritDoc
     */
    public static function maxColspan()
    {
        return 2;
    }

    /**
     * @inheritDoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules = array_merge(
            $rules,
            [
                ['period', 'string'],
                ['period', 'in', 'range' => ['hour', 'day', 'month']]
            ]
        );
        return $rules;
    }

    /**
     * @inheritDoc
     * @return string
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function getSettingsHtml()
    {

        return Craft::$app->getView()->renderTemplate(
            'ohdear/_components/widgets/OhDearWidget_settings',
            [
                'widget' => $this
            ]
        );
    }

    /**
     * @inheritDoc
     * @return string
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function getBodyHtml()
    {
//        Craft::$app->getView()->registerAssetBundle(OhDearWidgetAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'ohdear/_components/widgets/OhDearWidget_body',
            [
                'period' => $this->period,
                'checks' => $this->checks
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public static function iconPath()
    {
        return Craft::getAlias("@webhubworks/ohdear/assetbundles/ohdear/dist/img/OhDearWidget-icon.svg");
    }
}
