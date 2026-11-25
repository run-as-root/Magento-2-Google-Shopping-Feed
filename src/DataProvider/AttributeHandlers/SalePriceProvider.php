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
    private ProductPricesProvider $productPricesProvider;
    private CurrencyAmountProvider $currencyAmountProvider;

    public function __construct(
        ProductPricesProvider $productPricesProvider,
        CurrencyAmountProvider $currencyAmountProvider
    ) {
        $this->productPricesProvider = $productPricesProvider;
        $this->currencyAmountProvider = $currencyAmountProvider;
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function get(Product $product): string
    {
        $prices = $this->productPricesProvider->get($product);

        if ($prices->getFinalPrice() >= $prices->getRegularPrice()) {
            return '';
        }

        $storeId = (int)$product->getStoreId();
        $salePrice = $this->currencyAmountProvider->get($prices->getFinalPrice(), $storeId);
        $regularPrice = $this->currencyAmountProvider->get($prices->getRegularPrice(), $storeId);

        return $salePrice === $regularPrice ? '' : $salePrice;
    }
}
