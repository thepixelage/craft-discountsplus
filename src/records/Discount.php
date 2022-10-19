<?php

namespace thepixelage\discountsplus\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property int $limitDiscountsQuantity
 * @property boolean $isLimitPerItemDiscountsMultiples
 */
class Discount extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%tpa_discounts_addition}}';
    }
}
