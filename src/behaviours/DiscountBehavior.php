<?php

namespace thepixelage\discountsplus\behaviours;


use craft\commerce\models\Discount as DiscountModel;
use thepixelage\discountsplus\records\Discount as DiscountPlusRecord;
use yii\base\Behavior;

/**
 *
 * @property bool $isLimitPerItemDiscountsMultiples
 * @property int $limitDiscountsQuantity
 */
class DiscountBehavior extends Behavior
{
    private bool $_isLimitPerItemDiscountsMultiples;
    private int $_limitDiscountsQuantity;
    
    public function getIsLimitPerItemDiscountsMultiples(): bool
    {
        /** @var DiscountModel $discount */
        $discount = $this->owner;
        if (isset($this->_isLimitPerItemDiscountsMultiples)) {
            return $this->_isLimitPerItemDiscountsMultiples;
        }

        if (!$discount->id) {
            return 0;
        }

        $record = DiscountPlusRecord::findOne($discount->id);
        if(!$record) {
            $this->_isLimitPerItemDiscountsMultiples = false;
            $this->_limitDiscountsQuantity = 0;
            return $this->_isLimitPerItemDiscountsMultiples;
        }

        $this->_limitDiscountsQuantity = $record->limitDiscountsQuantity ?: 0;
        $this->_isLimitPerItemDiscountsMultiples = $record->isLimitPerItemDiscountsMultiples;

        return $this->_isLimitPerItemDiscountsMultiples;
    }

    public function setIsLimitPerItemDiscountsMultiples($val): void
    {
        $this->_isLimitPerItemDiscountsMultiples = $val;
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
            $this->_isLimitPerItemDiscountsMultiples = false;
            $this->_limitDiscountsQuantity = 0;
            return $this->_limitDiscountsQuantity;
        }
        $this->_limitDiscountsQuantity = $record->limitDiscountsQuantity ?: 0;
        $this->_isLimitPerItemDiscountsMultiples = $record->isLimitPerItemDiscountsMultiples;

        return $this->_limitDiscountsQuantity;
    }

    public function setLimitDiscountsQuantity($val): void
    {
        $this->_limitDiscountsQuantity = $val;
    }
}