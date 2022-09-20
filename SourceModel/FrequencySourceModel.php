<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\SourceModel;

use Magento\Framework\Data\OptionSourceInterface;

class FrequencySourceModel implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
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
    }
}
