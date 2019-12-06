<?php

namespace App\Controller;

use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use App\Repository\TorrentRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MetricsController
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

    /**
     * @Route(path="/metrics")
     */
    public function index()
    {
        $content = '';
        $content.= '# Help app_movies_all All Films count'.PHP_EOL;
        $content.= '# TYPE app_movies_all gauge'.PHP_EOL;
        $content.= 'app_movies_all '.$this->movie->count([]).PHP_EOL;

        $content.= '# Help app_shows_all All Shows count'.PHP_EOL;
        $content.= '# TYPE app_shows_all gauge'.PHP_EOL;
        $content.= 'app_shows_all '.$this->show->count([]).PHP_EOL;

        $content.= '# Help app_torrent_all All Torrents count'.PHP_EOL;
        $content.= '# TYPE app_torrent_all gauge'.PHP_EOL;
        $content.= 'app_torrent_all '.$this->torrent->count([]).PHP_EOL;

        $content.= '# Help app_torrent Torrents by provider count'.PHP_EOL;
        $content.= '# TYPE app_torrent gauge'.PHP_EOL;
        foreach($this->torrent->getStatByProvider() as $provider => $count) {
            $content .= 'app_torrent{provider="'.$provider.'"} ' . $count . PHP_EOL;
        }
        return new Response($content, 200, ['Content-Type' => 'text/plain']);
    }
}
