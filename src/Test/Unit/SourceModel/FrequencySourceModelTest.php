<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\SourceModel;

use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\SourceModel\FrequencySourceModel;

final class FrequencySourceModelTest extends TestCase
{
    /**
     * System under Test
     *
     * @var FrequencySourceModel $sut
     */
    private FrequencySourceModel $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new FrequencySourceModel();
    }

    public function test_it_should_get_named_cron_schedules(): void
    {
        $actual = $this->sut->toOptionArray();
        $expected = [
            '* * * * *' => 'Every Minute',
            '*/5 * * * *' => 'Every Five Minutes',
            '*/10 * * * *' => 'Every 10 Minutes',
            '*/15 * * * *' => 'Every 15 Minutes',
            '*/30 * * * *' => 'Every 30 Minutes',
            '0 * * * *' => 'Every Hour',
            '0 */2 * * *' => 'Every Two Hours',
            '0 */6 * * *' => 'Every Six Hours',
            '0 */12 * * *' => 'Every 12 Hours',
            '*/5 9-17 * * *' => 'During the Work Day',
            '0 0 * * *' => 'Every day at Midnight',
        ];

        $this->assertEquals($expected, $actual);
    }
}
