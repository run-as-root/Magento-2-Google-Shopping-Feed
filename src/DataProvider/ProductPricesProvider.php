<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Api\Data\GroupInterface;
use RunAsRoot\GoogleShoppingFeed\Data\ProductPricesData;

class ProductPricesProvider
{
    private const CUSTOMER_GROUP_ID = 'customer_group_id';

    public function get(Product $product): ProductPricesData
    {
        $typeInstance = $product->getTypeInstance();

        if ($typeInstance instanceof Configurable) {
            return $this->getLowestAvailableChildPrices($typeInstance, $product);
        }

        return $this->getOwnPrices($product);
    }

    private function getOwnPrices(Product $product): ProductPricesData
    {
        $product->setData(self::CUSTOMER_GROUP_ID, GroupInterface::NOT_LOGGED_IN_ID);

        return new ProductPricesData((float)$product->getPrice(), (float)$product->getFinalPrice());
    }

    private function getLowestAvailableChildPrices(Configurable $typeInstance, Product $product): ProductPricesData
    {
        $lowestPrices = null;

        foreach ($typeInstance->getUsedProducts($product) as $childProduct) {
            if (!$childProduct instanceof Product || !$this->isAvailable($childProduct)) {
                continue;
            }

            $childPrices = $this->getOwnPrices($childProduct);

            if ($lowestPrices !== null && $childPrices->getFinalPrice() >= $lowestPrices->getFinalPrice()) {
                continue;
            }

            $lowestPrices = $childPrices;
        }

        return $lowestPrices ?? new ProductPricesData(0.0, 0.0);
    }

    private function isAvailable(Product $childProduct): bool
    {
        return (int)$childProduct->getStatus() === Status::STATUS_ENABLED && $childProduct->isInStock();
    }
}
