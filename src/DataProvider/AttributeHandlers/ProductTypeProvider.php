<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductTypeProvider implements AttributeHandlerInterface
{
    /**
     * @var CategoryRepositoryInterface
     */
    private CategoryRepositoryInterface $categoryRepository;
    private array $cache = [];

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(CategoryRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function get(Product $product)
    {
        return $this->getProductType($product);
    }

    /**
     * @param Product $product
     *
     * @return string
     */
    private function getProductType(Product $product): string
    {
        $categoryIds = $product->getCategoryIds();

        if (empty($categoryIds)) {
            return '';
        }

        $categoryId = (int)$categoryIds[0];

        if (array_key_exists($categoryId, $this->cache)) {
            return $this->cache[$categoryId];
        }

        try {
            $fullCategoryPath = '';
            $category = $this->categoryRepository->get($categoryId, $product->getStoreId());
            $pathInStore = $category->getPathInStore();
            $pathIds = array_reverse(explode(',', $pathInStore));

            $categories = $category->getParentCategories();

            foreach ($pathIds as $categoryId) {
                if (!isset($categories[$categoryId]) || !$categories[$categoryId]->getName()) {
                    continue;
                }

                $fullCategoryPath .= $categories[$categoryId]->getName() . ' > ';
            }

            $this->cache[$categoryId] = rtrim($fullCategoryPath, '> ');
            return $this->cache[$categoryId];
        } catch (NoSuchEntityException $noSuchEntityException) {
            return '';
        }
    }
}
