<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\Cron;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\Cron\FeedExporterCron;
use RunAsRoot\Feed\Exception\GenerateFeedForStoreException;
use RunAsRoot\Feed\Service\GenerateFeedService;

final class FeedExporterCronTest extends TestCase
{
    private FeedExporterCron $sut;

    protected function setUp(): void
    {
        /** @var GenerateFeedService | MockObject $generateFeedServiceMock */
        $generateFeedServiceMock = $this->createMock(GenerateFeedService::class);
        $generateFeedServiceMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new GenerateFeedForStoreException(__('Test Exception thrown')));

        $this->sut = new FeedExporterCron($generateFeedServiceMock);
    }

    public function test_it_should_start_feed_generator(): void
    {
        $this->expectException(GenerateFeedForStoreException::class);
        $this->expectExceptionMessage('Test Exception thrown');
        $this->sut->run();
    }
}
