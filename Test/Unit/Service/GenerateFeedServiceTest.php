<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\Service;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\Exception\HandlerIsNotSpecifiedException;
use RunAsRoot\GoogleShoppingFeed\Exception\WrongInstanceException;
use RunAsRoot\GoogleShoppingFeed\Registry\FeedRegistry;
use RunAsRoot\GoogleShoppingFeed\Service\GenerateFeedForStore;
use RunAsRoot\GoogleShoppingFeed\Service\GenerateFeedService;

final class GenerateFeedServiceTest extends TestCase
{
    /** @var StoreManagerInterface|MockObject */
    private $storeManager;
    /** @var GenerateFeedForStore|MockObject */
    private $generateFeedForStore;
    /** @var FeedRegistry|MockObject */
    private FeedRegistry $registry;
    /** @var Emulation|MockObject */
    private Emulation $emulation;

    private GenerateFeedService $sut;

    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->generateFeedForStore = $this->createMock(GenerateFeedForStore::class);
        $this->registry = $this->createMock(FeedRegistry::class);
        $this->emulation = $this->createMock(Emulation::class);

        $this->sut = new GenerateFeedService(
            $this->storeManager,
            $this->generateFeedForStore,
            $this->registry,
            $this->emulation
        );
    }

    /**
     * @dataProvider storesDataProvider
     */
    public function testSuccessfulFeedGeneration(array $stores, array $storeIds): void
    {
        $this->storeManager->expects($this->once())
            ->method('getStores')
            ->willReturn($stores);

        $this->emulation->expects($this->exactly(count($storeIds)))
            ->method('startEnvironmentEmulation')
            ->withConsecutive(...array_map(static function ($id): array {
                return [ $id ];
            }, $storeIds));

        $this->generateFeedForStore->expects($this->exactly(count($stores)))
            ->method('execute')
            ->withConsecutive(...array_map(static function ($store): array {
                return [$store];
            }, $stores));

        $this->registry->expects($this->exactly(count($stores)))
            ->method('cleanForStore')
            ->withConsecutive(...array_map(static function ($storeId): array {
                return [$storeId];
            }, $storeIds));

        $this->emulation->expects($this->exactly(count($storeIds)))
            ->method('stopEnvironmentEmulation');

        $this->sut->execute();
    }

    /**
     * @dataProvider storesDataProvider
     */
    public function testHandlerIsNotSpecifiedExceptionIsThrown(array $stores, array $storeIds): void
    {
        $this->storeManager->expects($this->once())
            ->method('getStores')
            ->willReturn($stores);

        $this->emulation->expects($this->exactly(2))
            ->method('startEnvironmentEmulation')
            ->withConsecutive(...array_map(static function ($id): array {
                return [ $id ];
            }, $storeIds));

        $this->generateFeedForStore->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(...array_map(static function ($store): array {
                return [$store];
            }, $stores))
            ->willReturnOnConsecutiveCalls(
              null,
              $this->throwException(new HandlerIsNotSpecifiedException(
                  __('Handler should be specified for each attribute.')
              ))
        );

        $this->emulation->expects($this->once())
            ->method('stopEnvironmentEmulation');

        $this->registry->expects($this->once())
            ->method('cleanForStore')
            ->with(reset($storeIds));

        $this->expectException(HandlerIsNotSpecifiedException::class);

        $this->sut->execute();
    }

    /**
     * @dataProvider storesDataProvider
     */
    public function testWrongInstanceExceptionIsThrown(array $stores, array $storeIds): void
    {
        $this->storeManager->expects($this->once())
            ->method('getStores')
            ->willReturn($stores);

        $this->emulation->expects($this->exactly(2))
            ->method('startEnvironmentEmulation')
            ->withConsecutive(...array_map(static function ($id): array {
                return [ $id ];
            }, $storeIds));

        $this->generateFeedForStore->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(...array_map(static function ($store): array {
                return [ $store ];
            }, $stores))
            ->willReturnOnConsecutiveCalls(
                null,
                $this->throwException(new WrongInstanceException(
                    __('Class should implement AttributeHandlerInterface interface.')
                ))
            );

        $this->registry->expects($this->once())
            ->method('cleanForStore')
            ->with(reset($storeIds));

        $this->emulation->expects($this->once())
            ->method('stopEnvironmentEmulation');

        $this->expectException(WrongInstanceException::class);

        $this->sut->execute();
    }

    public function storesDataProvider(): array
    {
        $getStoreMock = function (int $storeId): MockObject {
            $store = $this->createMock(StoreInterface::class);
            $store->method('getId')->willReturn($storeId);
            return $store;
        };

        $storeIds = [ 1, 2, 3 ];
        $stores = [];
        foreach ($storeIds as $id) {
            $stores[] = $getStoreMock($id);
        }

        return [
            [
                'stores' => $stores,
                'storeIds' => $storeIds,
            ]
        ];
    }
}
