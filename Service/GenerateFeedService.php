<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Service;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use RunAsRoot\Feed\Registry\FeedRegistry;

use function gc_collect_cycles;

class GenerateFeedService
{
    private StoreManagerInterface $storeManager;
    private GenerateFeedForStore $generateFeedForStore;
    private FeedRegistry $registry;
    private Emulation $emulation;

    public function __construct(
        StoreManagerInterface $storeManager,
        GenerateFeedForStore $generateFeedForStore,
        FeedRegistry $registry,
        Emulation $emulation
    ) {
        $this->storeManager = $storeManager;
        $this->generateFeedForStore = $generateFeedForStore;
        $this->registry = $registry;
        $this->emulation = $emulation;
    }

    /**
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function execute(): void
    {
        foreach ($this->storeManager->getStores() as $store) {
            $this->emulation->startEnvironmentEmulation($store->getId());

            $this->generateFeedForStore->execute($store);
            $this->registry->cleanForStore((int)$store->getId());

            $this->emulation->stopEnvironmentEmulation();

            gc_collect_cycles();
        }
    }
}
