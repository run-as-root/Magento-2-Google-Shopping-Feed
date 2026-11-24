<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\CollectionProvider;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;

class ProductsCollectionProvider
{
    public const BATCH_SIZE = 50;
    private const STATUS_ENABLED = 1;
    private const CONFIGURABLE_LINK_TABLE = 'catalog_product_super_link';

    private ProductCollectionFactory $productCollectionFactory;
    private ExportableConfigurableParentsCollectionProvider $exportableConfigurableParentsCollectionProvider;
    private MetadataPool $metadataPool;

    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        ExportableConfigurableParentsCollectionProvider $exportableConfigurableParentsCollectionProvider,
        MetadataPool $metadataPool
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->exportableConfigurableParentsCollectionProvider = $exportableConfigurableParentsCollectionProvider;
        $this->metadataPool = $metadataPool;
    }

    /**
     * @param array<int> $categories
     * @throws \Exception
     */
    public function get(int $page, array $categories, ?int $storeId = null): ProductCollection
    {
        $collection = $this->productCollectionFactory->create();

        $collection->addStoreFilter($storeId)
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('status', [ 'eq' => self::STATUS_ENABLED ])
            ->addAttributeToFilter('visibility', [ 'neq' => Visibility::VISIBILITY_NOT_VISIBLE ])
            ->addCategoriesFilter([ 'in' => $categories ]);

        $this->excludeChildrenOfExportableConfigurables($collection, $storeId ?? (int)$collection->getStoreId());

        $collection->setPageSize(self::BATCH_SIZE)
            ->setCurPage($page)
            ->addMediaGalleryData();

        return $collection;
    }

    /**
     * @throws \Exception
     */
    private function excludeChildrenOfExportableConfigurables(ProductCollection $collection, int $storeId): void
    {
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $childrenSelect = $this->exportableConfigurableParentsCollectionProvider->get($storeId)->getSelect();
        $childrenSelect->reset(Select::COLUMNS)
            ->join(
                [ 'super_link' => $collection->getTable(self::CONFIGURABLE_LINK_TABLE) ],
                'super_link.parent_id = e.' . $linkField,
                [ 'product_id' ]
            );

        $collection->getSelect()->where(
            $collection->getConnection()->prepareSqlCondition('e.entity_id', [ 'nin' => $childrenSelect ])
        );
    }
}
