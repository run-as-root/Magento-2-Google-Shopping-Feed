<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ParentProductIdProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ParentProductProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ProductProvider;
use RunAsRoot\GoogleShoppingFeed\Registry\FeedRegistry;

final class ParentProductUrlProvider extends TestCase
{
    /** @var FeedRegistry|MockObject */
    private FeedRegistry $registry;
    /** @var ProductProvider|MockObject */
    private ProductProvider $productProvider;
    /** @var ParentProductIdProvider|MockObject */
    private ParentProductIdProvider $parentProductIdProvider;
    private ParentProductProvider $sut;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(FeedRegistry::class);
        $this->productProvider = $this->createMock(ProductProvider::class);
        $this->parentProductIdProvider = $this->createMock(ParentProductIdProvider::class);
        $this->sut = new ParentProductProvider(
            $this->registry,
            $this->productProvider,
            $this->parentProductIdProvider
        );
    }

    public function testGet(): void
    {
        $storeId = 9;

        $productId = 456;
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn(Type::TYPE_SIMPLE);
        $product->method('getId')->willReturn($productId);
        $product->method('getStoreId')->willReturn($storeId);

        $parentProductId = 2431;
        $parentProductRegistryKey = (string)$parentProductId;
        $this->registry->expects($this->once())
            ->method('registryForStore')
            ->with($parentProductRegistryKey, $storeId)
            ->willReturn(null);

        $this->parentProductIdProvider->method('get')->with($productId)->willReturn($parentProductId);

        $productForUrlRetrieval = $this->createMock(Product::class);
        $productForUrlRetrieval->method('getId')->willReturn($parentProductId);
        $this->productProvider->method('get')->with($parentProductId, $storeId)
            ->willReturn($productForUrlRetrieval);

        $this->registry->expects($this->once())
            ->method('registerForStore')
            ->with($parentProductRegistryKey, $storeId, $productForUrlRetrieval);

        $res = $this->sut->get($product);
        $this->assertEquals($parentProductId, $res->getId());
    }

    public function testGetProductGetException(): void
    {
        $storeId = 9;

        $productId = 456;
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn(Type::TYPE_SIMPLE);
        $product->method('getId')->willReturn($productId);
        $product->method('getStoreId')->willReturn($storeId);

        $parentProductId = 2431;
        $parentProductRegistryKey = (string)$parentProductId;
        $this->registry->expects($this->once())
            ->method('registryForStore')
            ->with($parentProductRegistryKey, $storeId)
            ->willReturn(null);

        $this->parentProductIdProvider->method('get')->with($productId)->willReturn($parentProductId);

        $this->productProvider->method('get')->with($parentProductId, $storeId)
            ->willThrowException(new NoSuchEntityException());

        $this->registry->expects($this->once())
            ->method('registerForStore')
            ->with($parentProductRegistryKey, $storeId, $product);

        $res = $this->sut->get($product);
        $this->assertEquals($productId, $res->getId());
    }

    public function testGetFoundInRegistry(): void
    {
        $storeId = 9;

        $productId = 456;
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn(Type::TYPE_SIMPLE);
        $product->method('getId')->willReturn($productId);
        $product->method('getStoreId')->willReturn($storeId);

        $parentProductId = 2431;

        $this->parentProductIdProvider->method('get')->with($productId)->willReturn($parentProductId);

        $productForUrlRetrieval = $this->createMock(Product::class);
        $productForUrlRetrieval->method('getId')->willReturn($parentProductId);

        $this->productProvider->expects($this->never())->method('get');

        $parentProductRegistryKey = (string)$parentProductId;
        $this->registry->expects($this->exactly(2))
            ->method('registryForStore')
            ->with($parentProductRegistryKey, $storeId)
            ->willReturn($productForUrlRetrieval);

        $this->registry->expects($this->never())
            ->method('registerForStore')
            ->with($parentProductRegistryKey, $storeId, $productForUrlRetrieval);

        $res = $this->sut->get($product);
        $this->assertEquals($parentProductId, $res->getId());
    }

    public function testGetProductIsNotSimple(): void
    {
        $storeId = 9;

        $productId = 456;
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn(Type::TYPE_VIRTUAL);
        $product->method('getId')->willReturn($productId);
        $product->method('getStoreId')->willReturn($storeId);

        $res = $this->sut->get($product);
        $this->assertEquals($productId, $res->getId());
    }

    public function testGetNoParentProduct(): void
    {
        $storeId = 9;

        $productId = 456;
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn(Type::TYPE_SIMPLE);
        $product->method('getId')->willReturn($productId);
        $product->method('getStoreId')->willReturn($storeId);

        $parentProductId = null;
        $this->parentProductIdProvider->method('get')->with($productId)->willReturn($parentProductId);

        $res = $this->sut->get($product);
        $this->assertEquals($productId, $res->getId());
    }
}
