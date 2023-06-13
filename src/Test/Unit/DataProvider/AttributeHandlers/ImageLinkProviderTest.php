<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\ImageLinkProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ParentProductProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ProductImageUrlProvider;

final class ImageLinkProviderTest extends TestCase
{
    private ImageLinkProvider $sut;

    /** @var ParentProductProvider|MockObject */
    private $parentProductProviderMock;

    /** @var ProductImageUrlProvider|MockObject */
    private $productImageUrlProviderMock;

    /** @var StoreManagerInterface|MockObject */
    private $storeManagerProviderMock;

    protected function setUp(): void
    {
        $this->parentProductProviderMock = $this->createMock(ParentProductProvider::class);
        $this->productImageUrlProviderMock = $this->createMock(ProductImageUrlProvider::class);
        $this->storeManagerProviderMock = $this->createMock(StoreManagerInterface::class);

        $this->sut = new ImageLinkProvider(
            $this->parentProductProviderMock,
            $this->productImageUrlProviderMock,
            $this->storeManagerProviderMock
        );
    }

    public function testItShouldReturnTheChildProductImageUrl(): void
    {
        $productMock = $this->createMock(Product::class);
        $image = 'o/c/child-product-image.jpg';

        $productMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn(1);

        $this->storeManagerProviderMock
            ->expects($this->once())
            ->method('setCurrentStore')
            ->with(1);

        $productMock->expects($this->exactly(2))
            ->method('getImage')
            ->willReturn($image);

        $productImageLink = 'https://app.default.test/media/catalog/product/o/c/child-product-image.jpg';

        $this->productImageUrlProviderMock
            ->expects($this->once())
            ->method('get')
            ->with($image)
            ->willReturn($productImageLink);

        $this->assertEquals($productImageLink, $this->sut->get($productMock));
    }

    public function testItShouldReturnTheParentProductImageUrl(): void
    {
        $productMock = $this->createMock(Product::class);
        $image = 'o/c/parent-product-image.jpg';

        $productMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn(1);

        $this->storeManagerProviderMock
            ->expects($this->once())
            ->method('setCurrentStore')
            ->with(1);

        $productMock->expects($this->exactly(1))
            ->method('getImage')
            ->willReturn(null);

        $parentProductMock = $this->createMock(Product::class);
        $this->parentProductProviderMock
            ->expects($this->once())
            ->method('get')
            ->with($productMock)
            ->willReturn($parentProductMock);

        $parentProductMock->expects($this->exactly(2))
            ->method('getImage')
            ->willReturn($image);

        $productImageLink = 'https://app.default.test/media/catalog/product/o/c/parent-product-image.jpg';

        $this->productImageUrlProviderMock
            ->expects($this->once())
            ->method('get')
            ->with($image)
            ->willReturn($productImageLink);

        $this->assertEquals($productImageLink, $this->sut->get($productMock));
    }
}
