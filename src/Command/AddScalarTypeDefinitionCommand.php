<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Wedrix\Watchtower\Console as WatchtowerConsole;

#[AsCommand(name:'watchtower:scalar-type-definitions:add', description:'Add a type definition for a custom scalar type.')]
class AddScalarTypeDefinitionCommand extends Command
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
        if (!$output instanceof ConsoleOutputInterface) {
            throw new \LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        }
        
        $typeName = (function () use ($input, $output): string {
            $helper = $this->getHelper('question');

            if (!$helper instanceof QuestionHelper) {
                throw new \Exception("Instance of ".QuestionHelper::class." expected, ".get_class($helper)." given.");
            }

            return $helper->ask($input, $output, new Question(
                question: "What is the custom scalar's name? "
            ));
        })();

        $this->watchtowerConsole
            ->addScalarTypeDefinition(
                typeName: $typeName
            );

        return Command::SUCCESS;
    }
}
