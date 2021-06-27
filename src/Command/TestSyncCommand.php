<?php

namespace App\Command;

use App\Processors\TorrentFilesLinkProcessor;
use App\Processors\SyncProcessor;
use App\Processors\TopicProcessor;
use App\Service\MediaService;
use Enqueue\Null\NullContext;
use Interop\Amqp\Impl\AmqpMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestSyncCommand extends Command
{
    protected static $defaultName = 'test:sync';

    /** @required */
    public SyncProcessor $processor;

    protected function configure()
    {
        $this
            ->setDescription('Test sync')
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
