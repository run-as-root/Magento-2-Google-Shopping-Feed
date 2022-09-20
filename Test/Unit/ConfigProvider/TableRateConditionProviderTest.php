<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\TableRateConditionProvider;

final class TableRateConditionProviderTest extends TestCase
{
    private TableRateConditionProvider $sut;

    protected function setUp(): void
    {
        $scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with('carriers/tablerate/condition_name', 'store', 10)
            ->willReturn('package_value');

        $this->sut = new TableRateConditionProvider($scopeConfigMock);
    }

    public function testGet(): void
    {
        $this->assertEquals('package_value', $this->sut->get(10));
    }
}
