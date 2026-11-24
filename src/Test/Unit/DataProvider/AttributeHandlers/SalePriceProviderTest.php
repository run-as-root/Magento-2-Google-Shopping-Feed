<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\Data\ProductPricesData;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\SalePriceProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\CurrencyAmountProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ProductPricesProvider;

final class SalePriceProviderTest extends TestCase
{
    private SalePriceProvider $sut;

    /**
     * @var CurrencyAmountProvider|MockObject
     */
    private $currencyAmountProviderMock;

    /**
     * @var ProductPricesProvider|MockObject
     */
    private $productPricesProviderMock;

    /**
     * @var Product|MockObject
     */
    private $productMock;

    protected function setUp(): void
    {
        $this->currencyAmountProviderMock = $this->createMock(CurrencyAmountProvider::class);
        $this->productPricesProviderMock = $this->createMock(ProductPricesProvider::class);
        $this->productMock = $this->createMock(Product::class);
        $this->productMock->method('getStoreId')->willReturn('2');
        $this->sut = new SalePriceProvider($this->productPricesProviderMock, $this->currencyAmountProviderMock);
    }

    public function testGetReturnsConvertedFinalPriceWhenLowerThanRegularPrice(): void
    {
        $this->productPricesProviderMock->method('get')
            ->with($this->productMock)
            ->willReturn(new ProductPricesData(34.0, 29.0));

        $this->currencyAmountProviderMock
            ->expects($this->once())
            ->method('get')
            ->with(29.0, 2)
            ->willReturn('29.00 USD');

        $this->assertSame('29.00 USD', $this->sut->get($this->productMock));
    }

    /**
     * @dataProvider notDiscountedPricesProvider
     */
    public function testGetReturnsEmptyStringWhenFinalPriceIsNotLower(float $regularPrice, float $finalPrice): void
    {
        $this->productPricesProviderMock->method('get')
            ->willReturn(new ProductPricesData($regularPrice, $finalPrice));

        $this->currencyAmountProviderMock->expects($this->never())->method('get');

        $this->assertSame('', $this->sut->get($this->productMock));
    }

    public static function notDiscountedPricesProvider(): array
    {
        return [
            'final price equals regular price' => [34.0, 34.0],
            'final price higher than regular price' => [34.0, 40.0],
            'final price equals regular price after rounding' => [34.0, 33.999],
            'no price at all' => [0.0, 0.0],
        ];
    }
}
