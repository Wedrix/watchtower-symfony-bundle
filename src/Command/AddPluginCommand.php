<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Wedrix\Watchtower\Console as WatchtowerConsole;

#[AsCommand(name:'watchtower:plugins:add', description:'Generates a plugin file.')]
class AddPluginCommand extends Command
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
        $pluginType = (function () use ($input, $output): string {
            $helper = $this->getHelper('question');

            if (!$helper instanceof QuestionHelper) {
                throw new \Exception("Instance of ".QuestionHelper::class." expected, ".get_class($helper)." given.");
            }

            return $helper->ask($input, $output, new ChoiceQuestion(
                question: "What's the plugin type? ",
                choices: [
                    'constraint',
                    'root_constraint',
                    'filter',
                    'ordering',
                    'selector',
                    'resolver',
                    'authorizor',
                    'root_authorizor',
                    'mutation',
                    'subscription',
                ],
            ));
        })();

        if ($pluginType === 'constraint') {
            $this->watchtowerConsole
                ->addConstraintPlugin(
                    (function () use ($input, $output): string {
                        $helper = $this->getHelper('question');

                        if (!$helper instanceof QuestionHelper) {
                            throw new \Exception("Instance of ".QuestionHelper::class." expected, ".get_class($helper)." given.");
                        }

                        return $helper->ask($input, $output, new Question(
                            question: "What's the node type? "
                        ));
                    })()
                );
        }

        if ($pluginType === 'root_constraint') {
            $this->watchtowerConsole->addRootConstraintPlugin();
        }

        if ($pluginType === 'filter') {
            $this->watchtowerConsole
                ->addFilterPlugin(
                    (function () use ($input, $output): string {
                        $helper = $this->getHelper('question');

                        if (!$helper instanceof QuestionHelper) {
                            throw new \Exception("Instance of ".QuestionHelper::class." expected, ".get_class($helper)." given.");
                        }

                        return $helper->ask($input, $output, new Question(
                            question: "What's the node type? "
                        ));
                    })(),
                    (function () use ($input, $output): string {
                        $helper = $this->getHelper('question');
        
                        if (!$helper instanceof QuestionHelper) {
                            throw new \Exception("Instance of ".QuestionHelper::class." expected, ".get_class($helper)." given.");
                        }
            
                        return $helper->ask($input, $output, new Question(
                            question: "What's the filter name? "
                        ));
                    })()
                );
        }

        if ($pluginType === 'ordering') {
            $this->watchtowerConsole
                ->addOrderingPlugin(
                    (function () use ($input, $output): string {
                        $helper = $this->getHelper('question');

                        if (!$helper instanceof QuestionHelper) {
                            throw new \Exception("Instance of ".QuestionHelper::class." expected, ".get_class($helper)." given.");
                        }

                        return $helper->ask($input, $output, new Question(
                            question: "What's the node type? "
                        ));
                    })(),
                    (function () use ($input, $output): string {
                        $helper = $this->getHelper('question');
        
                        if (!$helper instanceof QuestionHelper) {
                            throw new \Exception("Instance of ".QuestionHelper::class." expected, ".get_class($helper)." given.");
                        }
            
                        return $helper->ask($input, $output, new Question(
                            question: "What's the ordering name? "
                        ));
                    })()
                );
        }

        if ($pluginType === 'selector') {
            $this->watchtowerConsole
                ->addSelectorPlugin(
                    (function () use ($input, $output): string {
                        $helper = $this->getHelper('question');

                        if (!$helper instanceof QuestionHelper) {
                            throw new \Exception("Instance of ".QuestionHelper::class." expected, ".get_class($helper)." given.");
                        }

                        return $helper->ask($input, $output, new Question(
                            question: "What's the node type? "
                        ));
                    })(),
                    (function () use ($input, $output): string {
                        $helper = $this->getHelper('question');

                        if (!$helper instanceof QuestionHelper) {
                            throw new \Exception("Instance of ".QuestionHelper::class." expected, ".get_class($helper)." given.");
                        }

                        return $helper->ask($input, $output, new Question(
                            question: "What's the field name? "
                        ));
                    })()
                );
        }

        if ($pluginType === 'resolver') {
            $this->watchtowerConsole
                ->addResolverPlugin(
                    (function () use ($input, $output): string {
                        $helper = $this->getHelper('question');

                        if (!$helper instanceof QuestionHelper) {
                            throw new \Exception("Instance of ".QuestionHelper::class." expected, ".get_class($helper)." given.");
                        }

                        return $helper->ask($input, $output, new Question(
                            question: "What's the node type? "
                        ));
                    })(),
                    (function () use ($input, $output): string {
                        $helper = $this->getHelper('question');

                        if (!$helper instanceof QuestionHelper) {
                            throw new \Exception("Instance of ".QuestionHelper::class." expected, ".get_class($helper)." given.");
                        }

                        return $helper->ask($input, $output, new Question(
                            question: "What's the field name? "
                        ));
                    })()
                );
        }

        if ($pluginType === 'authorizor') {
            $this->watchtowerConsole
                ->addAuthorizorPlugin(
                    $nodeType = (function () use ($input, $output): string {
                        $helper = $this->getHelper('question');
        
                        if (!$helper instanceof QuestionHelper) {
                            throw new \Exception("Instance of ".QuestionHelper::class." expected, ".get_class($helper)." given.");
                        }
            
                        return $helper->ask($input, $output, new Question(
                            question: "What's the node type? "
                        ));
                    })(),
                    (function () use ($input, $output, $nodeType): bool {
                        $helper = $this->getHelper('question');
        
                        if (!$helper instanceof QuestionHelper) {
                            throw new \Exception("Instance of ".QuestionHelper::class." expected, ".get_class($helper)." given.");
                        }
            
                        return $helper->ask($input, $output, new ConfirmationQuestion(
                            question: "Is the authorizor for collections of $nodeType? "
                        ));
                    })()
                );
        }

        if ($pluginType === 'root_authorizor') {
            $this->watchtowerConsole->addRootAuthorizorPlugin();
        }

        if ($pluginType === 'mutation') {
            $this->watchtowerConsole
                ->addMutationPlugin(
                    (function () use ($input, $output): string {
                        $helper = $this->getHelper('question');

                        if (!$helper instanceof QuestionHelper) {
                            throw new \Exception("Instance of ".QuestionHelper::class." expected, ".get_class($helper)." given.");
                        }

                        return $helper->ask($input, $output, new Question(
                            question: "What's the field name? "
                        ));
                    })()
                );
        }

        if ($pluginType === 'subscription') {
            $this->watchtowerConsole
                ->addSubscriptionPlugin(
                    (function () use ($input, $output): string {
                        $helper = $this->getHelper('question');

                        if (!$helper instanceof QuestionHelper) {
                            throw new \Exception("Instance of ".QuestionHelper::class." expected, ".get_class($helper)." given.");
                        }

                        return $helper->ask($input, $output, new Question(
                            question: "What's the field name? "
                        ));
                    })()
                );
        }

        return Command::SUCCESS;
    }
}
