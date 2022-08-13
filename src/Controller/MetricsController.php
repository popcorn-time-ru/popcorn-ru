<?php

namespace App\Controller;

use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use App\Repository\TorrentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Prometheus\CollectorRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MetricsController
{
    /** @required */
    public ShowRepository $show;

    /** @required */
    public MovieRepository $movie;

    /** @required */
    public TorrentRepository $torrent;

    /** @required */
    public EntityManagerInterface $em;

    /** @required */
    public CollectorRegistry $cr;

    /**
     * @Route(path="/metrics-old")
     */
    public function index()
    {
        $g = $this->cr->getOrRegisterGauge('test', 'xxx_yyy', 'help', ['label1', 'label2']);
        $g->set(random_int(1,10), ['xxx', 'yyy']);

        return new Response('', 200, ['Content-Type' => 'text/plain']);

        $content = '';
        $content.= '# Help app_movies_all All Films count'.PHP_EOL;
        $content.= '# TYPE app_movies_all gauge'.PHP_EOL;
        $content.= 'app_movies_all '.$this->movie->count([]).PHP_EOL;

        $content.= '# Help app_shows_all All Shows count'.PHP_EOL;
        $content.= '# TYPE app_shows_all gauge'.PHP_EOL;
        $content.= 'app_shows_all '.$this->show->count([]).PHP_EOL;

        $content.= '# Help app_torrent_active Active Torrents count'.PHP_EOL;
        $content.= '# TYPE app_torrent_active gauge'.PHP_EOL;
        $content.= 'app_torrent_active '.$this->torrent->count(['active' => true]).PHP_EOL;

        $content.= '# Help app_torrent_inactive Inactive Torrents count'.PHP_EOL;
        $content.= '# TYPE app_torrent_inactive gauge'.PHP_EOL;
        $content.= 'app_torrent_inactive '.$this->torrent->count(['active' => false]).PHP_EOL;

        $content.= '# Help app_torrent Torrents by provider count'.PHP_EOL;
        $content.= '# TYPE app_torrent gauge'.PHP_EOL;
        foreach($this->torrent->getStatByProvider() as $provider => $count) {
            $content .= 'app_torrent{provider="'.$provider.'"} ' . $count . PHP_EOL;
        }

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('topic', 'topic');
        $rsm->addScalarResult('spider', 'spider');
        $rsm->addScalarResult('type', 'type');
        $rsm->addScalarResult('c', 'count');
        $result = $this->em->createNativeQuery(
        "select 
            count(*) c, 
            json_extract(properties, '$.\"enqueue.topic\"') topic, 
            json_extract(body, '$.spider') spider,
            json_extract(body, '$.type') type
            from enqueue 
            group by topic, spider, type",
            $rsm
        )->getArrayResult();

        $summ = 0;
        $content.= '# Help app_queue Len of queue'.PHP_EOL;
        $content.= '# TYPE app_queue gauge'.PHP_EOL;
        foreach ($result as $row) {
            $topic = trim($row['topic'],'"');
            $type = trim($row['spider'],'"') ?: trim($row['type'],'"');
            $content .= 'app_queue{topic="'.$topic.'", type="'.$type.'"} ' . $row['count'] . PHP_EOL;
            $summ += $row['count'];
        }

        $content.= '# Help app_queue_all All Queue count'.PHP_EOL;
        $content.= '# TYPE app_queue_all gauge'.PHP_EOL;
        $content.= 'app_queue_all '.$summ.PHP_EOL;

        return new Response($content, 200, ['Content-Type' => 'text/plain']);
    }
}
