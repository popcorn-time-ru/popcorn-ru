<?php

namespace App\Command;

use App\Processors\ShowTorrentProcessor;
use App\Processors\SyncProcessor;
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
    name: 'test:sync',
    description: 'Test sync',
)]
class TestSyncCommand extends Command
{
    #[Required] public SyncProcessor $processor;

    protected function configure()
    {
        $this
            ->addArgument('type', InputArgument::REQUIRED, 'Type')
            ->addArgument('id', InputArgument::REQUIRED, 'Id')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->processor->process(
            new AmqpMessage(json_encode([
                'type' => $input->getArgument('type'),
                'id' => $input->getArgument('id'),
            ])),
            new NullContext()
        );

        return 0;
    }
}
