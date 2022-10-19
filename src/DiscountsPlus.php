<?php
/**
 * Discounts Plus plugin for Craft CMS 4.x
 *
 * .
 *
 * @link      thepixelage.com
 * @copyright Copyright (c) 2022 thepixelage
 */

namespace thepixelage\discountsplus;

use craft\commerce\events\DiscountEvent;
use craft\commerce\models\Discount;
use craft\commerce\services\Discounts;
use craft\events\DefineBehaviorsEvent;
use craft\i18n\PhpMessageSource;
use thepixelage\discountsplus\records\Discount as DiscountRecord;
use thepixelage\discountsplus\behaviours\DiscountBehavior;
use thepixelage\discountsplus\services\Discounts as DiscountsPlusServiceService;

use Craft;
use craft\base\Plugin;
use thepixelage\discountsplus\base\Services;
use craft\events\PluginEvent;

use yii\base\Event;
use yii\db\Exception;

/**
 * Class DiscountsPlus
 *
 * @author    thepixelage
 * @package   DiscountsPlus
 * @since     4.0.0
 *
 * @property  DiscountsPlusServiceService $discountsPlusService
 */
class DiscountsPlus extends Plugin
{
    use Services;
    // Static Properties
    // =========================================================================

    /**
     * @var DiscountsPlus
     */
    public static DiscountsPlus $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $schemaVersion = '4.0.0';

    /**
     * @var bool
     */
    public bool $hasCpSettings = false;

    /**
     * @var bool
     */
    public bool $hasCpSection = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;


        $this->_registerTranslations();
        $this->_hookOnDiscountEditPage();
        $this->_registerOnDiscountBehavior();
        $this->_registerOnSaveDiscount();
    }

    private function _registerTranslations(): void
    {
        Craft::$app->i18n->translations['discounts-plus'] = [
            'class' => PhpMessageSource::class,
            'sourceLanguage' => 'en',
            'basePath' => __DIR__ . '/translations',
            'allowOverrides' => true,
        ];
    }
    private function _hookOnDiscountEditPage(): void
    {
        Craft::$app->view->hook('cp.commerce.discounts.edit', function(array &$context) {
            $context['tabs'][] = [
                'label' => 'Advanced Discounts',
                'url' => '#advanced-discounts'
            ];
        });
        Craft::$app->view->hook('cp.commerce.discounts.edit.content', function(array &$context) {
            return Craft::$app->view->renderTemplate('discounts-plus/_components/discount', [
                'discount' => $context['discount']
            ]);
        });
    }

    private function _registerOnDiscountBehavior(): void
    {
        Event::on(
            Discount::class,
            Discount::EVENT_DEFINE_BEHAVIORS,
            static function (DefineBehaviorsEvent $event) {
                $event->sender->attachBehaviors([
                    DiscountBehavior::class,
                ]);
            }
        );
    }

    private function _registerOnSaveDiscount(): void
    {
        Event::on(
            Discounts::class,
            Discounts::EVENT_AFTER_SAVE_DISCOUNT,
            static function(DiscountEvent $event) {
                $request = Craft::$app->request;
                if ($request->isConsoleRequest) {
                    return;
                }
                if (!$request->isCpRequest) {
                    return;
                }
                $isLimitPerItemDiscountsMultiples = (boolean)$request->getBodyParam('isLimitPerItemDiscountsMultiples');
                $limitDiscountsQuantity = abs((int)$request->getBodyParam('limitDiscountsQuantity'));

                $discount = DiscountsPlus::getInstance()?->getDiscounts()->saveDiscounts($event->discount, $isLimitPerItemDiscountsMultiples, $limitDiscountsQuantity);
                $event->discount = $discount;
            }
        );
    }


}
