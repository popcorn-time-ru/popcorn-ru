<?php

namespace App\Command;

use App\Entity\BaseMedia;
use App\Entity\Movie;
use App\Entity\Show;
use App\Repository\MediaRepository;
use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateTrendingCommand extends Command
{
    protected static $defaultName = 'update:trending';

    /** @var MovieRepository */
    private $movieRepository;

    /** @var ShowRepository */
    private $showRepository;

    /** @var \Traktor\Client */
    private $trakt;

    /**
     * @param MovieRepository $movieRepository
     * @param ShowRepository  $showRepository
     * @param \Traktor\Client $trakt
     */
    public function __construct(MovieRepository $movieRepository, ShowRepository $showRepository, \Traktor\Client $trakt)
    {
        parent::__construct();
        $this->movieRepository = $movieRepository;
        $this->showRepository = $showRepository;
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
        $this->update($this->movieRepository, $currentMap);
    }

    protected function updateShows()
    {
        $current = $this->trakt->get('shows/trending', ['limit' => 1000]);
        $currentMap = [];
        foreach ($current as $info) {
            $currentMap[$info->show->ids->imdb] = $info->watchers;
        }
        $this->update($this->showRepository, $currentMap);
    }

    protected function update(MediaRepository $repository, $currentMap)
    {
        /** @var BaseMedia[] $old */
        $old = $repository->findWatching();
        foreach ($old as $media) {
            if (isset($currentMap[$media->getImdb()])) {
                $media->getRating()->setWatching($currentMap[$media->getImdb()]);
                unset ($currentMap[$media->getImdb()]);
            } else {
                $media->getRating()->setWatching(0);
            }
        }
        foreach ($currentMap as $imdb => $watchers) {
            $media = $repository->findByImdb($imdb);
            if ($media) {
                $media->getRating()->setWatching($watchers);
            }
        }
        $repository->flush();
    }
}
