<?php

namespace App\Spider;

use App\Entity\Episode\Episode;
use App\Entity\File;
use App\Entity\Show;
use App\Entity\Torrent\BaseTorrent;
use App\Entity\Torrent\EpisodeTorrent;
use App\Service\EpisodeService;
use App\Service\TorrentService;
use App\Spider\Dto\ForumDto;
use App\Spider\Dto\TopicDto;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class T1337x extends AbstractSpider
{
    public const BASE_URL = 'https://1337x.to/';

    /** @var Client */
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::BASE_URL,
            RequestOptions::TIMEOUT => 10,
            'cookies' => new FileCookieJar(sys_get_temp_dir() . '/1337x.cookie.json', true)
        ]);
    }

    public function getPriority(BaseTorrent $torrent): int
    {
        return -10;
    }

    public function getSource(BaseTorrent $torrent): string
    {
        return self::BASE_URL . ltrim($torrent->getProviderExternalId(), '/');
    }

    public function getForumKeys(): array
    {
        return [
            'Movies',
            'TV',
        ];
    }

    public function getTopic(TopicDto $topic)
    {
        $this->context = ['spider' => $this->getName(), 'topicId' => $topic->id];

        $res = $this->client->get($topic->id);
        $html = $res->getBody()->getContents();
        $crawler = new Crawler($html);

        $post = $crawler->filter('#description')->first();
        $title = $crawler->filter('.box-info-heading h1')->first()->text();

        $imdb = $this->parseHelper->getImdb($post);

        if (!$imdb) {
            $this->logger->info('No IMDB', $this->context);
            $imdb = $this->getImdbByTitle($title);
            if (!$imdb) {
                return;
            }
        }

        $quality = $this->parseHelper->getQuality($title, $post);

        $torrentTable = $crawler->filter('.torrent-detail-page')->first();

        preg_match('#"(magnet[^"]+)"#', $torrentTable->html(), $m);
        if (empty($m[1])) {
            $this->logger->warning('Not Magnet torrent', $this->context);
            return;
        }
        $url = $m[1];

        $files = $this->getFiles($crawler);

        $lang = current(array_filter(
            $crawler->filter('ul.list li')->each(static function (Crawler $c) { return $c;}),
            static function (Crawler $c) {
                return strpos($c->html(), 'Language') !== false;
            }
        ));
        $lang = $lang->filter('span')->first()->text();
        $language = $this->langName2IsoCode($lang);
        if (!$language) {
            return;
        }

        if (preg_match('#S(\d\d)E(\d\d)#', $title, $m)) {
            $torrent = $this->getEpisodeTorrentByImdb($topic->id, $imdb, (int)$m[1], (int)$m[2]);
        } else {
            $torrent = $this->getTorrentByImdb($topic->id, $imdb);
        }

        if (!$torrent) {
            return;
        }
        $torrent
            ->setProviderTitle($title)
            ->setUrl($url)
            ->setSeed($topic->seed)
            ->setPeer($topic->seed + $topic->leech)
            ->setQuality($quality)
            ->setLanguage($language)
        ;

        $this->hackForReleaserLang($torrent, $post);

        $torrent->setFiles($files);

        $this->torrentService->updateTorrent($torrent);
    }

    public function getPage(ForumDto $forum): \Generator
    {
        $res = $this->client->get("/cat/{$forum->id}/{$forum->page}/");
        $html = $res->getBody()->getContents();
        $crawler = new Crawler($html);

        /** @var Crawler $table */
        $table = $crawler->filter('.featured-list table');
        $lines = array_filter(
            $table->filter('tr')->each(
                static function (Crawler $c) {
                    return $c;
                }
            ),
            function (Crawler $c) use ($forum) {
                return strpos($c->html(), 'href="/torrent') !== false;
            }
        );

        $after = $forum->last ? new \DateTime($forum->last.' hours ago') : false;
        $exist = false;

        foreach ($lines as $n => $line) {
            /** @var Crawler $line */
            if (preg_match('#href="(/torrent/[^"]+)"#', $line->html(), $m)) {
                $timeString = $line->filter('td.coll-date')->first()->html();
                $timeString = str_replace("'", '', $timeString);
                try {
                    $time = new \DateTime($timeString);
                } catch (\Exception $e) {
                    $time = false;
                }
                if ($time && $time < $after) {
                    continue;
                }

                $seed = $line->filter('td.seeds')->first()->text();
                $seed = preg_replace('#[^0-9]#', '', $seed);
                $leech = $line->filter('td.leeches')->text();
                $leech = preg_replace('#[^0-9]#', '', $leech);

                yield new TopicDto(
                    $m[1],
                    (int) $seed,
                    (int) $leech,
                    $n * 10 + random_int(10, 20)
                );
                $exist = true;
                continue;
            }
        }

        if (!$exist) {
            return;
        }

        $pages = $crawler->filter('.pagination');
        if (strpos($pages->html(), 'Last') !== false) {
            yield new ForumDto($forum->id, $forum->page + 1, $forum->last, random_int(1800, 3600));
        }
    }

    protected function getFiles(Crawler $c): array
    {
        $crawlerFiles = $c->filter('#files');
        $files = $crawlerFiles->children('ul')->each(\Closure::fromCallable([$this, 'subTree']));
        $flat = array();
        array_walk_recursive($files, function($a) use (&$flat) { $flat[] = $a; });
        return array_filter($flat);
    }

    public function subTree(Crawler $c): array
    {
        $files = [];
        if ($c->previousAll()->attr('class') === 'head') {
            $dir = trim($c->previousAll()->text()) . '/';
        } else {
            $dir = '';
        }

        $subs = $c->children('ul')->each(static function (Crawler $c) { return $c;});
        foreach($subs as $sub) {
            $subfiles = $this->subTree($sub);
            foreach($subfiles as $item) {
                /** @var File $item */
                $item->setName($dir . $item->getName());
                $files[] = $item;
            }
        }

        $items = $c->children('li')->each(static function (Crawler $c) { return $c;});
        foreach($items as $item) {
            preg_match('#(.*?)\((.*?)\)#', $item->text(), $m);
            $name = trim($m[1]);
            $size = $this->approximateSize($m[2]);
            $files[] = new File($dir . $name, $size);
        }

        return $files;
    }

    private function getImdbByTitle(string $titleStr): ?string
    {
        $titleStr = str_replace('.', ' ', $titleStr);
        $isSerial = false;
        if (mb_stripos($titleStr, 'Season') !== false ||
            preg_match('#S\d\dE\d\d#', $titleStr)
        ) {
            $isSerial = true;
        }
        preg_match('#\((\d{4})\)#', $titleStr, $match);
        if ($match) {
            $year = $match[1];
        } else {
            $isSerial = true;
        }

        if ($isSerial) {
            preg_match('#(.*?)(S\d\d|Season \d)#', $titleStr, $match);
            if ($match) {
                $name = trim($match[1]);
                return $this->torrentService->searchShowByTitle($name);
            }
        }
        preg_match('#^(.*)\((\d{4})#', $titleStr, $match);
        if (count($match) != 3) {
            preg_match('#^(.*?) (\d{4})#', $titleStr, $match);
        }
        if (count($match) != 3) {
            return null;
        }
        $name = trim($match[1]);
        $year = $match[2];

        if (!$name) {
            return null;
        }

        return $this->torrentService->searchMovieByTitleAndYear($name, $year);
    }
}
