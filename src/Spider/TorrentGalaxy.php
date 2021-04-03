<?php

namespace App\Spider;

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

class TorrentGalaxy extends AbstractSpider
{
    public const BASE_URL = 'https://torrentgalaxy.to/';

    public const BASE_URL_TOR = 'http://galaxy2gchufcb3z.onion';

    /** @var Client */
    private $client;

    private $context;

    public function __construct(TorrentService $torrentService, EpisodeService $episodeService, LoggerInterface $logger, string $torProxy)
    {
        //$torProxy = '';
        parent::__construct($torrentService, $episodeService, $logger);
        $this->client = new Client([
            'base_uri' => $torProxy ? self::BASE_URL_TOR : self::BASE_URL,
            RequestOptions::TIMEOUT => $torProxy ? 30 : 10,
            RequestOptions::PROXY => $torProxy,
            'curl' => [
                CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5_HOSTNAME
            ],
            'cookies' => new FileCookieJar(sys_get_temp_dir() . '/torrentgalaxy.cookie.json', true)
        ]);
    }

    public function getSource(BaseTorrent $torrent): string
    {
        return self::BASE_URL . ltrim($torrent->getProviderExternalId(), '/');
    }

    public function getForumKeys(): array
    {
        return [
            1, // Movies - SD
            3, // Movies - 4K UHD
            42, // Movies - HD
            5, // Movies - SD
            6, // TV - Packs
            41, // Movies - HD
        ];
    }

    public function getTopic(TopicDto $topic)
    {
        $this->context = ['spider' => $this->getName(), 'topicId' => $topic->id];

        $res = $this->client->get($topic->id);
        $html = $res->getBody()->getContents();
        $crawler = new Crawler($html);

        $panels = $crawler->filter('#panelmain')->each(static function (Crawler $c) { return $c;});
        foreach ($panels as $panel) {
            if (strpos($panel->html(), 'Torrent details')) {
                $post = $panel;
            }
        }
        if (!$post) {
            $this->logger->info('empty torrent details', $this->context);
            return;
        }
        preg_match('#Torrent details for "(.*?)"#', $post->text(), $m);
        $title = $m[1];

        $imdb = $this->getImdb($post);

        if (!$imdb) {
            $this->logger->info('No IMDB', $this->context);
            $imdb = $this->getImdbByTitle($title);
            if (!$imdb) {
                return;
            }
        }

        $quality = $this->getQuality($post);

        preg_match('#"(magnet[^"]+)"#', $post->html(), $m);
        if (empty($m[1])) {
            $this->logger->warning('Not Magnet torrent', $this->context);
            return;
        }
        $url = $m[1];

        $files = $this->getFiles($post);

        $lang = current(array_filter(
            $post->filter('div.tprow')->each(static function (Crawler $c) { return $c;}),
            static function (Crawler $c) {
                return strpos($c->html(), 'Language') !== false;
            }
        ));
        $lang = $lang ? $lang->filter('img')->first()->attr('alt') : 'en';

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
            ->setLanguage($this->langName2IsoCode($lang))
        ;

        $torrent->setFiles($files);

        $this->torrentService->updateTorrent($torrent);
    }

    public function getPage(ForumDto $forum): \Generator
    {
        $res = $this->client->get('/torrents.php', [
            'query' => [
                'cat' => $forum->id,
                'page' => $forum->page-1,
            ]
        ]);
        $html = $res->getBody()->getContents();
        $crawler = new Crawler($html);

        /** @var Crawler $table */
        $table = $crawler->filter('div.tgxtable');
        $lines = array_filter(
            $table->filter('div.tgxtablerow')->each(
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
                $time = false;
                $cells = $line->filter('div.tgxtablecell');
                foreach ($cells as $cell) {
                    if (preg_match('#^\d{2}/\d{2}/\d{2} \d{2}:\d{2}$#', $cell->nodeValue)) {
                        $time = \DateTime::createFromFormat('d/m/y H:i', $cell->nodeValue);
                    }
                }
                if ($time && $time < $after) {
                    continue;
                }

                $seed = $line->filter('span[title] font')->first()->text();
                $seed = preg_replace('#[^0-9]#', '', $seed);
                $leech = $line->filter('span[title] font')->last()->text();
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

        $pages = $crawler->filter('#pager');
        if ($pages->count() === 0) {
            return;
        }
        yield new ForumDto($forum->id, $forum->page + 1, $forum->last, random_int(1800, 3600));
    }

    protected function getFiles(Crawler $c): array
    {
        $crawlerFiles = $c->filter('#k1');
        $files = $crawlerFiles->filter('tr')->each(function (Crawler $c) {
            $col = $c->filter('td.table_col1');
            if (!$col->count()) {
                return false;
            }
            $name = trim($c->filter('td.table_col1')->html());
            $size = $this->approximateSize($c->filter('td.table_col2')->text());
            if (!$size) {
                return false;
            }

            return new File($name, $size);
        });
        return array_filter($files);
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

        return $this->torrentService->searchMovieByTitleAndYear($name, $year);
    }
}

