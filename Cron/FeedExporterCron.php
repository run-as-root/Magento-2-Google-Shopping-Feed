<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Cron;

use RunAsRoot\Feed\Exception\GenerateFeedForStoreException;
use RunAsRoot\Feed\Service\GenerateFeedService;

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
