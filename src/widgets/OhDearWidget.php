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
use webhubworks\ohdear\assetbundles\ohdear\OhDearAsset;
use yii\base\Exception;

/**
 * https://craftcms.com/docs/3.x/extend/widget-types.html
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
    public string $period = 'month';

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
    public static function maxColspan(): ?int
    {
        return 2;
    }

    /**
     * @inheritDoc
     */
    public function rules(): array
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
    public function getSettingsHtml(): ?string
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
    public function getBodyHtml(): ?string
    {
        OhDearAsset::registerLangFile();

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
    public static function iconPath(): false|string
    {
        return Craft::getAlias("@webhubworks/ohdear/assetbundles/ohdear/dist/img/OhDearWidget-icon.svg");
    }
}
