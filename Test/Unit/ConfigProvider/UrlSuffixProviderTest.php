<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\ConfigProvider\UrlSuffixProvider;

final class UrlSuffixProviderTest extends TestCase
{
    private UrlSuffixProvider $sut;

    /** @var ScopeConfigInterface|MockObject */
    private $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->sut = new UrlSuffixProvider($this->scopeConfigMock);
    }

    public function testGet(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with('catalog/seo/product_url_suffix')
            ->willReturn('.html');

        $this->assertEquals('.html', $this->sut->get());
    }
}
