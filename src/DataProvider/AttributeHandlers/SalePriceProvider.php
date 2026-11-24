<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use RunAsRoot\GoogleShoppingFeed\DataProvider\CurrencyAmountProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ProductPricesProvider;

class SalePriceProvider implements AttributeHandlerInterface
{
    public function __construct(
        private readonly ProductPricesProvider $productPricesProvider,
        private readonly CurrencyAmountProvider $currencyAmountProvider
    ) {
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function get(Product $product): string
    {
        $prices = $this->productPricesProvider->get($product);

        if (round($prices->getFinalPrice(), 2) >= round($prices->getRegularPrice(), 2)) {
            return '';
        }

        return $this->currencyAmountProvider->get($prices->getFinalPrice(), (int)$product->getStoreId());
    }
}
