<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Service;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Bundle\Model\Product\Type as BundleProduct;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use RunAsRoot\GoogleShoppingFeed\CollectionProvider\ProductsCollectionProvider;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\FeedConfigProvider;
use RunAsRoot\GoogleShoppingFeed\Converter\ArrayToXmlConverter;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AllowedCategoryIdsProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributesConfigListProvider;
use RunAsRoot\GoogleShoppingFeed\Exception\GenerateFeedForStoreException;
use RunAsRoot\GoogleShoppingFeed\Exception\HandlerIsNotSpecifiedException;
use RunAsRoot\GoogleShoppingFeed\Exception\WrongInstanceException;
use RunAsRoot\GoogleShoppingFeed\Mapper\ProductToFeedAttributesRowMapper;
use RunAsRoot\GoogleShoppingFeed\Writer\XmlFileWriterProvider;

use function in_array;

class GenerateFeedForStore
{
    private const STATUS_ENABLED = 1;

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

        $rows = [];
        do {
            $collection = $this->productsCollectionProvider->get(
                $currentPage,
                $whitelistedCategories,
                $storeId
            );


            /** @var Product[] $items */
            $items = $collection->getItems();

            foreach ($items as $product) {
                if (isset($rows[$product->getId()])) {
                    continue;
                }

                $typeInstance = $product->getTypeInstance();

                // CONFIGURABLE AND GROUPED PRODUCTS FLOW
                if ($typeInstance instanceof Configurable || $typeInstance instanceof Grouped) {
                    $childProducts = $typeInstance instanceof Grouped ?
                        $typeInstance->getAssociatedProducts($product) : $typeInstance->getUsedProducts($product);
                    foreach ($childProducts as $childProduct) {
                        if ((int)$childProduct->getStatus() !== self::STATUS_ENABLED) {
                            continue;
                        }
                        try {
                            $childProduct = $this->productRepository
                                ->get($childProduct->getSku(), false, $childProduct->getStoreId());
                            $rows[$childProduct->getId()] = $this->productToRowMapper
                                ->map($childProduct, $attributesConfigList);
                        } catch (HandlerIsNotSpecifiedException|WrongInstanceException $exception) {
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
                    $currentPage++;
                    continue;
                }

                // BUNDLE PRODUCTS FLOW
                if ($typeInstance instanceof BundleProduct) {
                    $childProductIds = $typeInstance->getChildrenIds($product->getId());
                    foreach ($childProductIds as $productIds) {
                        foreach ($productIds as $childProductId) {
                            try {
                                $childProduct = $this->productRepository
                                    ->getById($childProductId, false, $product->getStoreId());
                                if ((int)$childProduct->getStatus() !== self::STATUS_ENABLED) {
                                    continue;
                                }
                                $rows[$childProduct->getId()] = $this->productToRowMapper
                                    ->map($childProduct, $attributesConfigList);
                            } catch (HandlerIsNotSpecifiedException|WrongInstanceException $exception) {
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
                    $currentPage++;
                    continue;
                }

                // SIMPLE PRODUCTS FLOW
                try {
                    $rows[$product->getId()] = $this->productToRowMapper->map($product, $attributesConfigList);
                } catch (HandlerIsNotSpecifiedException|WrongInstanceException $exception) {
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

            $currentPage++;
        } while ($this->canProceed($collection, $currentPage));

        $fileWriter->write($this->arrayToXmlConverter->convert($rows));
    }

    private function canProceed(ProductCollection $productCollection, int $currentPage): bool
    {
        $pageSize = $productCollection->getPageSize();
        return $pageSize * $currentPage < $productCollection->getSize() + $pageSize;
    }
}
