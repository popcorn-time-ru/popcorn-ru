<?php

namespace App\Spider;

use DateTime;
use App\Entity\File;
use App\Entity\Torrent\BaseTorrent;
use App\Service\EpisodeService;
use App\Service\TorrentService;
use App\Spider\Dto\ForumDto;
use App\Spider\Dto\TopicDto;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class NyaaSi extends AbstractSpider
{
    public const BASE_URL = 'https://nyaa.si/';

    /** @var Client */
    private $client;

    public function __construct(TorrentService $torrentService, EpisodeService $episodeService, LoggerInterface $logger, string $torProxy)
    {
        //$torProxy = '';
        parent::__construct($torrentService, $episodeService, $logger);
        $this->client = new Client([
            'base_uri' => self::BASE_URL,
            RequestOptions::TIMEOUT => 10,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13'
            ]
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
            "1_2", // Anime - English-translated
            "1_4", // Anime - Raw
            // "4_1", // TODO Live Action - English-translated
            // "4_4", // TODO Live Action - Raw
        ];
    }

    public function getPage(ForumDto $forum): \Generator
    {
        $res = $this->client->get(sprintf('https://nyaa.si/?f=0&c=%s&q=&p=%d', $forum->id, $forum->page - 1));
        $html = $res->getBody()->getContents();
        $crawler = new Crawler($html);

        $table = $crawler->filter('table.torrent-list');
        $lines = array_filter(
            $table->filter('tr')->each(
                static function (Crawler $c) {
                    return $c;
                }
            ),
            function (Crawler $c) use ($forum) {
                return strpos($c->html(), 'href="/view/') !== false;
            }
        );

        $after = $forum->last ? new \DateTime($forum->last . ' hours ago') : false;
        $exist = false;

        foreach ($lines as $n => $line) {
            /** @var Crawler $line */
            if (preg_match('#href="(/view/[0-9]+)"#', $line->html(), $m)) {
                $unixTimestamp = $line->filter('td')->eq(4)->attr('data-timestamp');
                $time = new DateTime("@$unixTimestamp");
                if ($time < $after) {
                    continue;
                }

                $seed = $line->filter('td')->eq(5)->text();
                $seed = preg_replace('#[^0-9]#', '', $seed);
                $leech = $line->filter('td')->eq(6)->text();
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

        if (strpos($crawler->html(), sprintf('/?f=0&c=%s&q=&p=%d', $forum->id, $forum->page)) !== false) {
            yield new ForumDto($forum->id, $forum->page + 1, $forum->last, random_int(1800, 3600));
        }
    }

    public function getTopic(TopicDto $topic)
    {
        $this->context = ['spider' => $this->getName(), 'topicId' => $topic->id];

        $res = $this->client->get($topic->id);
        $html = $res->getBody()->getContents();
        $crawler = new Crawler($html);

        $title = $crawler->filter('h3.panel-title')->first()->text();
        $this->context["title"] = $title;

        $anitomy = anitomy_parse($title);
        $animeTitle = @$anitomy["anime_title"];
        if (!$animeTitle) {
            $this->logger->info('Anitomy failed to parse', $this->context);
            return;
        }
        $this->context["anitomy"] = $anitomy;

        $animeYear = @$anitomy["anime_year"];

        $kitsu = $this->torrentService->searchAnimeByTitle($animeTitle, $animeYear);
        if (!$kitsu) {
            $this->logger->info('No Kitsu', $this->context);
            return;
        }
        $this->context["kitsu"] = $kitsu;

        $quality = @$anitomy["video_resolution"];
        if (!$quality) {
            $quality = "480p";
        }

        $footer = $crawler->filter('.panel-footer')->first();

        preg_match('#"(magnet[^"]+)"#', $footer->html(), $m);
        if (empty($m[1])) {
            $this->logger->warning('No Magnet torrent', $this->context);
            return;
        }
        $url = $m[1];

        $files = $this->getFiles($crawler);

        $categoryUrl = $crawler->filter(".panel-body > .row > .col-md-5")->first()->filter("a")->eq(1)->attr("href");
        parse_str(parse_url($categoryUrl, PHP_URL_QUERY), $params);
        $category = $params['c'];

        $lang = "en";
        // if ($category == "1_4") { // Anime - Raw
        //     $lang = "ja";
        // }

        if ($this->isBatchRelease($anitomy)) {
            $torrent = $this->getTorrentByKitsu($topic->id, $kitsu);
        } else {
            $episode = @$anitomy["episode_number"];
            $season = $anitomy["anime_season"] ?? 1;
            $torrent = $this->getEpisodeTorrentByKitsu($topic->id, $kitsu, (int)$season, (int)$episode);
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
            ->setLanguage($lang);

        $torrent->setFiles($files);

        $this->torrentService->updateTorrent($torrent);

        $this->logger->debug('Saved torrent', $this->context);
    }

    protected function getFiles(Crawler $crawler): array
    {
        $files = $crawler->filter('.torrent-file-list > ul > li')->each(\Closure::fromCallable([$this, 'subTree']));
        $flat = array();
        array_walk_recursive($files, function($a) use (&$flat) { $flat[] = $a; });
        return array_filter($flat);
    }

    private function subTree(Crawler $c): array
    {
        $files = [];
        $folder = $c->filter("a.folder");
        if ($folder->count() > 0) {
            $dir = $folder->text();

            $items = $c->children('ul > li')->each(\Closure::fromCallable([$this, 'subTree']));
            $subfiles = array();
            array_walk_recursive($items, function($a) use (&$subfiles) { $subfiles[] = $a; });
            foreach($subfiles as $item) {
                /** @var File $item */
                $item->setName($dir . '/' . $item->getName());
                $files[] = $item;
            }
        } else {
            $size = 0;
            if (preg_match("#\(([\d.]+) (.*)\)#", $c->filter('.file-size')->text(), $m)) {
                $size = (double) $m[1];
                $unit = $m[2];
                if ($unit == "KiB") {
                    $size = $size * pow(1024, 1);
                }
                elseif ($unit == "MiB") {
                    $size = $size * pow(1024, 2);
                }
                elseif ($unit == "GiB") {
                    $size = $size * pow(1024, 3);
                }
                elseif ($unit == "TiB") {
                    $size = $size * pow(1024, 4);
                }
            }
            $fileName = rtrim($c->getNode(0)->childNodes[1]->nodeValue);
            $files[] = new File($fileName, $size);
        }

        return $files;
    }

    private function isBatchRelease($anitomy): bool {
        $release = @$anitomy["release_information"];
        return !@$anitomy["episode_number"]
            || $release == "Complete"
            || $release == "Batch"
            || preg_match("#\b[0]?1[\s]*-[\s]*(\d+)\b#", $anitomy["anime_title"]);
    }
}
