<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Data;

class ProductPricesData
{
    public function __construct(
        private readonly float $regularPrice,
        private readonly float $finalPrice
    ) {
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
