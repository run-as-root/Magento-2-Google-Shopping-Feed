<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Cron;

use RunAsRoot\GoogleShoppingFeed\Exception\GenerateFeedForStoreException;
use RunAsRoot\GoogleShoppingFeed\Service\GenerateFeedService;

class FeedExporterCron
{
    private GenerateFeedService $generateFeedService;

    public function __construct(GenerateFeedService $generateFeedService)
    {
        $this->generateFeedService = $generateFeedService;
    }

    /**
     * @throws GenerateFeedForStoreException
     */
    public function run(): void
    {
        $this->generateFeedService->execute();
    }
}
