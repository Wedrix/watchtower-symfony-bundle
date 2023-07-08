<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wedrix\Watchtower\Console as WatchtowerConsole;

final class GenerateSchemaCommand extends Command
{
    protected static $defaultName = 'watchtower:schema:generate';

    protected static $defaultDescription = 'Generate the schema file.';

    public function __construct(
        protected readonly WatchtowerConsole $watchtowerConsole
    )
    {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int
    {
        $output->writeln("<info>Generating Schema ...</info>");

        $this->watchtowerConsole
            ->generateSchema();
        
        $output->writeln("<info>Done!</info>");
            
        return Command::SUCCESS;
    }
}
