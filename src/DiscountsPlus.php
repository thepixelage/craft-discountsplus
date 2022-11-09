<?php
/**
 * Discounts Plus plugin for Craft CMS 4.x
 *
 * .
 *
 * @copyright Copyright (c) 2022 ThePixelAge
 */

namespace thepixelage\discountsplus;

use Craft;
use craft\base\Plugin;
use craft\commerce\adjusters\Discount as DiscountAdjuster;
use craft\commerce\events\DiscountAdjustmentsEvent;
use craft\commerce\events\DiscountEvent;
use craft\commerce\models\Discount;
use craft\commerce\services\Discounts;
use craft\events\DefineBehaviorsEvent;
use craft\events\DefineRulesEvent;
use craft\i18n\PhpMessageSource;
use thepixelage\discountsplus\base\Services;
use thepixelage\discountsplus\behaviours\DiscountBehavior;
use thepixelage\discountsplus\records\Discount as DiscountRecord;
use thepixelage\discountsplus\services\Discounts as DiscountsPlusServiceService;
use yii\base\Event;

/**
 * Class DiscountsPlus
 *
 * @author    ThePixelAge
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


        $this->_setComponents();
        $this->_registerTranslations();
        $this->_hookOnDiscountEditPage();
        $this->_registerOnDiscountBehavior();
        $this->_registerOnBeforeSaveDiscount();
        $this->_registerOnDiscountDefineRules();
        $this->_registerOnAfterSaveDiscount();
        $this->_registerAfterDiscountAdjustmentsCreated();
        $this->_registerOnDeleteDiscount();
    }

    private function _registerTranslations(): void
    {
        Craft::$app->i18n->translations['discountsplus'] = [
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
                'label' => 'Discounts Plus',
                'url' => '#discounts-plus'
            ];
        });
        Craft::$app->view->hook('cp.commerce.discounts.edit.content', function(array $context) {
            $perItemDiscountCustomBehaviors = [
                DiscountRecord::DISCOUNT_DEFAULT_BEHAVIOR => Craft::t('discountsplus','Apply discount amount to each item'),
                DiscountRecord::DISCOUNT_BEHAVIOR_EACH_ITEMS_IN_N_STEPS => Craft::t('discountsplus','Apply discount amount to each item in steps of N items'),
                DiscountRecord::DISCOUNT_BEHAVIOR_EVERY_NTH => Craft::t('discountsplus','Apply discount amount to every Nth item only'),

            ];
            return Craft::$app->view->renderTemplate('discountsplus/_components/discount', [
                'perItemDiscountCustomBehaviors' => $perItemDiscountCustomBehaviors,
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

    private function _registerOnBeforeSaveDiscount(): void
    {
        Event::on(
            Discounts::class,
            Discounts::EVENT_BEFORE_SAVE_DISCOUNT,
            static function(DiscountEvent $event) {
                $request = Craft::$app->request;
                if ($request->isConsoleRequest) {
                    return;
                }
                if (!$request->isCpRequest) {
                    return;
                }
                $customPerItemDiscountBehavior = $request->getBodyParam('customPerItemDiscountBehavior');
                $limitDiscountsQuantity = abs((int)$request->getBodyParam('limitDiscountsQuantity'));

                /** @var Discount|DiscountBehavior $discount */
                $discount = $event->discount;
                $discount->setCustomPerItemDiscountBehavior($customPerItemDiscountBehavior);
                $discount->setLimitDiscountsQuantity($limitDiscountsQuantity);
                $event->discount = $discount;

            }
        );
    }

    public function _registerOnDiscountDefineRules(): void
    {
        Event::on(
            Discount::class,
            Discount::EVENT_DEFINE_RULES,
            static function(DefineRulesEvent $event) {
                /** @var Discount|DiscountBehavior $discount */
                $discount = $event->sender;
                if ($discount->customPerItemDiscountBehavior === DiscountRecord::DISCOUNT_BEHAVIOR_EACH_ITEMS_IN_N_STEPS || $discount->customPerItemDiscountBehavior === DiscountRecord::DISCOUNT_BEHAVIOR_EVERY_NTH) {
                    $event->rules[] = [
                        'purchaseQty', 'compare', 'operator'=>'>', 'compareValue'=>0, 'message' => Craft::t('discountsplus', 'Purchase Qty cannot be zero for non default Discounts Plus custom behavior')
                    ];
                    $event->rules[] = [
                        'customPerItemDiscountBehavior', 'in', 'range' => [
                            DiscountRecord::DISCOUNT_DEFAULT_BEHAVIOR,
                            DiscountRecord::DISCOUNT_BEHAVIOR_EACH_ITEMS_IN_N_STEPS,
                            DiscountRecord::DISCOUNT_BEHAVIOR_EVERY_NTH,
                        ]
                    ];
                }

            }
        );
    }

    private function _registerOnAfterSaveDiscount(): void
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
                $discount = DiscountsPlus::getInstance()?->getDiscounts()->saveDiscount($event->discount);
                $event->discount = $discount;
            }
        );
    }

    private function _registerOnDeleteDiscount(): void
    {
          Event::on(
              Discounts::class,
              Discounts::EVENT_AFTER_DELETE_DISCOUNT,
              static function(DiscountEvent $event) {
                    // @var Discount $discount
                    $discount = $event->discount;
                    DiscountsPlus::getInstance()->discounts->deleteDiscount($discount);
              }
          );
    }

    private function _registerAfterDiscountAdjustmentsCreated(): void
    {
        Event::on(
            DiscountAdjuster::class,
            DiscountAdjuster::EVENT_AFTER_DISCOUNT_ADJUSTMENTS_CREATED,
            static function(DiscountAdjustmentsEvent $event) {
                $order = $event->order;

                /** @var Discount|DiscountBehavior $discount */
                $discount = $event->discount;

                $adjustments = $event->adjustments;
                $newAdjustments = DiscountsPlus::getInstance()->discounts->recalculateDiscounts($order, $discount, $adjustments);
                $event->adjustments = $newAdjustments;
            });
    }

}
