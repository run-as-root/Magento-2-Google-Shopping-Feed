<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\Service;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\Simple;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\CollectionProvider\ProductsCollectionProvider;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\FeedConfigProvider;
use RunAsRoot\GoogleShoppingFeed\Converter\ArrayToXmlConverter;
use RunAsRoot\GoogleShoppingFeed\Data\AttributeConfigDataList;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AllowedCategoryIdsProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributesConfigListProvider;
use RunAsRoot\GoogleShoppingFeed\Exception\GenerateFeedForStoreException;
use RunAsRoot\GoogleShoppingFeed\Mapper\ProductToFeedAttributesRowMapper;
use RunAsRoot\GoogleShoppingFeed\Service\GenerateFeedForStore;
use RunAsRoot\GoogleShoppingFeed\Writer\FileWriter;
use RunAsRoot\GoogleShoppingFeed\Writer\XmlFileWriterProvider;

final class GenerateFeedForStoreTest extends TestCase
{
    /** @var FeedConfigProvider|MockObject */
    private $configProviderMock;

    /** @var MockObject|AttributesConfigListProvider */
    private $attributesConfigListProviderMock;

    /** @var ProductToFeedAttributesRowMapper|MockObject */
    private $mapperMock;

    /** @var XmlFileWriterProvider|MockObject */
    private $xmlFileWriterProviderMock;

    /** @var ProductsCollectionProvider|MockObject */
    private $productsCollectionProviderMock;

    /** @var AllowedCategoryIdsProvider|MockObject */
    private $allowedCategoryIdsProviderMock;

    /** @var ArrayToXmlConverter|MockObject */
    private $arrayToXmlConverterMock;

    /** @var ProductRepositoryInterface|MockObject */
    private $productRepositoryMock;

    private GenerateFeedForStore $sut;

    protected function setUp(): void
    {
        $this->configProviderMock = $this->createMock(FeedConfigProvider::class);
        $this->attributesConfigListProviderMock = $this->createMock(AttributesConfigListProvider::class);
        $this->mapperMock = $this->createMock(ProductToFeedAttributesRowMapper::class);
        $this->xmlFileWriterProviderMock = $this->createMock(XmlFileWriterProvider::class);
        $this->productsCollectionProviderMock = $this->createMock(ProductsCollectionProvider::class);
        $this->allowedCategoryIdsProviderMock = $this->createMock(AllowedCategoryIdsProvider::class);
        $this->arrayToXmlConverterMock = $this->createMock(ArrayToXmlConverter::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);

        $this->sut = new GenerateFeedForStore(
            $this->configProviderMock,
            $this->attributesConfigListProviderMock,
            $this->mapperMock,
            $this->xmlFileWriterProviderMock,
            $this->productsCollectionProviderMock,
            $this->allowedCategoryIdsProviderMock,
            $this->arrayToXmlConverterMock,
            $this->productRepositoryMock
        );
    }

    public function testFeedGenerationIsNotEnabled(): void
    {
        $storeId = '1';
        $store = $this->createMock(StoreInterface::class);

        $store->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $this->configProviderMock->expects($this->once())
            ->method('isEnabled')
            ->with((int)$storeId)
            ->willReturn(false);

        $this->sut->execute($store);
    }

    public function testNoSuchEntityExceptionThrown(): void
    {
        $storeId = '1';
        $expectedLogMessage = 'The file writer cannot be created for the store with id: ' . $storeId;
        $store = $this->createMock(StoreInterface::class);
        $store->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $this->configProviderMock->expects($this->once())
            ->method('isEnabled')
            ->with((int)$storeId)
            ->willReturn(true);

        $this->xmlFileWriterProviderMock->expects($this->once())
            ->method('get')
            ->with($store)
            ->willThrowException(new NoSuchEntityException());

        $this->expectException(GenerateFeedForStoreException::class);
        $this->expectExceptionMessage($expectedLogMessage);

        $this->sut->execute($store);
    }

    public function testExecute(): void
    {
        $storeMock = $this->createMock(StoreInterface::class);
        $fileWriterMock = $this->createMock(FileWriter::class);
        $storeId = '1';

        $storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $this->configProviderMock->expects($this->once())
            ->method('isEnabled')
            ->with((int)$storeId)
            ->willReturn(true);

        $this->xmlFileWriterProviderMock
            ->expects($this->once())
            ->method('get')
            ->with($storeMock)
            ->willReturn($fileWriterMock);

        $this->attributesConfigListProviderMock->expects($this->once())
            ->method('get')
            ->willReturn(new AttributeConfigDataList([]));

        $whitelistedCategories = [1, 2, 3];

        $this->allowedCategoryIdsProviderMock
            ->expects($this->once())
            ->method('get')
            ->with((int)$storeId)
            ->willReturn($whitelistedCategories);

        $productOne = $this->createMock(Product::class);
        $productTwo = $this->createMock(Product::class);

        $productItems = [
            $productOne
        ];

        $typeInstanceMock = $this->createMock(Simple::class);

        $productOne->expects($this->once())
            ->method('getTypeInstance')
            ->willReturn($typeInstanceMock);

        $collectionMock = $this->createMock(ProductCollection::class);

        $collectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn($productItems);

        $collectionMock->expects($this->once())
            ->method('getPageSize')
            ->willReturn(300);

        $collectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn(count($productItems));

        $this->productsCollectionProviderMock
            ->expects($this->once())
            ->method('get')
            ->with(1, $whitelistedCategories, (int)$storeId)
            ->willReturn($collectionMock);

        $dataRows = [
            [
                'sku' => 'dummy-sku-1',
                'price' => 119.96,
                'category_url' => 'https://website/some-category-1'
            ]
        ];

        $this->mapperMock
            ->expects($this->exactly(count($productItems)))
            ->method('map')
            ->with($productItems[0])
            ->willReturn($dataRows);

        $this->arrayToXmlConverterMock
            ->expects($this->once())
            ->method('convert')
            ->willReturn('xml string');

        $fileWriterMock->expects($this->once())
            ->method('write')
            ->with('xml string')
            ->willReturn(true);

        $this->sut->execute($storeMock);
    }
}
