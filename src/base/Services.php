<?php

namespace thepixelage\discountsplus\base;
use thepixelage\discountsplus\services\Discounts;
use yii\base\InvalidConfigException;

/**
 * @property-read Discounts $discounts
 */
trait Services
{

    /**
     * @throws InvalidConfigException
     */
    public function getDiscounts(): Discounts
    {
        return $this->get('discounts');
    }

    private function _setComponents(): void
    {
        $this->setComponents([
            'discounts'           => Discounts::class
        ]);
    }
}