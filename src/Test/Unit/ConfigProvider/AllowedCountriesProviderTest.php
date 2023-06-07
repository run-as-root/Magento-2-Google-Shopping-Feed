<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\AllowedCountriesProvider;

final class AllowedCountriesProviderTest extends TestCase
{
    private AllowedCountriesProvider $sut;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->sut = new AllowedCountriesProvider($this->scopeConfigMock);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGet($configValue, $storeId, $expected): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with('general/country/allow', 'store', $storeId)
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->sut->get($storeId));
    }

    public function dataProvider(): array
    {
        return [
            [null, 1, []],
            [false, 2, []],
            ['DE,BR', 10, ['DE', 'BR']]
        ];
    }
}
