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
    public const DISCOUNT_DEFAULT_BEHAVIOR = 'default';
    public const DISCOUNT_BEHAVIOR_EACH_ITEMS_IN_N_STEPS = 'discountEachItemInNSteps';
    public const DISCOUNT_BEHAVIOR_EVERY_NTH = 'discountEveryNth';

    public static function tableName(): string
    {
        return Table::DISCOUNTS;
    }
}
