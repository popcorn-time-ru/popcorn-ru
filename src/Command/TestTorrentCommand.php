<?php

namespace App\Command;

use App\Processors\TorrentFilesLinkProcessor;
use App\Processors\TopicProcessor;
use App\Service\MediaService;
use Enqueue\Null\NullContext;
use Interop\Amqp\Impl\AmqpMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Service\Attribute\Required;

class TestTorrentCommand extends Command
{
    protected static $defaultName = 'test:torrent';

    #[Required] private TorrentFilesLinkProcessor $processor;

    protected function configure()
    {
        $this
            ->setDescription('Link files to episodes')
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
