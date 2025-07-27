<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Service;

use Magento\Bundle\Model\Product\Type as BundleProduct;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Api\Data\StoreInterface;
use RunAsRoot\GoogleShoppingFeed\CollectionProvider\ProductsCollectionProvider;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\FeedConfigProvider;
use RunAsRoot\GoogleShoppingFeed\Converter\ArrayToXmlConverter;
use RunAsRoot\GoogleShoppingFeed\Data\AttributeConfigDataList;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AllowedCategoryIdsProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributesConfigListProvider;
use RunAsRoot\GoogleShoppingFeed\Exception\GenerateFeedForStoreException;
use RunAsRoot\GoogleShoppingFeed\Exception\HandlerIsNotSpecifiedException;
use RunAsRoot\GoogleShoppingFeed\Exception\WrongInstanceException;
use RunAsRoot\GoogleShoppingFeed\Mapper\ProductToFeedAttributesRowMapper;
use RunAsRoot\GoogleShoppingFeed\SourceModel\ConfigurableExportType;
use RunAsRoot\GoogleShoppingFeed\Writer\XmlFileWriterProvider;

class GenerateFeedForStore
{
    private const STATUS_ENABLED = Status::STATUS_ENABLED;

    private FeedConfigProvider $configProvider;
    private AttributesConfigListProvider $attributesConfigListProvider;
    private ProductToFeedAttributesRowMapper $productToRowMapper;
    private XmlFileWriterProvider $xmlFileWriterProvider;
    private ProductsCollectionProvider $productsCollectionProvider;
    private AllowedCategoryIdsProvider $allowedCategoryIdsProvider;
    private ArrayToXmlConverter $arrayToXmlConverter;
    private ProductRepositoryInterface $productRepository;

    public function __construct(
        FeedConfigProvider $configProvider,
        AttributesConfigListProvider $attributesConfigListProvider,
        ProductToFeedAttributesRowMapper $productToRowMapper,
        XmlFileWriterProvider $xmlFileWriterProvider,
        ProductsCollectionProvider $productsCollectionProvider,
        AllowedCategoryIdsProvider $allowedCategoryIdsProvider,
        ArrayToXmlConverter $arrayToXmlConverter,
        ProductRepositoryInterface $productRepository
    ) {
        $this->configProvider = $configProvider;
        $this->attributesConfigListProvider = $attributesConfigListProvider;
        $this->productToRowMapper = $productToRowMapper;
        $this->xmlFileWriterProvider = $xmlFileWriterProvider;
        $this->productsCollectionProvider = $productsCollectionProvider;
        $this->allowedCategoryIdsProvider = $allowedCategoryIdsProvider;
        $this->arrayToXmlConverter = $arrayToXmlConverter;
        $this->productRepository = $productRepository;
    }

    /**
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function execute(StoreInterface $store): void
    {
        $storeId = (int)$store->getId();

        if (!$this->configProvider->isEnabled($storeId)) {
            return;
        }

        try {
            $fileWriter = $this->xmlFileWriterProvider->get($store);
        } catch (NoSuchEntityException $exception) {
            throw new GenerateFeedForStoreException(
                __('The file writer cannot be created for the store with id: %1', $storeId),
                $exception
            );
        }

        try {
            $attributesConfigList = $this->attributesConfigListProvider->get();
        } catch (\InvalidArgumentException $exception) {
            throw new GenerateFeedForStoreException(
                __('Attributes config list is invalid. %1' . $exception->getMessage()),
                $exception
            );
        }

        $whitelistedCategories = $this->allowedCategoryIdsProvider->get($storeId);
        $currentPage = 1;

        /** @var array<int, array<string, mixed>> $rows */
        $rows = [];

        do {
            $collection = $this->productsCollectionProvider->get(
                $currentPage,
                $whitelistedCategories,
                $storeId
            );

            $items = $collection->getItems();

            foreach ($items as $product) {
                /** @var Product $product */
                if (isset($rows[$product->getId()])) {
                    continue;
                }

                $productRows = $this->processProduct($product, $attributesConfigList);

                // phpcs:ignore Magento2.Performance.ForeachArrayMerge.ForeachArrayMerge
                $rows = array_merge($rows, $productRows);
            }

            $currentPage++;
        } while ($this->canProceed($collection, $currentPage));

