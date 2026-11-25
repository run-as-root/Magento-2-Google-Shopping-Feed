<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Data;

class ProductPricesData
{
    private float $regularPrice;
    private float $finalPrice;

    public function __construct(float $regularPrice, float $finalPrice)
    {
        $this->regularPrice = $regularPrice;
        $this->finalPrice = $finalPrice;
    }

    public function getRegularPrice(): float
    {
        return $this->regularPrice;
    }

    public function getFinalPrice(): float
    {
        return $this->finalPrice;
    }
}
