<?php

namespace App\Spider;

use App\Entity\File;
use App\Entity\MovieTorrent;
use App\Service\TorrentService;
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
        ];
    }

    public function getPage($forumId, $page): \Generator
    {
        $res = $this->client->get('viewforum.php', [
            'query' => [
                'f' => $forumId,
                'start' => (($page-1)*self::PAGE_SIZE),
            ]
        ]);
        $html = $res->getBody()->getContents();
        $crawler = new Crawler($html);

        /** @var Crawler $table */
        $table = $crawler->filter('table.forumline');
        $lines = array_filter(
            $table->filter('tr')->each(static function (Crawler $c) { return $c;}),
            static function (Crawler $c) {
                return strpos($c->html(), 'href="download.php') !== false;
            }
        );
        foreach($lines as $line) {
            /** @var Crawler $line */
            if (preg_match('#viewtopic\.php\?t=(\d+)#', $line->html(), $m)) {
                $info = [
                    'seed' => $line->filter('.seedmed b')->first()->text(),
                    'leech' => $line->filter('.leechmed b')->first()->text(),
                ];
                yield $m[1] => $info;
            }
        }

        $pages = $crawler->filter('form span.gensmall');
        return strpos($pages->html(), 'След.') !== false;
    }

    public function getTopic($topicId, array $info)
    {
        $this->context = ['spider' => $this->getName(), 'topicId' => $topicId];

        $res = $this->client->get('viewtopic.php', [
            'query' => [
                't' =>$topicId,
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
            ->setProviderExternalId($topicId)
            ->setUrl($url)
            ->setSeed($info['seed'])
            ->setPeer($info['seed'] + $info['leech'])
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

    private function getQuality(Crawler $post): string
    {
        if (strpos($post->text(), '1080p')) {
            return '1080p';
        }
        if (strpos($post->text(), '720p')) {
            return '720p';
        }
        if (preg_match('#1920\s*[xхXХ*]\s*\d+#u', $post->html())) {
            return '1080p';
        }
        if (preg_match('#1280\s*[xхXХ*]\s*\d+#u', $post->html())) {
            return '720p';
        }

        return '480p';
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
