<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Framework\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\UrlSuffixProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\ProductUrlProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ParentProductProvider;

final class ProductUrlProviderTest extends TestCase
{
    private ProductUrlProvider $sut;

    /** @var Url|MockObject */
    private $urlMock;

    /** @var ParentProductProvider|MockObject */
    private $productProviderMock;

    /** @var UrlSuffixProvider|MockObject */
    private $urlSuffixProviderMock;

    protected function setUp(): void
    {
        $this->urlMock = $this->createMock(Url::class);
        $this->productProviderMock = $this->createMock(ParentProductProvider::class);
        $this->urlSuffixProviderMock = $this->createMock(UrlSuffixProvider::class);
        $this->sut = new ProductUrlProvider(
            $this->urlMock,
            $this->productProviderMock,
            $this->urlSuffixProviderMock
        );
    }

    public function testGet(): void
    {
        $productMock = $this->createMock(Product::class);

        $this->productProviderMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($productMock);

        $productMock->expects($this->once())
            ->method('getData')
            ->with('url_key')
            ->willReturn('some-product-url');

        $this->urlSuffixProviderMock
            ->expects($this->once())
            ->method('get')
            ->willReturn('.html');

        $routeParamsShort = [
            '_direct' => 'some-product-url.html',
            '_nosid' => true
        ];

        $this->urlMock
            ->expects($this->once())
            ->method('getUrl')
            ->with('', $routeParamsShort)
            ->willReturn('some-product-url.html');

        $this->assertEquals('some-product-url.html', $this->sut->get($productMock));
    }
}
