<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\CollectionProvider;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\CollectionProvider\ConfigurableChildrenCollectionProvider;

final class ConfigurableChildrenCollectionProviderTest extends TestCase
{
    public function testLoadsAllAttributesAndMediaGalleryOfChildrenInStore(): void
    {
        $calls = [];
        $collection = $this->createMock(ProductCollection::class);

        foreach ([ 'addStoreFilter', 'addIdFilter', 'addAttributeToSelect', 'addMediaGalleryData' ] as $method) {
            $collection->method($method)->willReturnCallback(
                static function (...$arguments) use (&$calls, $method, $collection) {
                    $calls[] = array_merge([ $method ], $arguments);

                    return $collection;
                }
            );
        }

        $productCollectionFactory = $this->createMock(ProductCollectionFactory::class);
        $productCollectionFactory->method('create')->willReturn($collection);
        $sut = new ConfigurableChildrenCollectionProvider($productCollectionFactory);

        $this->assertSame($collection, $sut->get([ 5, 6 ], 3));
        $this->assertSame(
            [
                [ 'addStoreFilter', 3 ],
                [ 'addIdFilter', [ 5, 6 ], false ],
                [ 'addAttributeToSelect', '*', false ],
                [ 'addMediaGalleryData' ],
            ],
            $calls
        );
    }
}
