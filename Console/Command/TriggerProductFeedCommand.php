<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use RunAsRoot\Feed\Service\GenerateFeedService;

class TriggerProductFeedCommand extends Command
{
    public const COMMAND_NAME = 'run_as_root:product-feed:execute';
    public const COMMAND_DESCRIPTION = 'Runs feed generation for all store views.';

    private GenerateFeedService $generateFeedService;
    private State $state;

    public function __construct(
        State $state,
        GenerateFeedService $generateFeedService,
        string $name = null
    ) {
        $this->state = $state;
        $this->generateFeedService = $generateFeedService;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription(self::COMMAND_DESCRIPTION);
    }

    /**
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Starting Product Feed');

        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        $this->generateFeedService->execute();

        $io->success('Product feed exported');
        return Cli::RETURN_SUCCESS;
    }
}
