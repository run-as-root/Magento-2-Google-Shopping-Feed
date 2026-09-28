<?php

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\Ui\DataProvider\GoogleFeeds;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use RunAsRoot\GoogleShoppingFeed\Api\FeedRepositoryInterface;
use RunAsRoot\GoogleShoppingFeed\Ui\DataProvider\GoogleFeeds\ListingDataProvider;
use PHPUnit\Framework\TestCase;

class ListingDataProviderTest extends TestCase
{
    private ListingDataProvider $sut;

    private FeedRepositoryInterface $feedRepositoryMock;

    protected function setUp(): void
    {
        $name = 'google_shopping_feed_listing_data_source';
        $primaryFieldName = 'filename';
        $requestFieldName = 'filename';
        $reportingMock = $this->createMock(ReportingInterface::class);
        $searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $filterBuilderMock = $this->createMock(FilterBuilder::class);
        $this->feedRepositoryMock = $this->createMock(FeedRepositoryInterface::class);

        $this->sut = new ListingDataProvider(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reportingMock,
            $searchCriteriaBuilderMock,
            $requestMock,
            $filterBuilderMock,
            $this->feedRepositoryMock
        );
    }

    public function testGetEmptyDataTest(): void
    {
        $this->feedRepositoryMock->method('getList')->willReturn([]);
        $expected = [
            'items' => [],
            'totalRecords' => 0
        ];

        $this->assertEquals($expected, $this->sut->getData());
    }

    public function testGetDataTest(): void
    {
        $feedMock = $this->createMock(\RunAsRoot\GoogleShoppingFeed\Api\Data\FeedInterface::class);
        $feedMock->method('getLink')->willReturn('https://local.magento2.com/media/run_as_root/feed/base_store_default_feed.xml');

        $this->feedRepositoryMock->method('getList')->willReturn([$feedMock]);
        
        $result = $this->sut->getData();
        $this->assertCount(1, $result['items']);
        $this->assertEquals(1, $result['totalRecords']);
        $this->assertEquals('https://local.magento2.com/media/run_as_root/feed/base_store_default_feed.xml', $result['items'][0]->getLink());
    }
}
