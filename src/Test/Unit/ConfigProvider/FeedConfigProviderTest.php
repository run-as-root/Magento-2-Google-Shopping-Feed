<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\FeedConfigProvider;
use RunAsRoot\GoogleShoppingFeed\SourceModel\ConfigurableExportType;

final class FeedConfigProviderTest extends TestCase
{
    private FeedConfigProvider $sut;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)->getMock();
        $this->sut = new FeedConfigProvider($this->scopeConfigMock);
    }

    public function testIsEnabled(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('isSetFlag')
            ->with('run_as_root_product_feed/general/enabled', 'store', 100)
            ->willReturn(true);

        $this->assertTrue($this->sut->isEnabled(100));
    }

    public function testGetCategoryWhitelist(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with('run_as_root_product_feed/general/category_whitelist', 'store', 100)
            ->willReturn('1,2,3');

        $this->assertEquals(['1', '2', '3'], $this->sut->getCategoryWhitelist(100));
    }

    public function testGetCategoryBlacklist(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with('run_as_root_product_feed/general/category_blacklist', 'store', 100)
            ->willReturn('1,2,3');

        $this->assertEquals(['1', '2', '3'], $this->sut->getCategoryBlacklist(100));
    }

    public function testGetConfigurableExportTypeReturnsConfiguredValue(): void
    {
        $storeId = 100;
        $configuredValue = ConfigurableExportType::EXPORT_PARENT_PRODUCTS;

        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with('run_as_root_product_feed/general/configurable_export_type', 'store', $storeId)
            ->willReturn($configuredValue);

        $this->assertEquals($configuredValue, $this->sut->getConfigurableExportType($storeId));
    }

    public function testGetConfigurableExportTypeReturnsDefaultWhenNotConfigured(): void
    {
        $storeId = 100;

        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with('run_as_root_product_feed/general/configurable_export_type', 'store', $storeId)
            ->willReturn(null);

        $this->assertEquals(
            ConfigurableExportType::EXPORT_CHILD_PRODUCTS,
            $this->sut->getConfigurableExportType($storeId)
        );
    }

    public function testGetConfigurableExportTypeReturnsDefaultWhenEmpty(): void
    {
        $storeId = 100;

        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with('run_as_root_product_feed/general/configurable_export_type', 'store', $storeId)
            ->willReturn('');

        $this->assertEquals(
            ConfigurableExportType::EXPORT_CHILD_PRODUCTS,
            $this->sut->getConfigurableExportType($storeId)
        );
    }

    public function testGetConfigurableExportTypeHandlesChildProductsValue(): void
    {
        $storeId = 100;
        $configuredValue = ConfigurableExportType::EXPORT_CHILD_PRODUCTS;

        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with('run_as_root_product_feed/general/configurable_export_type', 'store', $storeId)
            ->willReturn($configuredValue);

        $this->assertEquals($configuredValue, $this->sut->getConfigurableExportType($storeId));
    }
}
