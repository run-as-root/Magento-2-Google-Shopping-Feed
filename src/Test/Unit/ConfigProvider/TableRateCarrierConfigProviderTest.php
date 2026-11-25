<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\TableRateCarrierConfigProvider;

final class TableRateCarrierConfigProviderTest extends TestCase
{
    private TableRateCarrierConfigProvider $sut;

    /** @var ScopeConfigInterface|MockObject */
    private $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->sut = new TableRateCarrierConfigProvider($this->scopeConfigMock);
    }

    public function testIsActive(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with('carriers/tablerate/active', 'store', 10)
            ->willReturn(true);

        $this->assertTrue($this->sut->isActive(10));
    }

    public function testGetHandlingType(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('carriers/tablerate/handling_type', 'store', 10)
            ->willReturn('P');

        $this->assertSame('P', $this->sut->getHandlingType(10));
    }

    public function testGetHandlingTypeDefaultsToFixed(): void
    {
        $this->scopeConfigMock->method('getValue')->willReturn(null);

        $this->assertSame('F', $this->sut->getHandlingType(10));
    }

    public function testGetHandlingFee(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('carriers/tablerate/handling_fee', 'store', 10)
            ->willReturn('2.5');

        $this->assertSame(2.5, $this->sut->getHandlingFee(10));
    }

    public function testIsSpecificCountriesOnly(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with('carriers/tablerate/sallowspecific', 'store', 10)
            ->willReturn(true);

        $this->assertTrue($this->sut->isSpecificCountriesOnly(10));
    }

    public function testGetSpecificCountries(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('carriers/tablerate/specificcountry', 'store', 10)
            ->willReturn('DE,US');

        $this->assertSame(['DE', 'US'], $this->sut->getSpecificCountries(10));
    }

    public function testGetSpecificCountriesWhenNoneAreSet(): void
    {
        $this->scopeConfigMock->method('getValue')->willReturn(null);

        $this->assertSame([], $this->sut->getSpecificCountries(10));
    }
}
