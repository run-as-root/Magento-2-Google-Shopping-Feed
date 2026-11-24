<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\Exception\LocalizedException;
use RunAsRoot\GoogleShoppingFeed\Data\ProductPricesData;
use RunAsRoot\GoogleShoppingFeed\Service\GetAssignedStockIdForStore;
use RunAsRoot\GoogleShoppingFeed\Service\InventoryAdapterFactory;
use RunAsRoot\GoogleShoppingFeed\Service\InventoryAdapterInterface;

class ProductPricesProvider
{
    private const CUSTOMER_GROUP_ID = 'customer_group_id';
    private const SINGLE_ITEM_QTY = 1.0;

    private GetAssignedStockIdForStore $getAssignedStockIdForStore;
    private InventoryAdapterFactory $inventoryAdapterFactory;
    private ?InventoryAdapterInterface $inventoryAdapter = null;
    private ?Product $lastProduct = null;
    private ?int $lastProductId = null;
    private ?int $lastStoreId = null;
    private ?ProductPricesData $lastPrices = null;

    public function __construct(
        GetAssignedStockIdForStore $getAssignedStockIdForStore,
        InventoryAdapterFactory $inventoryAdapterFactory
    ) {
        $this->getAssignedStockIdForStore = $getAssignedStockIdForStore;
        $this->inventoryAdapterFactory = $inventoryAdapterFactory;
    }

    public function get(Product $product): ProductPricesData
    {
        if ($this->lastPrices !== null && $this->isLastComputedFor($product)) {
            return $this->lastPrices;
        }

        $prices = $this->calculate($product);
        $this->rememberLastComputed($product, $prices);

        return $prices;
    }

    private function isLastComputedFor(Product $product): bool
    {
        return $this->lastProduct === $product
            && $this->lastProductId === (int)$product->getId()
            && $this->lastStoreId === (int)$product->getStoreId();
    }

    private function rememberLastComputed(Product $product, ProductPricesData $prices): void
    {
        $this->lastProduct = $product;
        $this->lastProductId = (int)$product->getId();
        $this->lastStoreId = (int)$product->getStoreId();
        $this->lastPrices = $prices;
    }

    private function calculate(Product $product): ProductPricesData
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

        return new ProductPricesData(
            (float)$product->getPrice(),
            (float)$product->getFinalPrice(self::SINGLE_ITEM_QTY)
        );
    }

    private function getLowestAvailableChildPrices(Configurable $typeInstance, Product $product): ProductPricesData
    {
        $lowestPrices = null;

        foreach ($this->getAvailableChildren($typeInstance, $product) as $childProduct) {
            $childPrices = $this->getOwnPrices($childProduct);

            if ($lowestPrices !== null && !$this->isLower($childPrices, $lowestPrices)) {
                continue;
            }

            $lowestPrices = $childPrices;
        }

        return $lowestPrices ?? new ProductPricesData(0.0, 0.0);
    }

    private function isLower(ProductPricesData $candidate, ProductPricesData $current): bool
    {
        if ($candidate->getFinalPrice() !== $current->getFinalPrice()) {
            return $candidate->getFinalPrice() < $current->getFinalPrice();
        }

        return $candidate->getRegularPrice() < $current->getRegularPrice();
    }

    /**
     * Salable enabled children, or all enabled children when salability can not be determined or none is salable.
     *
     * @return Product[]
     */
    private function getAvailableChildren(Configurable $typeInstance, Product $product): array
    {
        $enabledChildren = $this->getEnabledChildren($typeInstance, $product);
        $salableSkus = $this->getSalableSkus($enabledChildren, (int)$product->getStoreId());

        $salableChildren = array_filter(
            $enabledChildren,
            static fn (Product $childProduct): bool => isset($salableSkus[(string)$childProduct->getSku()])
        );

        return $salableChildren ?: $enabledChildren;
    }

    /**
     * @return Product[]
     */
    private function getEnabledChildren(Configurable $typeInstance, Product $product): array
    {
        $enabledChildren = [];

        foreach ($typeInstance->getUsedProducts($product) as $childProduct) {
            if (!$childProduct instanceof Product || (int)$childProduct->getStatus() !== Status::STATUS_ENABLED) {
                continue;
            }

            $enabledChildren[] = $childProduct;
        }

        return $enabledChildren;
    }

    /**
     * @param Product[] $products
     * @return array<string, true>
     */
    private function getSalableSkus(array $products, int $storeId): array
    {
        if ($products === []) {
            return [];
        }

        $skus = array_map(static fn (Product $product): string => (string)$product->getSku(), $products);

        try {
            $stockId = $this->getAssignedStockIdForStore->execute($storeId);

            if ($stockId === null) {
                return [];
            }

            $salableResults = $this->getInventoryAdapter()->areProductsSalable($skus, $stockId);
        } catch (LocalizedException $exception) {
            return [];
        }

        $salableSkus = [];

        foreach ($salableResults as $salableResult) {
            if (!$salableResult->isSalable()) {
                continue;
            }

            $salableSkus[$salableResult->getSku()] = true;
        }

        return $salableSkus;
    }

    /**
     * @throws LocalizedException
     */
    private function getInventoryAdapter(): InventoryAdapterInterface
    {
        if ($this->inventoryAdapter === null) {
            $this->inventoryAdapter = $this->inventoryAdapterFactory->create();
        }

        return $this->inventoryAdapter;
    }
}
