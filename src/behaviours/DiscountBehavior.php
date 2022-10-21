<?php

namespace thepixelage\discountsplus\behaviours;


use craft\commerce\models\Discount as DiscountModel;
use thepixelage\discountsplus\records\Discount as DiscountPlusRecord;
use yii\base\Behavior;

/**
 *
 * @property string $customPerItemDiscountBehavior
 * @property int $limitDiscountsQuantity
 */
class DiscountBehavior extends Behavior
{
    private ?string $_customPerItemDiscountBehavior;
    private int $_limitDiscountsQuantity;
    
    public function getCustomPerItemDiscountBehavior(): ?string
    {
        /** @var DiscountModel $discount */
        $discount = $this->owner;
        if (isset($this->_customPerItemDiscountBehavior)) {
            return $this->_customPerItemDiscountBehavior;
        }

        if (!$discount->id) {
            return null;
        }

        $record = DiscountPlusRecord::findOne($discount->id);
        if(!$record) {
            $this->_customPerItemDiscountBehavior = null;
            $this->_limitDiscountsQuantity = 0;
            return $this->_customPerItemDiscountBehavior;
        }

        $this->_limitDiscountsQuantity = $record->limitDiscountsQuantity ?: 0;
        $this->_customPerItemDiscountBehavior = $record->customPerItemDiscountBehavior;

        return $this->_customPerItemDiscountBehavior;
    }

    public function setCustomPerItemDiscountBehavior($val): void
    {
        $this->_customPerItemDiscountBehavior = $val;
    }


    public function getLimitDiscountsQuantity(): int
    {

        /** @var DiscountModel $discount */
        $discount = $this->owner;

        if (isset($this->_limitDiscountsQuantity)) {
            return $this->_limitDiscountsQuantity;
        }

        if (!$discount->id) {
            return 0;
        }


        $record = DiscountPlusRecord::findOne($discount->id);
        if(!$record) {
            $this->_customPerItemDiscountBehavior = false;
            $this->_limitDiscountsQuantity = 0;
            return $this->_limitDiscountsQuantity;
        }
        $this->_limitDiscountsQuantity = $record->limitDiscountsQuantity ?: 0;
        $this->_customPerItemDiscountBehavior = $record->customPerItemDiscountBehavior;

        return $this->_limitDiscountsQuantity;
    }

    public function setLimitDiscountsQuantity($val): void
    {
        $this->_limitDiscountsQuantity = $val;
    }
}