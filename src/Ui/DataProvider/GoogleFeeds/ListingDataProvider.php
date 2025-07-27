<?php

declare(strict_types=1);

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

    /**
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        FeedRepositoryInterface $feedRepository,
        array $meta = [],
        array $data = []
    ) {
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

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $items = $this->feedRepository->getList();
        return [
            'items' => $items,
            'totalRecords' => count($items),
        ];
    }
}
