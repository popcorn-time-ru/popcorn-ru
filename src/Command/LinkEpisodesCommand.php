<?php

namespace App\Command;

use App\Processors\ForumProcessor;
use App\Processors\TorrentFilesLinkProcessor;
use App\Processors\SyncProcessor;
use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use App\Repository\TorrentRepository;
use App\Service\SpiderSelector;
use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LinkEpisodesCommand extends Command
{
    protected static $defaultName = 'link:episodes';

    /** @required */
    public ProducerInterface $producer;

    /** @required */
    public TorrentRepository $torrentRepository;

    protected function configure()
    {
        $this
            ->setDescription('Try Link unlinked show torrents')
            ->addArgument('limit', InputArgument::REQUIRED, 'Limit')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = $input->getArgument('limit');
        $torrents = $this->torrentRepository->getUnlinkedShowTorrents($limit);
        foreach ($torrents as $torrent) {
            $this->sendDelayed(
                new Message(json_encode(['torrentId' => $torrent]))
            );
        }

        return 0;
    }

    private function sendDelayed(Message $message)
    {
        //$message->setDelay(random_int(120, 3600));
        $this->producer->sendEvent(
            TorrentFilesLinkProcessor::TOPIC,
            $message
        );
    }
}

