<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wedrix\Watchtower\Console as WatchtowerConsole;

#[AsCommand(name:'watchtower:cache:generate', description:'Generate the updated cache.')]
class GenerateCacheCommand extends Command
{
    public function __construct(
        protected WatchtowerConsole $watchtowerConsole
    )
    {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int
    {
        $output->writeln("<info>Generating Cache ...</info>");

        $this->watchtowerConsole
            ->generateCache();
        
        $output->writeln("<info>Done!</info>");
            
        return Command::SUCCESS;
    }
}
