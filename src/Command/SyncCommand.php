<?php

namespace App\Command;

use App\Processors\ForumProcessor;
use App\Processors\SyncProcessor;
use App\Repository\AnimeRepository;
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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCommand extends Command
{
    protected static $defaultName = 'update:syncOld';

    /** @required */
    public ProducerInterface $producer;

    /** @required */
    public TorrentRepository $torrentRepository;

    /** @required */
    public MovieRepository $movieRepository;

    /** @required */
    public ShowRepository $showRepository;

    /** @required */
    public LoggerInterface $logger;

    protected function configure()
    {
        // 180 дней по 200 каждые 4 часа (???)
        $this
            ->setDescription('Generate task for sync old records')
            ->addOption('days-check', 'c', InputOption::VALUE_REQUIRED, 'Days for check')
            ->addOption('days-delete', 'd', InputOption::VALUE_REQUIRED, 'Days for delete')
            ->addArgument('limit', InputArgument::REQUIRED, 'Limit')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateDelete = new \DateTime($input->getOption('days-delete') . ' days ago');
        $dateCheck = new \DateTime($input->getOption('days-check') . ' days ago');
        $limit = $input->getArgument('limit');

        $torrentsDelete = $this->torrentRepository->getNotSyncAndInactive($dateDelete, $limit);
        foreach ($torrentsDelete as $torrent) {
            $this->sendDelayed(
                new Message(json_encode(['type' => 'torrent', 'id' => $torrent->getId()->toString(), 'delete' => $torrent->isChecked($dateCheck)]))
            );
        }

        $torrents = $this->torrentRepository->getOld($dateCheck, $limit);

        $this->logger->info('Update old torrents', ['count' => count($torrents)]);
        foreach ($torrents as $torrent) {
            $this->sendDelayed(
                new Message(json_encode(['type' => 'torrent', 'id' => $torrent->getId()->toString()]))
            );
        }

        $shows = $this->showRepository->getOld($dateCheck, $limit);

        $this->logger->info('Update old shows', ['count' => count($shows)]);
        foreach ($shows as $show) {
            $this->sendDelayed(
                new Message(json_encode(['type' => 'show', 'id' => $show->getId()->toString()]))
            );
        }

        $movies = $this->movieRepository->getOld($dateCheck, $limit);

        $this->logger->info('Update old movies', ['count' => count($movies)]);
        foreach ($movies as $movie) {
            $this->sendDelayed(
                new Message(json_encode(['type' => 'movie', 'id' => $movie->getId()->toString()]))
            );
        }

        $animes = $this->animeRepository->getOld($dateCheck, $limit);

        $this->logger->info('Update old animes', ['count' => count($animes)]);
        foreach ($animes as $anime) {
            $this->sendDelayed(
                new Message(json_encode(['type' => 'anime', 'id' => $anime->getId()->toString()]))
            );
        }

        return 0;
    }

    private function sendDelayed(Message $message)
    {
        $message->setDelay(random_int(120, 3600));
        $this->producer->sendEvent(
            SyncProcessor::TOPIC,
            $message
        );
    }
}

