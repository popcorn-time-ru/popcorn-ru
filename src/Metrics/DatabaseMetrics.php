<?php

namespace App\Metrics;

use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use App\Repository\TorrentRepository;
use PrismaMedia\Metrics\Metric;
use PrismaMedia\Metrics\MetricGenerator;

class DatabaseMetrics implements MetricGenerator
{
    /** @var ShowRepository */
    private $show;

    /** @var MovieRepository */
    private $movie;

    /** @var TorrentRepository */
    private $torrent;

    /**
     * DatabaseMetrics constructor.
     *
     * @param ShowRepository    $show
     * @param MovieRepository   $movie
     * @param TorrentRepository $torrent
     */
    public function __construct(
        ShowRepository $show,
        MovieRepository $movie,
        TorrentRepository $torrent
    )
    {
        $this->show = $show;
        $this->movie = $movie;
        $this->torrent = $torrent;
    }

    public function getMetrics(): \Traversable
    {
        yield new Metric('app_movies_all', $this->movie->count([]));
        yield new Metric('app_shows_all', $this->show->count([]));
        yield new Metric('app_torrent_all', $this->torrent->count([]));

        foreach($this->torrent->getStatByProvider() as $provider => $count) {
            yield new Metric('app_torrent', $count, ['provider' => $provider]);
        }
    }
}
