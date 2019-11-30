<?php

namespace App\Spider;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DomCrawler\Crawler;

class NnmClub extends AbstractSpider
{
    public const BASE_URL = 'https://nnmclub.to/forum/';

    private const PAGE_SIZE = 50;

    /** @var Client */
    private $client;

    /**
     * NnmClub constructor.
     */
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::BASE_URL,
            RequestOptions::TIMEOUT => 5,
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
                'f' =>$forumId,
                'start' => (($page-1)*self::PAGE_SIZE),
            ]
        ]);
        $html = $res->getBody()->getContents();
        $crawler = new Crawler($html);

        /** @var Crawler $table */
        $table = $crawler->filter('table.forumline');
        $lines = array_filter($table->filter('tr')->each(function (Crawler $c) { return $c;}), function (Crawler $c) {
            return strpos($c->html(), 'href="download.php') !== false;
        });
        foreach($lines as $line) {
            /** @var Crawler $line */
            preg_match('#viewtopic\.php\?p=(\d+)#', $line->html(), $m);
            yield $m[1];
        }

        $pages = $crawler->filter('form span.gensmall');
        return strpos($pages->html(), 'След.') !== false;
    }

    public function getTopic($topicId)
    {

    }
}
