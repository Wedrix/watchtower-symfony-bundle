<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Wedrix\Watchtower\Console as WatchtowerConsole;

final class ListScalarTypeDefinitionsCommand extends Command
{
    protected static $defaultName = 'watchtower:scalar-type-definitions:list';

    protected static $defaultDescription = 'Lists all the project\'s scalar type definitions.';

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
        if (!$output instanceof ConsoleOutputInterface) {
            throw new \LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        }

        if (iterator_count($this->watchtowerConsole->scalarTypeDefinitions()) > 0) {
            $styledOutput = new SymfonyStyle($input, $output);
    
            $styledOutput->table(
                ['<comment>Type Name</comment>', '<comment>File</comment>'],
                (function (): array {
                    $results = [];
    
                    foreach ($this->watchtowerConsole->scalarTypeDefinitions() as $scalarTypeDefinition) {
                        $results[] = [
                            $scalarTypeDefinition->typeName(),
                            $this->watchtowerConsole->scalarTypeDefinitions()->filePath($scalarTypeDefinition)
                        ];
                    }
    
                    return $results;
                })()
            );
        }
        else {
            $output->writeln('<info>You have no scalar type definitions.</info>');
        }

        return Command::SUCCESS;
    }
}
