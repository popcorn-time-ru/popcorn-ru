<?php

namespace App\Controller;

use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use App\Repository\TorrentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
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

    /** @var EntityManagerInterface */
    private $em;

    /**
     * DatabaseMetrics constructor.
     *
     * @param ShowRepository    $show
     * @param MovieRepository   $movie
     * @param TorrentRepository $torrent
     */
    public function __construct(
        EntityManagerInterface $em,
        ShowRepository $show,
        MovieRepository $movie,
        TorrentRepository $torrent
    )
    {
        $this->show = $show;
        $this->movie = $movie;
        $this->torrent = $torrent;
        $this->em = $em;
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

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('topic', 'topic');
        $rsm->addScalarResult('spider', 'spider');
        $rsm->addScalarResult('c', 'count');
        $result = $this->em->createNativeQuery(
        "select 
            count(*) c, 
            json_extract(properties, '$.\"enqueue.topic\"') topic, 
            json_extract(body, '$.spider') spider 
            from enqueue 
            group by topic, spider",
            $rsm
        )->getArrayResult();

        $summ = 0;
        $content.= '# Help app_queue Len of queue'.PHP_EOL;
        $content.= '# TYPE app_queue gauge'.PHP_EOL;
        foreach ($result as $row) {
            $topic = trim($row['topic'],'"');
            if ($row['spider']) {
                $spider = trim($row['spider'],'"');
                $content .= 'app_queue{topic="'.$topic.'", spider="'.$spider.'"} ' . $row['count'] . PHP_EOL;
            } else {
                $content .= 'app_queue{topic="'.$topic.'"} ' . $row['count'] . PHP_EOL;
            }
            $summ += $row['count'];
        }

        $content.= '# Help app_queue_all All Queue count'.PHP_EOL;
        $content.= '# TYPE app_queue_all gauge'.PHP_EOL;
        $content.= 'app_queue_all '.$summ.PHP_EOL;

        return new Response($content, 200, ['Content-Type' => 'text/plain']);
    }
}
