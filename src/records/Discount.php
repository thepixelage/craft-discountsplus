<?php

namespace thepixelage\discountsplus\records;

use craft\db\ActiveRecord;
use thepixelage\discountsplus\db\Table;

/**
 * @property int $id
 * @property int $limitDiscountsQuantity
 * @property boolean $isLimitPerItemDiscountsMultiples
 */
class Discount extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::DISCOUNTS;
    }
}
