<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider;

use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Framework\Escaper;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ProductImageUrlProvider;

final class ProductImageProviderTest extends TestCase
{
    private ProductImageUrlProvider $sut;

    private string $imageUrl = 'https://app.seidenland.test/media/catalog/product/c/a/image.jpg';
    private string $imagePath = 'c/a/image.jpg';

    protected function setUp(): void
    {
        $escaperMock = $this->createMock(Escaper::class);
        $urlBuilderMock = $this->createMock(UrlBuilder::class);

        $urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with($this->imagePath, 'product_page_image_large')
            ->willReturn($this->imageUrl);

        $escaperMock->expects($this->once())
            ->method('escapeUrl')
            ->with($this->imageUrl)
            ->willReturn($this->imageUrl);

        $this->sut = new ProductImageUrlProvider($escaperMock, $urlBuilderMock);
    }

    public function testGet(): void
    {
        $expected = $this->imageUrl;
        $this->assertEquals($expected, $this->sut->get($this->imagePath));
    }
}
