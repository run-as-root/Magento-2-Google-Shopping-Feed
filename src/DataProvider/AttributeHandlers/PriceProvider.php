<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use RunAsRoot\GoogleShoppingFeed\DataProvider\CurrencyAmountProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ProductPricesProvider;

class PriceProvider implements AttributeHandlerInterface
{
    private CurrencyAmountProvider $currencyAmountProvider;
    private ProductPricesProvider $productPricesProvider;

    public function __construct(
        CurrencyAmountProvider $currencyAmountProvider,
        ProductPricesProvider $productPricesProvider
    ) {
        $this->currencyAmountProvider = $currencyAmountProvider;
        $this->productPricesProvider = $productPricesProvider;
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function get(Product $product): string
    {
        return $this->currencyAmountProvider->get(
            $this->productPricesProvider->get($product)->getRegularPrice(),
            (int)$product->getStoreId()
        );
    }
}
