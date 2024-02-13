<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wedrix\Watchtower\Console as WatchtowerConsole;

#[AsCommand(name:'watchtower:schema:update', description:'Update queries in the schema file to match the project\'s Doctrine models.')]
class UpdateSchemaCommand extends Command
{
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
        $this->watchtowerConsole
            ->updateSchema();
            
        return Command::SUCCESS;
    }
}
