<?php

namespace thepixelage\discountsplus\records;

use craft\db\ActiveRecord;
use thepixelage\discountsplus\db\Table;

/**
 * @property int $id
 * @property int $limitDiscountsQuantity
 * @property string $customPerItemDiscountBehavior
 */
class Discount extends ActiveRecord
{
    public const DISCOUNT_EVERY_N_BEHAVIOR = 'discountEveryNItems';
    public const LIMIT_DISCOUNT_MULTIPLE_BY_N_BEHAVIOR = 'limitDiscountMultipleByN';

    public static function tableName(): string
    {
        return Table::DISCOUNTS;
    }
}
