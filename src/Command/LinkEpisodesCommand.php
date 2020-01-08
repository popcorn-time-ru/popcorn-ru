<?php

namespace App\Command;

use App\Processors\ForumProcessor;
use App\Processors\ShowTorrentProducer;
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

    /** @var ProducerInterface */
    private $producer;

    /**
     * @var TorrentRepository
     */
    private $torrentRepository;

    /**
     * @var MovieRepository
     */
    private $movieRepository;

    /**
     * @var ShowRepository
     */
    private $showRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        TorrentRepository $torrentRepository,
        MovieRepository $movieRepository,
        ShowRepository $showRepository,
        ProducerInterface $producer,
        LoggerInterface $logger
        )
    {
        parent::__construct();
        $this->producer = $producer;
        $this->torrentRepository = $torrentRepository;
        $this->movieRepository = $movieRepository;
        $this->showRepository = $showRepository;
        $this->logger = $logger;
    }

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
        $message->setDelay(random_int(120, 3600));
        $this->producer->sendEvent(
            ShowTorrentProducer::TOPIC,
            $message
        );
    }
}

