<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\AdditionalImageLinkProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ParentProductProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ProductImageUrlProvider;

final class AdditionalImageLinkProviderTest extends TestCase
{
    private AdditionalImageLinkProvider $sut;

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

        $this->sut = new AdditionalImageLinkProvider(
            $this->parentProductProviderMock,
            $this->productImageUrlProviderMock,
            $this->storeManagerProviderMock
        );
    }

    public function testGet(): void
    {
        $productMock = $this->createMock(Product::class);
        $productImageOne = 'o/e/image-one.jpg';
        $productImageTwo = 'o/e/image-two.jpg';
        $productImageThree = 'o/e/image-three.jpg';

        $productMock->expects($this->once())
            ->method('getImage')
            ->willReturn(null);

        $productMock->expects($this->once())
            ->method('getMediaGalleryEntries')
            ->willReturn([]);

        $parentProductMock = $this->createMock(Product::class);
        $this->parentProductProviderMock
            ->expects($this->once())
            ->method('get')
            ->with($productMock)
            ->willReturn($parentProductMock);

        $parentProductMock->expects($this->once())
            ->method('getImage')
            ->willReturn('o/e/image-one.jpg');

        $mediaGalleryEntryOne = $this->createMock(ProductAttributeMediaGalleryEntryInterface::class);
        $mediaGalleryEntryTwo = $this->createMock(ProductAttributeMediaGalleryEntryInterface::class);
        $mediaGalleryEntryThree = $this->createMock(ProductAttributeMediaGalleryEntryInterface::class);
        $mediaGalleryEntries = [
            $mediaGalleryEntryOne,
            $mediaGalleryEntryTwo,
            $mediaGalleryEntryThree
        ];

        $parentProductMock->expects($this->once())
            ->method('getMediaGalleryEntries')
            ->willReturn($mediaGalleryEntries);

        $productMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn(1);

        $this->storeManagerProviderMock
            ->expects($this->once())
            ->method('setCurrentStore')
            ->with(1);

        $mediaGalleryEntryOne->expects($this->once())
            ->method('getFile')
            ->willReturn($productImageOne);

        $mediaGalleryEntryTwo->expects($this->exactly(3))
            ->method('getFile')
            ->willReturn($productImageTwo);

        $mediaGalleryEntryThree->expects($this->exactly(3))
            ->method('getFile')
            ->willReturn($productImageThree);

        $productImageLinkTwo = 'https://app.default.test/media/catalog/product/o/e/image-two.jpg';
        $productImageLinkThree = 'https://app.default.test/media/catalog/product/o/e/image-three.jpg';

        $this->productImageUrlProviderMock
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive([$productImageTwo], [$productImageThree])
            ->willReturnOnConsecutiveCalls($productImageLinkTwo, $productImageLinkThree);

        $expected = [$productImageLinkTwo, $productImageLinkThree];

        $this->assertEquals($expected, $this->sut->get($productMock));
    }
}
