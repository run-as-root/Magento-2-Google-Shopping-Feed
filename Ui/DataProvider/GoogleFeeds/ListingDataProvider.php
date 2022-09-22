<?php

namespace RunAsRoot\GoogleShoppingFeed\Ui\DataProvider\GoogleFeeds;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use RunAsRoot\GoogleShoppingFeed\Api\FeedRepositoryInterface;

class ListingDataProvider extends DataProvider
{
    private FeedRepositoryInterface $feedRepository;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        FeedRepositoryInterface $feedRepository,
        array $meta = [],
        array $data = []
    )
    {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );

        $this->feedRepository = $feedRepository;
    }

    public function getData(): array
    {
        $items = $this->feedRepository->getList();
        return [
            'items' => $items,
            'totalRecords' => count($items)
        ];
    }
}