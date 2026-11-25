<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\CollectionProvider;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\CollectionProvider\ExportableConfigurableParentsCollectionProvider;

final class ExportableConfigurableParentsCollectionProviderTest extends TestCase
{
    public function testFiltersEnabledVisibleConfigurablesOfTheStore(): void
    {
        $calls = [];
        $collection = $this->createMock(ProductCollection::class);

        foreach ([ 'addStoreFilter', 'addAttributeToFilter' ] as $method) {
            $collection->method($method)->willReturnCallback(
                static function (...$arguments) use (&$calls, $method, $collection) {
                    $calls[] = array_merge([ $method ], array_slice($arguments, 0, 2));

                    return $collection;
                }
            );
        }

        $productCollectionFactory = $this->createMock(ProductCollectionFactory::class);
        $productCollectionFactory->method('create')->willReturn($collection);
        $sut = new ExportableConfigurableParentsCollectionProvider($productCollectionFactory);

        $this->assertSame($collection, $sut->get(3));
        $this->assertSame(
            [
                [ 'addStoreFilter', 3 ],
                [ 'addAttributeToFilter', 'type_id', [ 'eq' => Configurable::TYPE_CODE ] ],
                [ 'addAttributeToFilter', 'status', [ 'eq' => Status::STATUS_ENABLED ] ],
                [ 'addAttributeToFilter', 'visibility', [ 'neq' => Visibility::VISIBILITY_NOT_VISIBLE ] ],
            ],
            $calls
        );
    }
}
