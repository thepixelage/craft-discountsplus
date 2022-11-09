<?php

namespace thepixelage\discountsplus\services;

use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\helpers\Currency;
use craft\commerce\models\Discount;
use craft\commerce\models\OrderAdjustment;
use craft\commerce\Plugin;
use craft\commerce\records\Discount as DiscountRecord;
use thepixelage\discountsplus\behaviours\DiscountBehavior;
use thepixelage\discountsplus\records\Discount as DiscountPlusRecord;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\db\StaleObjectException;

/**
 * @author    ThePixelAge
 * @package   DiscountsPlus
 * @since     4.0.0
 */

class Discounts extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * @throws InvalidConfigException
     * @throws \Exception
     */
    public function recalculateDiscounts(Order $order, Discount|DiscountBehavior $discount, $adjustments): array
    {
        if (!$discount->getLimitDiscountsQuantity() && $discount->getCustomPerItemDiscountBehavior() === DiscountPlusRecord::DISCOUNT_DEFAULT_BEHAVIOR) {
            return $adjustments;
        }

        if (!$discount->perItemDiscount && !$discount->percentDiscount) {
            return $adjustments;
        }

        if ($discount->appliedTo !== DiscountRecord::APPLIED_TO_MATCHING_LINE_ITEMS) {
            return $adjustments;
        }

        if (!$discount->purchaseQty && ($discount->getCustomPerItemDiscountBehavior() === DiscountPlusRecord::DISCOUNT_BEHAVIOR_EACH_ITEMS_IN_N_STEPS || $discount->getCustomPerItemDiscountBehavior() === DiscountPlusRecord::DISCOUNT_BEHAVIOR_EVERY_NTH )) {
            return $adjustments;
        }

        // get the discount total qty to get discounted

        $totalMatchingItemsOnOrder = 0;
        $discountUnitPricesByLineItem = [];
        foreach ($order->getLineItems() as $item) {
            $lineItemHashId = spl_object_hash($item);
            // Order is already a match to this discount, or we wouldn't get here.
            $lineItemDiscountAmount = $item->getDiscount();
            if ($lineItemDiscountAmount) {
                $discountedUnitPrice = $item->salePrice + Currency::round($lineItemDiscountAmount / $item->qty);
                $discountUnitPricesByLineItem[$lineItemHashId] = $discountedUnitPrice;
            }

            if (Plugin::getInstance()?->getDiscounts()->matchLineItem($item, $discount)) {
                $totalMatchingItemsOnOrder += ($item->qty);
            }
        }


        $totalQtyToDiscounted = $discount->getLimitDiscountsQuantity() ? min($totalMatchingItemsOnOrder, $discount->getLimitDiscountsQuantity()) : $totalMatchingItemsOnOrder;
        if ($discount->getCustomPerItemDiscountBehavior() === DiscountPlusRecord::DISCOUNT_BEHAVIOR_EACH_ITEMS_IN_N_STEPS) {
            $reminderOfTotalQtyToDiscounted = $totalQtyToDiscounted % $discount->purchaseQty;
            $totalQtyToDiscounted = $reminderOfTotalQtyToDiscounted === 0 ? $totalQtyToDiscounted : $totalQtyToDiscounted - $reminderOfTotalQtyToDiscounted;
        }elseif ($discount->getCustomPerItemDiscountBehavior() === DiscountPlusRecord::DISCOUNT_BEHAVIOR_EVERY_NTH) {
            $totalQtyToDiscounted = floor($totalQtyToDiscounted/$discount->purchaseQty);
        }

        $newAdjustments = [];
        $countDiscountItemQty = 0;
        $countItemQty  = 0;
        foreach ($adjustments as $adjustment) {
            /** @var OrderAdjustment $adjustment */
            if (!$adjustment->getLineItem()) {
                $newAdjustments[] = $adjustment;
                continue;
            }

            if ($countDiscountItemQty >= $totalQtyToDiscounted) {
                continue;
            }

            if ($discount->getCustomPerItemDiscountBehavior() !== DiscountPlusRecord::DISCOUNT_BEHAVIOR_EVERY_NTH) {
                $currentLineItemQtyToDiscount = (($adjustment->lineItem->qty + $countDiscountItemQty) > $totalQtyToDiscounted) ? ($totalQtyToDiscounted - $countDiscountItemQty) : ($adjustment->lineItem->qty);
            } else {
                $numberOfItemGetDiscountForCurrentLineItem = floor(($adjustment->lineItem->qty + $countItemQty)/$discount->purchaseQty) - $countDiscountItemQty;
                $currentLineItemQtyToDiscount = $numberOfItemGetDiscountForCurrentLineItem + $countDiscountItemQty > $totalQtyToDiscounted ? ($totalQtyToDiscounted - $countDiscountItemQty) : $numberOfItemGetDiscountForCurrentLineItem;
            }

            $countDiscountItemQty += $currentLineItemQtyToDiscount;
            $countItemQty += $adjustment->lineItem->qty;


            //do update the amount discount, do same as commerce how to do it.
            $lineItemHashId = spl_object_hash($adjustment->lineItem);
            $discountAmountPerItemPreDiscounts = 0;
            $amountPerItem = Currency::round($discount->perItemDiscount);

            if ($discount->percentageOffSubject === DiscountRecord::TYPE_ORIGINAL_SALEPRICE) {
                $discountAmountPerItemPreDiscounts = ($discount->percentDiscount * $adjustment->lineItem->salePrice);
            }

            $unitPrice = $discountUnitPricesByLineItem[$lineItemHashId] ?? $adjustment->lineItem->salePrice;

            $lineItemSubtotal = Currency::round($unitPrice * $currentLineItemQtyToDiscount);

            $unitPrice = max($unitPrice + $amountPerItem, 0);

            if ($unitPrice > 0) {
                if ($discount->percentageOffSubject === DiscountRecord::TYPE_ORIGINAL_SALEPRICE) {
                    $discountedUnitPrice = $unitPrice + $discountAmountPerItemPreDiscounts;
                } else {
                    $discountedUnitPrice = $unitPrice + ($discount->percentDiscount * $unitPrice);
                }

                $discountedSubtotal = Currency::round($discountedUnitPrice * $currentLineItemQtyToDiscount);
                $amountOfPercentDiscount = $discountedSubtotal - $lineItemSubtotal;
                $discountUnitPricesByLineItem[$lineItemHashId] = $discountedUnitPrice;
                $adjustment->amount = $amountOfPercentDiscount; //Adding already rounded
            } else {
                $adjustment->amount = -$lineItemSubtotal;
                $discountUnitPricesByLineItem[$lineItemHashId] = 0;
            }

            if ($adjustment->amount != 0) {
                $newAdjustments[] = $adjustment;
            }
        }

        return $newAdjustments;
    }

    /**
     * @throws Exception
     */
    public function saveDiscount(Discount|DiscountBehavior $discount, $customPerItemDiscountBehavior, $limitDiscountsQuantity): DiscountBehavior|Discount
    {
        if (!in_array($customPerItemDiscountBehavior, [
            DiscountPlusRecord::DISCOUNT_DEFAULT_BEHAVIOR,
            DiscountPlusRecord::DISCOUNT_BEHAVIOR_EACH_ITEMS_IN_N_STEPS,
            DiscountPlusRecord::DISCOUNT_BEHAVIOR_EVERY_NTH,
        ], true)) {
            throw new Exception('Wrong Custom Behavior value');
        }
        $record = DiscountPlusRecord::findOne($discount->id);
        if (!$record) {
            $record = new DiscountPlusRecord();
        }
        $record->id = $discount->id;
        $record->limitDiscountsQuantity = $limitDiscountsQuantity;
        $record->customPerItemDiscountBehavior = $customPerItemDiscountBehavior;
        if (!$record->save()) {
            throw new Exception('Failed to save discount');
        }


        $discount->setCustomPerItemDiscountBehavior($customPerItemDiscountBehavior);
        $discount->setLimitDiscountsQuantity($limitDiscountsQuantity);
        return $discount;
    }

    /**
     * @throws StaleObjectException
     */
    public function deleteDiscount(Discount $discount):bool
    {
        $record = DiscountPlusRecord::findOne($discount->id);
        if (!$record) {
            return false;
        }
        return $record->delete();
    }
}
