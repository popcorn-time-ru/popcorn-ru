<?php

namespace App\Command;

use App\Processors\DownloadTorrentProcessor;
use App\Processors\ShowTorrentProcessor;
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
    name: 'test:download',
    description: 'Download torrent file',
)]
class TestDownloadCommand extends Command
{
    #[Required] public DownloadTorrentProcessor $processor;

    protected function configure()
    {
        $this
            ->addArgument('spider', InputArgument::REQUIRED, 'Spider')
            ->addArgument('id', InputArgument::REQUIRED, 'Id')
            ->addArgument('downloadId', InputArgument::REQUIRED, 'Id')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->processor->process(
            new AmqpMessage(json_encode([
                'spider' => $input->getArgument('spider'),
                'torrentId' => $input->getArgument('id'),
                'downloadId' => $input->getArgument('downloadId'),
            ])),
            new NullContext()
        );

        return 0;
    }
}
