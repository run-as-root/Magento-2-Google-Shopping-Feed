<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Directory\Model\Currency;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\Data\ProductPricesData;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\SalePriceProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\CurrencyAmountProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\CurrencyProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ProductPricesProvider;

final class SalePriceProviderTest extends TestCase
{
    private const STORE_ID = 2;

    private SalePriceProvider $sut;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrencyMock;

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
        $currencyMock = $this->createMock(Currency::class);
        $currencyMock->method('getCode')->willReturn('EUR');
        $currencyProviderMock = $this->createMock(CurrencyProvider::class);
        $currencyProviderMock->method('get')->with(self::STORE_ID)->willReturn($currencyMock);
        $this->priceCurrencyMock = $this->createMock(PriceCurrencyInterface::class);
        $this->productPricesProviderMock = $this->createMock(ProductPricesProvider::class);
        $this->productMock = $this->createMock(Product::class);
        $this->productMock->method('getStoreId')->willReturn((string)self::STORE_ID);

        $this->sut = new SalePriceProvider(
            $this->productPricesProviderMock,
            new CurrencyAmountProvider($currencyProviderMock, $this->priceCurrencyMock)
        );
    }

    public function testGetReturnsConvertedFinalPriceWhenLowerThanRegularPrice(): void
    {
        $this->useConversionRate(0.5);
        $this->productPricesProviderMock->method('get')
            ->with($this->productMock)
            ->willReturn(new ProductPricesData(34.0, 29.0));

        $this->assertSame('14.50 EUR', $this->sut->get($this->productMock));
    }

    public function testGetReturnsEmptyStringWhenPricesAreEqualAfterConversion(): void
    {
        $this->useConversionRate(0.5);
        $this->productPricesProviderMock->method('get')
            ->willReturn(new ProductPricesData(10.0, 9.99));

        $this->assertSame('', $this->sut->get($this->productMock));
    }

    /**
     * @dataProvider notDiscountedPricesProvider
     */
    public function testGetReturnsEmptyStringWhenFinalPriceIsNotLower(float $regularPrice, float $finalPrice): void
    {
        $this->productPricesProviderMock->method('get')
            ->willReturn(new ProductPricesData($regularPrice, $finalPrice));

        $this->priceCurrencyMock->expects($this->never())->method('convertAndRound');

        $this->assertSame('', $this->sut->get($this->productMock));
    }

    public static function notDiscountedPricesProvider(): array
    {
        return [
            'final price equals regular price' => [34.0, 34.0],
            'final price higher than regular price' => [34.0, 40.0],
            'no price at all' => [0.0, 0.0],
        ];
    }

    private function useConversionRate(float $rate): void
    {
        $this->priceCurrencyMock->method('convertAndRound')
            ->willReturnCallback(static function ($amount) use ($rate) {
                return round($amount * $rate, 2);
            });
    }
}
