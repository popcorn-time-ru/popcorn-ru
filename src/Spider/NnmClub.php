<?php

namespace App\Spider;

use App\Entity\File;
use App\Entity\MovieTorrent;
use App\Service\TorrentService;
use App\Spider\Dto\ForumDto;
use App\Spider\Dto\TopicDto;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class NnmClub extends AbstractSpider
{
    public const BASE_URL = 'https://nnmclub.to/forum/';

    private const PAGE_SIZE = 50;

    /** @var Client */
    private $client;

    private $context;

    public function __construct(TorrentService $torrentService, LoggerInterface $logger)
    {
        parent::__construct($torrentService, $logger);
        $this->client = new Client([
            'base_uri' => self::BASE_URL,
            RequestOptions::TIMEOUT => 10,
        ]);
    }

    public function getForumKeys(): array
    {
        return [
            218, // Зарубежные Новинки (HD*Rip/LQ, DVDRip)
            225, // Зарубежные Фильмы (HD*Rip/LQ, DVDRip, SATRip, VHSRip)
            319, // Зарубежная Классика (HD*Rip/LQ, DVDRip, SATRip, VHSRip)

            768, // Зарубежные сериалы
        ];
    }

    public function getPage(ForumDto $forum): \Generator
    {
        $res = $this->client->get('viewforum.php', [
            'query' => [
                'f' => $forum->id,
                'start' => (($forum->page-1)*self::PAGE_SIZE),
            ]
        ]);
        $html = $res->getBody()->getContents();
        $crawler = new Crawler($html);

        /** @var Crawler $table */
        $table = $crawler->filter('table.forumline');
        $lines = array_filter(
            $table->filter('tr')->each(static function (Crawler $c) { return $c;}),
            function (Crawler $c) use ($forum){
                // показывает дочерние форумы на всех страницах, парсим только на первой
                if ($forum->page === 1) {
                    if (strpos($c->html(), 'href="viewforum.php') !== false) {
                        return true;
                    }
                }

                return strpos($c->html(), 'href="download.php') !== false;
            }
        );
        foreach($lines as $n => $line) {
            /** @var Crawler $line */
            if (preg_match('#viewforum\.php\?f=(\d+)#', $line->html(), $m)) {
                yield new ForumDto($m[1], 1, random_int(1800, 3600));
                continue;
            }
            if (preg_match('#viewtopic\.php\?t=(\d+)#', $line->html(), $m)) {
                yield new TopicDto(
                    $m[1],
                    (int) $line->filter('.seedmed b')->first()->text(),
                    (int) $line->filter('.leechmed b')->first()->text(),
                    $n * 10 + random_int(10, 30)
                );
                continue;
            }
        }

        $pages = $crawler->filter('form span.gensmall');
        if (strpos($pages->html(), 'След.') !== false) {
            yield new ForumDto($forum->id, $forum->page + 1, random_int(1800, 3600));
        }
    }

    public function getTopic(TopicDto $topic)
    {
        $this->context = ['spider' => $this->getName(), 'topicId' => $topic->id];

        $res = $this->client->get('viewtopic.php', [
            'query' => [
                't' => $topic->id,
            ]
        ]);
        $html = $res->getBody()->getContents();
        $crawler = new Crawler($html);

        preg_match('#\'filelst.php\?attach_id=(\d+)\'#', $crawler->html(), $m);
        if (empty($m)) {
            $this->logger->info('No File List', $this->context + ['html' => $crawler->html()]);
            // нету списка файлов
            return;
        }
        $fileListId = $m[1];

        $post = $crawler->filter('.postbody')->first();

        $imdb = $this->getImdb($post);

        if (!$imdb) {
            $this->logger->info('No IMDB', $this->context);
            // TODO: пока так, только imdb
            return;
        }

        $quality = $this->getQuality($post);

        $torrentTable = $crawler->filter('.btTbl')->first();

        preg_match('#"(magnet[^"]+)"#', $torrentTable->html(), $m);
        if (empty($m[1])) {
            $this->logger->warning('Not Magnet torrent', $this->context);
            return;
        }
        $url = $m[1];

        $files = $this->getFiles($fileListId);

        $torrent = new MovieTorrent();
        $torrent
            ->setProvider($this->getName())
            ->setProviderExternalId($topic->id)
            ->setUrl($url)
            ->setSeed($topic->seed)
            ->setPeer($topic->seed + $topic->leech)
            ->setQuality($quality)
        ;

        $this->torrentService->updateTorrent($torrent, $imdb, $files);
    }

    protected function getFiles($fileListId): array
    {
        $res = $this->client->get('filelst.php', [
            'query' => [
                'attach_id' =>$fileListId,
            ]
        ]);
        $html = $res->getBody()->getContents();
        $html = substr($html, strpos($html, '<table'));
        $html = substr($html, 0, strpos($html,'</table>') + 8);
        $crawlerFiles = new Crawler();
        $crawlerFiles->addHtmlContent($html, 'CP-1251');

        $files = $crawlerFiles->filter('tr')->each(function (Crawler $c) {
            $name = trim($c->filter('td[align="left"]')->html());
            $size = preg_replace('#[^0-9]#', '', $c->filter('td[align="right"]')->html());
            if (!$name) {
                $this->logger->warning('Files parsing error', $this->context);
            }
            if ($size === '') {
                return false;
            }

            return new File($name, (int) $size);
        });

        return array_filter($files);
    }

    private function getImdb(Crawler $post): ?string
    {
        $plugins = $post->filter('.imdbRatingPlugin')->each(function (Crawler $c) {
            return $c->attr('data-title');
        });

        $links = $post->filter('a[href*="imdb.com"]')->each(function (Crawler $c) {
            preg_match('#tt\d+#', $c->attr('href'), $m);
            return $m[0] ?? false;
        });

        $ids = array_unique(array_filter(array_merge($plugins, $links)));

        // пропускаем сборники
        return count($ids) == 1 ? current($ids) : null;
    }
}
