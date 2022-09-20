<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\ConfigProvider\FeedConfigProvider;

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
}
