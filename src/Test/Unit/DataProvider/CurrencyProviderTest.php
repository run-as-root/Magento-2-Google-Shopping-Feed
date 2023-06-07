<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider;

use Magento\Directory\Model\Currency;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\DataProvider\CurrencyProvider;

final class CurrencyProviderTest extends TestCase
{
    private CurrencyProvider $sut;

    protected function setUp(): void
    {
        $storeManagerProviderMock = $this->createMock(StoreManagerInterface::class);
        $storeMock = $this->createMock(Store::class);
        $currencyMock = $this->createMock(Currency::class);

        $storeManagerProviderMock->expects($this->once())
            ->method('getStore')
            ->with(1)
            ->willReturn($storeMock);

        $storeMock->expects($this->once())
            ->method('getCurrentCurrency')
            ->willReturn($currencyMock);

        $this->sut = new CurrencyProvider($storeManagerProviderMock);
    }

    public function testGet(): void
    {
        $this->sut->get(1);
    }
}
