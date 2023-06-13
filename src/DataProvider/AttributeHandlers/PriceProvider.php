<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use RunAsRoot\GoogleShoppingFeed\DataProvider\CurrencyAmountProvider;

class PriceProvider implements AttributeHandlerInterface
{
    private CurrencyAmountProvider $currencyAmountProvider;

    public function __construct(CurrencyAmountProvider $currencyAmountProvider)
    {
        $this->currencyAmountProvider = $currencyAmountProvider;
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function get(Product $product): string
    {
        return $this->currencyAmountProvider->get(
            (float)$product->getFinalPrice(),
            (int)$product->getStoreId()
        );
    }
}
