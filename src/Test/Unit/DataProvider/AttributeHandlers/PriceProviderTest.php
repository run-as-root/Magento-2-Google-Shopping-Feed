<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\Data\ProductPricesData;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\PriceProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\CurrencyAmountProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ProductPricesProvider;

final class PriceProviderTest extends TestCase
{
    private PriceProvider $sut;

    /**
     * @var CurrencyAmountProvider|MockObject
     */
    private $currencyAmountProviderMock;

    /**
     * @var ProductPricesProvider|MockObject
     */
    private $productPricesProviderMock;

    protected function setUp(): void
    {
        $this->currencyAmountProviderMock = $this->createMock(CurrencyAmountProvider::class);
        $this->productPricesProviderMock = $this->createMock(ProductPricesProvider::class);
        $this->sut = new PriceProvider($this->currencyAmountProviderMock, $this->productPricesProviderMock);
    }

    public function testGetReturnsRegularPriceEvenWhenFinalPriceIsLower(): void
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getStoreId')->willReturn('1');

        $this->productPricesProviderMock->method('get')
            ->with($productMock)
            ->willReturn(new ProductPricesData(125.55, 99.0));

        $this->currencyAmountProviderMock
            ->expects($this->once())
            ->method('get')
            ->with(125.55, 1)
            ->willReturn('125.55 EUR');

        $this->assertSame('125.55 EUR', $this->sut->get($productMock));
    }
}
