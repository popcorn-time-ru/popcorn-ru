<?php

namespace App\Command;

use App\Processors\ShowTorrentProcessor;
use App\Processors\TopicProcessor;
use App\Processors\TorrentActiveProcessor;
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
    name: 'test:active',
    description: 'Link files to episodes',
)]
class TestActiveCommand extends Command
{
    #[Required] public TorrentActiveProcessor $processor;

    protected function configure()
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'Id')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->processor->process(
            new AmqpMessage(json_encode([
                'torrentId' => $input->getArgument('id'),
            ])),
            new NullContext()
        );

        return 0;
    }
}
