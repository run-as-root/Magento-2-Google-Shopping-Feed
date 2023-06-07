<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Url;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\UrlProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ChildProductParamsProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ParentProductProvider;

final class UrlProviderTest extends TestCase
{
    /** @var Url|MockObject */
    private Url $url;
    /** @var StoreManagerInterface|MockObject */
    private StoreManagerInterface $storeManager;
    /** @var ParentProductProvider|MockObject */
    private ParentProductProvider $productProvider;
    /** @var ChildProductParamsProvider|MockObject */
    private ChildProductParamsProvider $childProductParamsProvider;
    private UrlProvider $sut;

    protected function setUp(): void
    {
        $this->url = $this->createMock(Url::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->productProvider = $this->createMock(ParentProductProvider::class);
        $this->childProductParamsProvider = $this->createMock(ChildProductParamsProvider::class);

        $this->sut = new UrlProvider(
            $this->url,
            $this->storeManager,
            $this->productProvider,
            $this->childProductParamsProvider
        );
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGet(?array $queryParams, array $query): void
    {
        $storeId = 1;

        $product = $this->createMock(Product::class);
        $product->method('getStoreId')->willReturn($storeId);

        $store = $this->createMock(Store::class);
        $this->storeManager->method('getStore')
            ->with($storeId)
            ->willReturn($store);

        $mockBuilder = $this->getMockBuilder(Product::class);
        $mockBuilder->addMethods([ 'getUrlKey' ]);
        $mockBuilder->disableOriginalConstructor();
        $productForUrlRetrieval = $mockBuilder->getMock();

        $urlKey = 'some-url-key';
        $productForUrlRetrieval->method('getUrlKey')->willReturn($urlKey);

        $this->productProvider->method('get')
            ->with($product)
            ->willReturn($productForUrlRetrieval);

        $this->childProductParamsProvider->method('get')
            ->with($product, $productForUrlRetrieval)
            ->willReturn($queryParams);

        $scope = 'de';
        $this->url->method('getData')->with('scope')->willReturn($scope);

        $this->url->method('setScope')->with($store);

        $routeParamsShort = [
            '_direct' => $urlKey,
            '_nosid' => true,
            '_query' => $query,
            '_scope_to_url' => true,
            '_scope' => $scope,
        ];

        $this->url->method('getUrl')
            ->with('', $routeParamsShort)
            ->willReturnCallback(function (string $routePath, array $routeParamsShort) {
                return 'https://run_as_root.test/' . $routePath . $routeParamsShort['_direct'];
            });

        $res = $this->sut->get($product);
        $expectedUrl = 'https://run_as_root.test/' . $urlKey;
        $this->assertEquals($expectedUrl, $res);
    }

    public function dataProvider(): array
    {
        $queryParams = [ 'tec_color' => 123 ];

        return [
            [
                'query_params' => $queryParams,
                'query' => array_merge([ '___store' => null ], $queryParams),
            ],
            [
                'query_params' => null,
                'query' => [ '___store' => null ],
            ],
        ];
    }

    public function testGetStoreNotFound(): void
    {
        $storeId = 1;

        $product = $this->createMock(Product::class);
        $product->method('getStoreId')->willReturn($storeId);

        $this->storeManager->method('getStore')
            ->with($storeId)
            ->willThrowException(new NoSuchEntityException());

        $res = $this->sut->get($product);
        $this->assertEquals(null, $res);
    }
}
