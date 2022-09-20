<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\PriceProvider;
use RunAsRoot\Feed\DataProvider\CurrencyAmountProvider;

final class PriceProviderTest extends TestCase
{
    private PriceProvider $sut;

    /**
     * @var CurrencyAmountProvider|MockObject
     */
    private $currencyAmountProviderMock;

    protected function setUp(): void
    {
        $this->currencyAmountProviderMock = $this->createMock(CurrencyAmountProvider::class);
        $this->sut = new PriceProvider($this->currencyAmountProviderMock);
    }

    public function testGet(): void
    {
        $productMock = $this->createMock(Product::class);

        $productMock->expects($this->once())
            ->method('getFinalPrice')
            ->willReturn('125.55');

        $productMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn('1');

        $this->currencyAmountProviderMock
            ->expects($this->once())
            ->method('get')
            ->with(125.55, 1)
            ->willReturn('125,55 EUR');

        $this->assertEquals('125,55 EUR', $this->sut->get($productMock));
    }
}
