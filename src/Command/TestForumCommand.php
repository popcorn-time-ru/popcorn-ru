<?php

namespace App\Command;

use App\Processors\ForumProcessor;
use App\Processors\TopicProcessor;
use App\Service\MediaService;
use Enqueue\Null\NullContext;
use Interop\Amqp\Impl\AmqpMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Service\Attribute\Required;

#[AsCommand(
    name: 'test:forum',
    description: 'Get list of topics from tracker',
)]
class TestForumCommand extends Command
{
    #[Required] public ForumProcessor $processor;

    protected function configure()
    {
        $this
            ->addArgument('spider', InputArgument::REQUIRED, 'Spider')
            ->addArgument('id', InputArgument::REQUIRED, 'Id')
            ->addArgument('page', InputArgument::OPTIONAL, 'page', 1)
            ->addOption('last', null, InputOption::VALUE_REQUIRED, 'Only last N hours')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->processor->process(
            new AmqpMessage(json_encode([
                'spider' => $input->getArgument('spider'),
                'forumId' => $input->getArgument('id'),
                'page' => $input->getArgument('page'),
                'last' => $input->getOption('last')
            ])),
            new NullContext()
        );

        return 0;
    }
}
