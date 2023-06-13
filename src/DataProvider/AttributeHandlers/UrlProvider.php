<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Url;
use Magento\Store\Model\StoreManagerInterface;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ChildProductParamsProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ParentProductProvider;

class UrlProvider implements AttributeHandlerInterface
{
    private Url $url;
    private StoreManagerInterface $storeManager;
    private ParentProductProvider $productProvider;
    private ChildProductParamsProvider $childProductParamsProvider;

    public function __construct(
        Url                        $url,
        StoreManagerInterface      $storeManager,
        ParentProductProvider      $productProvider,
        ChildProductParamsProvider $childProductParamsProvider
    ) {
        $this->url = $url;
        $this->storeManager = $storeManager;
        $this->productProvider = $productProvider;
        $this->childProductParamsProvider = $childProductParamsProvider;
    }

    public function get(Product $product): ?string
    {
        try {
            $store = $this->storeManager->getStore($product->getStoreId());
        } catch (NoSuchEntityException $exception) {
            return null;
        }

        $productForUrlRetrieval = $this->productProvider->get($product);
        $queryParams = $this->childProductParamsProvider->get($product, $productForUrlRetrieval);

        if ($queryParams !== null) {
            $query = array_merge([ '___store' => null ], $queryParams);
        }

        $routeParamsShort = [
            '_direct' => $productForUrlRetrieval->getUrlKey(),
            '_nosid' => true,
            '_query' => $query ?? [ '___store' => null ],
            '_scope_to_url' => true,
            '_scope' => $this->url->getData('scope'),
        ];

        $this->url->setScope($store);
        return $this->url->getUrl('', $routeParamsShort);
    }
}
