<?php

namespace App\Spider;

use App\Entity\Torrent;
use App\Service\TorrentSrvice;
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

    public function __construct(TorrentSrvice $torrentService, LoggerInterface $logger)
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
            if (preg_match('#viewtopic\.php\?p=(\d+)#', $line->html(), $m)) {
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
        $res = $this->client->get('viewtopic.php', [
            'query' => [
                't' =>$topicId,
            ]
        ]);
        $html = $res->getBody()->getContents();
        $crawler = new Crawler($html);

        preg_match('#\'filelst.php\?attach_id=(\d+)\'#', $crawler->html(), $m);
        if (empty($m)) {
            // нету списка файлов
            return;
        }
        $fileListId = $m[1];

        $post = $crawler->filter('.postbody')->first();

        $imdbBlock = $post->filter('.imdbRatingPlugin');
        if (!$imdbBlock->count()) {
            // TODO: пока так, только imdb
            return;
        }
        $imdb = $imdbBlock->attr('data-title');

        // TODO: пока так
        $quality = '480p';
        if (strpos($post->text(), '1080p')) {
            $quality = '1080p';
        } else if (strpos($post->text(), '720p')) {
            $quality = '720p';
        }

        $torrentTable = $crawler->filter('.btTbl')->first();


        preg_match('#"(magnet[^"]+)"#', $torrentTable->html(), $m);
        if (empty($m[1])) {
            $this->logger->warning('Not Magnet torrent', ['spider' => $this->getName(), 'topicId' => $topicId]);
            return;
        }
        $url = $m[1];

        $fileSize = $torrentTable->filter('span[title]')->first()->html();

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

        $size = '0';
        $lines = $crawlerFiles->filter('td[align="right"]')->each(static function (Crawler $c) {return $c;});
        foreach ($lines as $line) {
            $text = preg_replace('#[^0-9]#', '', $line->html());
            $size += (int)$text;
        }

        $torrent = new Torrent();
        $torrent
            ->setProvider($this->getName())
            ->setProviderExternalId($topicId)
            ->setUrl($url)
            ->setSeed($info['seed'])
            ->setPeer($info['seed'] + $info['leech'])
            ->setSize($size)
            ->setFilesize($fileSize)
            ->setQuality($quality)
        ;

        $this->torrentService->updateTorrent($torrent, $imdb);
    }
}
