<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\Registry;

use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\Data\ProductPricesData;
use RunAsRoot\GoogleShoppingFeed\Registry\LastComputedProductPricesCache;

final class LastComputedProductPricesCacheTest extends TestCase
{
    private const PRODUCT_ID = 10;
    private const STORE_ID = 1;

    private LastComputedProductPricesCache $sut;

    protected function setUp(): void
    {
        $this->sut = new LastComputedProductPricesCache();
    }

    public function testGetReturnsNullWhenNothingWasSaved(): void
    {
        self::assertNull($this->sut->get($this->createProductMock(self::PRODUCT_ID, self::STORE_ID)));
    }

    public function testGetReturnsPricesSavedForTheSameProduct(): void
    {
        $productMock = $this->createProductMock(self::PRODUCT_ID, self::STORE_ID);
        $prices = new ProductPricesData(34.0, 29.0);

        $this->sut->save($productMock, $prices);

        self::assertSame($prices, $this->sut->get($productMock));
    }

    public function testGetReturnsNullForAnotherProductInstanceWithTheSameIdAndStore(): void
    {
        $this->sut->save($this->createProductMock(self::PRODUCT_ID, self::STORE_ID), new ProductPricesData(34.0, 29.0));

        self::assertNull($this->sut->get($this->createProductMock(self::PRODUCT_ID, self::STORE_ID)));
    }

    public function testGetReturnsNullWhenTheStoreOfTheSavedProductChanged(): void
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getId')->willReturn(self::PRODUCT_ID);
        $productMock->method('getStoreId')->willReturnOnConsecutiveCalls(self::STORE_ID, 2);

        $this->sut->save($productMock, new ProductPricesData(34.0, 29.0));

        self::assertNull($this->sut->get($productMock));
    }

    public function testSaveReplacesThePreviouslySavedProduct(): void
    {
        $firstProductMock = $this->createProductMock(self::PRODUCT_ID, self::STORE_ID);
        $secondProductMock = $this->createProductMock(11, self::STORE_ID);
        $secondPrices = new ProductPricesData(50.0, 50.0);

        $this->sut->save($firstProductMock, new ProductPricesData(34.0, 29.0));
        $this->sut->save($secondProductMock, $secondPrices);

        self::assertNull($this->sut->get($firstProductMock));
        self::assertSame($secondPrices, $this->sut->get($secondProductMock));
    }

    private function createProductMock(int $productId, int $storeId): Product
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getId')->willReturn($productId);
        $productMock->method('getStoreId')->willReturn($storeId);

        return $productMock;
    }
}
