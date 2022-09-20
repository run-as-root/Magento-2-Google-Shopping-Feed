<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider;

use Magento\Directory\Model\Currency;
use Magento\Framework\Pricing\Helper\Data;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\DataProvider\CurrencyAmountProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\CurrencyProvider;

final class CurrencyAmountProviderTest extends TestCase
{
    private CurrencyAmountProvider $sut;

    protected function setUp(): void
    {
        $priceHelperMock = $this->createMock(Data::class);
        $currencyProviderMock = $this->createMock(CurrencyProvider::class);
        $currencyMock = $this->createMock(Currency::class);

        $priceHelperMock->expects($this->once())
            ->method('currencyByStore')
            ->with(120.00, 1, true, false)
            ->willReturn('120,00 €');

        $currencyProviderMock->expects($this->once())
            ->method('get')
            ->willReturn($currencyMock);

        $currencyMock->expects($this->once())
            ->method('getCurrencySymbol')
            ->willReturn('€');

        $currencyMock->expects($this->once())
            ->method('getCode')
            ->willReturn('EUR');

        $this->sut = new CurrencyAmountProvider($currencyProviderMock, $priceHelperMock);
    }

    public function testGet(): void
    {
        $expected = '120,00 EUR';
        $this->assertEquals($expected, $this->sut->get(120.00, 1));
    }
}
