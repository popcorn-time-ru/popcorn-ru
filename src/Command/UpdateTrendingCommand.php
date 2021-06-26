<?php

namespace App\Command;

use App\Entity\BaseMedia;
use App\Entity\Movie;
use App\Entity\Show;
use App\Processors\TorrentActiveProcessor;
use App\Processors\WatcherProcessor;
use App\Repository\MediaRepository;
use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateTrendingCommand extends Command
{
    protected static $defaultName = 'update:trending';

    private MovieRepository $movieRepository;

    private ShowRepository $showRepository;

    private \App\Traktor\Client $trakt;

    private ProducerInterface $producer;

    /**
     * @param MovieRepository $movieRepository
     * @param ShowRepository  $showRepository
     * @param \App\Traktor\Client $trakt
     */
    public function __construct(ProducerInterface $producer, MovieRepository $movieRepository, ShowRepository $showRepository, \App\Traktor\Client $trakt)
    {
        parent::__construct();
        $this->movieRepository = $movieRepository;
        $this->showRepository = $showRepository;
        $this->producer = $producer;
        $this->trakt = $trakt;
    }

    protected function configure()
    {
        $this
            ->setDescription('Update Trending from Trakt.tv')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->updateMovies();
        $this->updateShows();
        return 0;
    }

    protected function updateMovies()
    {
        $current = $this->trakt->get('movies/trending', ['limit' => 1000]);
        $currentMap = [];
        foreach ($current as $info) {
            $currentMap[$info->movie->ids->imdb] = $info->watchers;
        }
        $this->update('movie', $this->movieRepository, $currentMap);
    }

    protected function updateShows()
    {
        $current = $this->trakt->get('shows/trending', ['limit' => 1000]);
        $currentMap = [];
        foreach ($current as $info) {
            $currentMap[$info->show->ids->imdb] = $info->watchers;
        }
        $this->update('show', $this->showRepository, $currentMap);
    }

    protected function update($type, MediaRepository $repository, $currentMap)
    {
        unset($currentMap['']);
        /** @var BaseMedia[] $old */
        $old = $repository->findWatching();
        foreach ($old as $media) {
            if (isset($currentMap[$media->getImdb()])) {
                $this->sendToUpdate($type, $media->getImdb(), $currentMap[$media->getImdb()]);
                unset ($currentMap[$media->getImdb()]);
            } else {
                $this->sendToUpdate($type, $media->getImdb(), 0);
            }
        }
        foreach ($currentMap as $imdb => $watchers) {
            $this->sendToUpdate($type, $imdb, $watchers);
        }
    }

    protected function sendToUpdate($type, $imdb, $watching)
    {
        $topicMessage = new \Enqueue\Client\Message(JSON::encode([
            'type' => $type,
            'imdb' => $imdb,
            'watching' => $watching,
        ]));
        $topicMessage->setDelay(random_int(120, 600));
        $this->producer->sendEvent(WatcherProcessor::TOPIC, $topicMessage);
    }
}
