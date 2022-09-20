<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use RunAsRoot\Feed\CollectionProvider\LeastLevelCategoryCollectionProvider;
use RunAsRoot\Feed\DataProvider\AllowedCategoryIdsProvider;

use function array_intersect;
use function reset;

class CategoryUrlProvider implements AttributeHandlerInterface
{
    private LeastLevelCategoryCollectionProvider $leastLevelCategoryCollectionProvider;
    private AllowedCategoryIdsProvider $categoryIdsProvider;
    private UrlInterface $url;

    public function __construct(
        LeastLevelCategoryCollectionProvider $leastLevelCategoryCollectionProvider,
        AllowedCategoryIdsProvider $categoryIdsProvider,
        UrlInterface $url
    ) {
        $this->leastLevelCategoryCollectionProvider = $leastLevelCategoryCollectionProvider;
        $this->categoryIdsProvider = $categoryIdsProvider;
        $this->url = $url;
    }

    public function get(Product $product): ?string
    {
        $storeId = (int)$product->getStoreId();

        $categoryIds = array_intersect(
            $product->getCategoryIds(),
            $this->categoryIdsProvider->get($storeId)
        );

        try {
            $collection = $this->leastLevelCategoryCollectionProvider->get($categoryIds, $storeId);
        } catch (LocalizedException $exception) {
            return null;
        }

        $items = $collection->getItems();
        $item = reset($items);

        if (!$item instanceof Category) {
            return null;
        }

        $requestPath = $item->getRequestPath();
        return $this->url->getDirectUrl($requestPath, [ '_scope' => $storeId ]);
    }
}