        $fileWriter->write($this->arrayToXmlConverter->convert($rows));
    }

    private function canProceed(ProductCollection $productCollection, int $currentPage): bool
    {
        $pageSize = $productCollection->getPageSize();
        return $pageSize * $currentPage < $productCollection->getSize() + $pageSize;
    }

    /**
     * @throws GenerateFeedForStoreException
     * @throws NoSuchEntityException
     */
    private function processProduct(Product $product, AttributeConfigDataList $attributesConfigList): array
    {
        $typeInstance = $product->getTypeInstance();

        if ($typeInstance instanceof Configurable) {
            return $this->getConfigurableProductRows($product, $attributesConfigList);
        }

        if ($typeInstance instanceof Grouped) {
            return $this->getGroupedProductRows($typeInstance, $product, $attributesConfigList);
        }

        if ($typeInstance instanceof BundleProduct) {
            return $this->getBundleProductRows($typeInstance, $product, $attributesConfigList);
        }

        return $this->getSimpleProductRows($product, $attributesConfigList);
    }

    /**
     * @throws GenerateFeedForStoreException
     */
    private function getSimpleProductRows(Product $product, AttributeConfigDataList $attributesConfigList): array
    {
        try {
            return [$product->getId() => $this->productToRowMapper->map($product, $attributesConfigList)];
        } catch (HandlerIsNotSpecifiedException | WrongInstanceException $exception) {
            throw new GenerateFeedForStoreException(
                __(
                    'Product can not be mapped to feed row. Product ID: %1 . Error: %2',
                    $product->getId(),
                    $exception->getMessage()
                ),
                $exception
            );
        }
    }

    /**
     * @throws GenerateFeedForStoreException
     * @throws NoSuchEntityException
     */
    private function getConfigurableProductRows(
        Product $product,
        AttributeConfigDataList $attributesConfigList
    ): array {
        $storeId = (int)$product->getStoreId();
        $configExportType = $this->configProvider->getConfigurableExportType($storeId);

        if ($configExportType === ConfigurableExportType::EXPORT_PARENT_PRODUCTS) {
            return $this->getConfigurableParentProductRows($product, $attributesConfigList);
        }

        return $this->getConfigurableChildProductRows($product, $attributesConfigList);
    }

    /**
     * @throws GenerateFeedForStoreException
     * @throws NoSuchEntityException
     */
    private function getGroupedProductRows(
        Grouped $typeInstance,
        Product $product,
        AttributeConfigDataList $attributesConfigList
    ): array {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = [];
        $childProducts = $typeInstance->getAssociatedProducts($product);

        foreach ($childProducts as $childProduct) {
            /** @var Product $childProduct */
            if ((int)$childProduct->getStatus() !== self::STATUS_ENABLED) {
                continue;
            }

            $visibility = (int)$childProduct->getVisibility();

            if ($visibility === \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE) {
                continue;
            }

            try {
                $childProduct = $this->productRepository
                    ->get($childProduct->getSku(), false, $childProduct->getStoreId());

                if (!$childProduct instanceof Product) {
                    throw new \InvalidArgumentException('Expected Product instance');
                }

                $rows[$childProduct->getId()] = $this->productToRowMapper
                    ->map($childProduct, $attributesConfigList);
            } catch (HandlerIsNotSpecifiedException | WrongInstanceException $exception) {
                throw new GenerateFeedForStoreException(
                    __(
                        'Product can not be mapped to feed row. Product ID: %1 . Error: %2',
                        $product->getId(),
                        $exception->getMessage()
                    ),
                    $exception
                );
            }
        }

        return $rows;
    }

    /**
     * @throws GenerateFeedForStoreException
     * @throws NoSuchEntityException
     */
    private function getBundleProductRows(
        BundleProduct $typeInstance,
        Product $product,
        AttributeConfigDataList $attributesConfigList
    ): array {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = [];
        $childProductIds = $typeInstance->getChildrenIds($product->getId());

        foreach ($childProductIds as $productIds) {
            foreach ($productIds as $childProductId) {
                try {
                    $childProduct = $this->productRepository
                        ->getById($childProductId, false, $product->getStoreId());

                    if (!$childProduct instanceof Product) {
                        throw new \InvalidArgumentException('Expected Product instance');
                    }
    
                    if ((int)$childProduct->getStatus() !== self::STATUS_ENABLED) {
                        continue;
                    }

                    $rows[$childProduct->getId()] = $this->productToRowMapper
                        ->map($childProduct, $attributesConfigList);
                } catch (HandlerIsNotSpecifiedException | WrongInstanceException $exception) {
                    throw new GenerateFeedForStoreException(
                        __(
                            'Product can not be mapped to feed row. Product ID: %1 . Error: %2',
                            $product->getId(),
                            $exception->getMessage()
                        ),
                        $exception
                    );
                }
            }
        }

        return $rows;
    }

    /**
     * @throws GenerateFeedForStoreException
     * @throws NoSuchEntityException
     */
    private function getConfigurableParentProductRows(
        Product $product,
        AttributeConfigDataList $attributesConfigList
    ): array {
        $typeInstance = $product->getTypeInstance();
        /** @var Configurable $typeInstance */
        $childProducts = $typeInstance->getUsedProducts($product);

        $availableChildren = $this->getAvailableChildProducts(array_map(static function ($product) {
            if (!$product instanceof Product) {
                throw new \InvalidArgumentException('Expected Product instance');
            }

            return $product;
        }, $childProducts));

        if (empty($availableChildren)) {
            return [];
        }

        try {
            return [$product->getId() => $this->productToRowMapper->map($product, $attributesConfigList)];
        } catch (HandlerIsNotSpecifiedException | WrongInstanceException $exception) {
            throw new GenerateFeedForStoreException(
                __(
                    'Product can not be mapped to feed row. Product ID: %1 . Error: %2',
                    $product->getId(),
                    $exception->getMessage()
                ),
                $exception
            );
        }
    }

    /**
     * @throws GenerateFeedForStoreException
     * @throws NoSuchEntityException
     */
    private function getConfigurableChildProductRows(
        Product $product,
        AttributeConfigDataList $attributesConfigList
    ): array {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = [];
        $typeInstance = $product->getTypeInstance();
        /** @var Configurable $typeInstance */
        $childProducts = $typeInstance->getUsedProducts($product);

        foreach ($childProducts as $childProduct) {
            /** @var Product $childProduct */
            if ((int)$childProduct->getStatus() !== self::STATUS_ENABLED) {
                continue;
            }

            $visibility = (int)$childProduct->getVisibility();

            if ($visibility === \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE) {
                continue;
            }

            try {
                $childProduct = $this->productRepository
                    ->get($childProduct->getSku(), false, $childProduct->getStoreId());

                if (!$childProduct instanceof Product) {
                    throw new \InvalidArgumentException('Expected Product instance');
                }

                $rows[$childProduct->getId()] = $this->productToRowMapper
                    ->map($childProduct, $attributesConfigList);
            } catch (HandlerIsNotSpecifiedException | WrongInstanceException $exception) {
                throw new GenerateFeedForStoreException(
                    __(
                        'Product can not be mapped to feed row. Product ID: %1 . Error: %2',
                        $product->getId(),
                        $exception->getMessage()
                    ),
                    $exception
                );
            }
        }

        return $rows;
    }

    /**
     * @param Product[] $childProducts
     * @return Product[]
     */
    private function getAvailableChildProducts(array $childProducts): array
    {
        /** @var Product[] $availableChildren */
        $availableChildren = [];

        foreach ($childProducts as $childProduct) {
            if (
                (int) $childProduct->getStatus() !== Status::STATUS_ENABLED ||
                !$childProduct->isInStock()
            ) {
                continue;
            }

            $availableChildren[] = $childProduct;
        }

        return $availableChildren;
    }
}
