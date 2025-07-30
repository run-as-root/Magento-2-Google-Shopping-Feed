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

        $emulationStartInvokedCount = $this->exactly(count($storeIds));
        $this->emulation->expects($emulationStartInvokedCount)
            ->method('startEnvironmentEmulation')
            ->willReturnCallback(function ($storeId) use ($storeIds, $emulationStartInvokedCount) {
                $this->assertEquals($storeIds[$emulationStartInvokedCount->numberOfInvocations() - 1], $storeId);
            });

        $feedGenerateInvokedCount = $this->exactly(count($stores));
        $this->generateFeedForStore->expects($feedGenerateInvokedCount)
            ->method('execute')
            ->willReturnCallback(function ($store) use ($stores, $feedGenerateInvokedCount) {
                $this->assertEquals($stores[$feedGenerateInvokedCount->numberOfInvocations() - 1], $store);
            });

        $registryCleanInvokedCount = $this->exactly(count($stores));
        $this->registry->expects($registryCleanInvokedCount)
            ->method('cleanForStore')
            ->willReturnCallback(function ($storeId) use ($storeIds, $registryCleanInvokedCount) {
                $this->assertEquals($storeIds[$registryCleanInvokedCount->numberOfInvocations() - 1], $storeId);
            });

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

        $emulationStartInvokedCount = $this->exactly(2);
        $this->emulation->expects($emulationStartInvokedCount)
            ->method('startEnvironmentEmulation')
            ->willReturnCallback(function ($storeId) use ($storeIds, $emulationStartInvokedCount) {
                $this->assertEquals($storeIds[$emulationStartInvokedCount->numberOfInvocations() - 1], $storeId);
            });

        $feedGenerateInvokedCount = $this->exactly(2);
        $this->generateFeedForStore->expects($feedGenerateInvokedCount)
            ->method('execute')
            ->willReturnCallback(function ($store) use ($stores, $feedGenerateInvokedCount) {
                return match ($feedGenerateInvokedCount->numberOfInvocations()) {
                    1 => $this->assertEquals($stores[0], $store) ?: null,
                    2 => $this->assertEquals($stores[1], $store) 
                         ?: throw new HandlerIsNotSpecifiedException(
                             __('Handler should be specified for each attribute.')
                         ),
                };
            });

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

        $emulationStartInvokedCount = $this->exactly(2);
        $this->emulation->expects($emulationStartInvokedCount)
            ->method('startEnvironmentEmulation')
            ->willReturnCallback(function ($storeId) use ($storeIds, $emulationStartInvokedCount) {
                $this->assertEquals($storeIds[$emulationStartInvokedCount->numberOfInvocations() - 1], $storeId);
            });

        $feedGenerateInvokedCount = $this->exactly(2);
        $this->generateFeedForStore->expects($feedGenerateInvokedCount)
            ->method('execute')
            ->willReturnCallback(function ($store) use ($stores, $feedGenerateInvokedCount) {
                return match ($feedGenerateInvokedCount->numberOfInvocations()) {
                    1 => $this->assertEquals($stores[0], $store) ?: null,
                    2 => $this->assertEquals($stores[1], $store) 
                         ?: throw new WrongInstanceException(
                             __('Class should implement AttributeHandlerInterface interface.')
                         ),
                };
            });

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
