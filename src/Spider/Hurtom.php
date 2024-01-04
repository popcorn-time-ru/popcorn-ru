<?php

namespace App\Spider;

use App\Entity\File;
use App\Entity\Torrent\BaseTorrent;
use App\Spider\Dto\ForumDto;
use App\Spider\Dto\TopicDto;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\RequestOptions;
use Rhilip\Bencode\TorrentFile;
use Symfony\Component\DomCrawler\Crawler;

class Hurtom extends AbstractSpider implements DownloadInterface
{
    public const BASE_URL = 'https://toloka.to/';

    private const PAGE_SIZE = 50;

    private const LOGIN = 'popcorntime';
    private const PASS = 'popcorntime';

    /** @var Client */
    private $client;

    public function useTor(): bool
    {
        return false;
    }

    public function __construct(string $torProxy)
    {
        $this->client = new Client([
            'base_uri' =>  $this->useTor() ? self::BASE_URL : self::BASE_URL, // tor site dead now
            RequestOptions::TIMEOUT => $this->useTor() ? 30 : 10,
            RequestOptions::PROXY => $this->useTor() ? $torProxy : '',
            RequestOptions::DELAY => random_int(1000, 3000),
            RequestOptions::HEADERS => [
                'Accept-Encoding' => 'gzip',
            ],
            'curl' => [
                CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5_HOSTNAME
            ],
            'cookies' => new FileCookieJar(sys_get_temp_dir() . '/hurtom.cookie.json', true)
        ]);
    }

    public function getSource(BaseTorrent $torrent): string
    {
        return self::BASE_URL . 't'.$torrent->getProviderExternalId();
    }

    public function getForumKeys(): array
    {
        return [
            117, // Українське кіно
            118, // Українське озвучення

            136, // HD українською
            120, // DVD українською
        ];
    }

    protected function blackListForums(): array
    {
        return [
            // кино-говно.com
            55, //Атрхаус
            114, //короткометражки
            129, //Атрхаус
            219, //либительское видео

            // чтобы не бегать по огромным форумам без фильмов
            94, // трейлеры
        ];
    }

    public function getPage(ForumDto $forum): \Generator
    {
        $res = $this->client->get(sprintf('/f%d-%d', $forum->id, ($forum->page - 1) * 90));
        $html = $res->getBody()->getContents();
        $crawler = new Crawler($html);

        if (strpos($crawler->filter('.nav1')->html(), self::LOGIN) === false) {
            $this->login();

            $res = $this->client->get(sprintf('/f%d-%d', $forum->id, ($forum->page - 1) * 90));
            $html = $res->getBody()->getContents();
            $crawler = new Crawler($html);
        }

        $table = $crawler->filter('table.forumline');
        $lines = array_filter(
            $table->filter('tr')->each(static function (Crawler $c) { return $c;}),
            function (Crawler $c) use ($forum){
                if (strpos($c->html(), 'forumlink') !== false) {
                    return true;
                }

                return strpos($c->html(), 'topictitle') !== false;
            }
        );

        $after = $forum->last ? new \DateTime($forum->last.' hours ago') : false;
        $exist = false;

        foreach($lines as $n => $line) {
            /** @var Crawler $line */
            if (preg_match('#href="f(\d+)"#', $line->html(), $m)) {
                if (!in_array((int) $m[1], $this->blackListForums())) {
                    yield new ForumDto($m[1], 1, $forum->last, random_int(1800, 3600));
                }
                continue;
            }
            if (preg_match('#href="t(\d+)"#', $line->html(), $m)) {
                $time = $line->filter('td span.postdetails')->first()->html();
                $time = substr($time, 0 , strpos($time, '<'));
                if ($this->ruStrToTime('Y-m-d H:i', $time) < $after) {
                    continue;
                }

                $seed = $line->filter('span.seedmed')->first()->text();
                $seed = preg_replace('#[^0-9]#', '', $seed);
                $leech = $line->filter('span.leechmed')->first()->text();
                $leech = preg_replace('#[^0-9]#', '', $leech);

                yield new TopicDto(
                    $m[1],
                    (int) $seed,
                    (int) $leech,
                    $n * 10 + random_int(10, 30)
                );
                $exist = true;
            }
        }

        if (!$exist) {
            return;
        }

        $pages = $crawler->filter('td span.navigation');
        if ($pages->count() && strpos($pages->html(), 'наступна') !== false) {
            yield new ForumDto($forum->id, $forum->page + 1, $forum->last, random_int(1800, 3600));
        }
    }

    public function getTopic(TopicDto $topic)
    {
        $this->context = ['spider' => $this->getName(), 'topicId' => $topic->id];

        $res = $this->client->get('t' . $topic->id);
        $html = $res->getBody()->getContents();
        $crawler = new Crawler($html);

        //если нет, то логинимся
        if (strpos($crawler->filter('.nav1')->html(), self::LOGIN) === false) {
            $this->login();

            $res = $this->client->get('t' . $topic->id);
            $html = $res->getBody()->getContents();
            $crawler = new Crawler($html);
        }

        // тему удалили
        if (strpos($crawler->html(), 'Такої теми чи такого повідомлення не існує')) {
            $this->torrentService->deleteTorrent($this->getName(), $topic->id);
            return;
        }

        $torrentBlock = $crawler->filter('.btTbl')->first();
        $post = $torrentBlock->closest('td');
        $title = $crawler->filter('h1 a.maintitle')->first()->text();

        $imdb = $this->parseHelper->getImdb($post);

        if (!$imdb) {
            $this->logger->info('No IMDB', $this->context);
            $imdb = $this->getImdbByTitle($title);
            if (!$imdb) {
                return;
            }
        }

        $quality = $this->parseHelper->getQuality($title, $post);

        $seed = $crawler->filter('span.seed b');
        $seed = $seed->count() ? $seed->last()->text() : 0;
        $leech = $crawler->filter('span.leech b');
        $leech = $leech->count() ? $leech->last()->text() : 0;

        preg_match('#href="download\.php\?id=(\d+)"#', $torrentBlock->html(), $m);
        if (empty($m[1])) {
            $this->torrentService->deleteTorrent($this->getName(), $topic->id);
            $this->logger->warning('No Download link', $this->context);
            return;
        }
        $downloadId = $m[1];

        $topic->seed = (int) $seed;
        $topic->leech = (int) $leech;

        $torrent = $this->getTorrentByImdb($topic->id, $imdb);
        if (!$torrent) {
            return;
        }
        $torrent
            ->setProviderTitle($title)
            ->setSeed($topic->seed)
            ->setPeer($topic->seed + $topic->leech)
            ->setQuality($quality)
            ->setLanguage('ua')
        ;

        $torrent->setFiles([]);

        $this->torrentService->downloadTorrent($torrent, $downloadId);
    }

    protected function login()
    {
        $resp = $this->client->post('login.php', [
            'form_params' => [
                'username' => self::LOGIN,
                'password' => self::PASS,
                'autologin' => 'on',
                'ssl' => 'on',
                'login' => 'Вхід',
            ]
        ]);
    }

    public function downloadTorrent(BaseTorrent $torrent, string $downloadId)
    {
        $res = $this->client->get('t' . $torrent->getProviderExternalId());
        $html = $res->getBody()->getContents();
        $crawler = new Crawler($html);

        if (strpos($crawler->filter('.nav1')->html(), self::LOGIN) === false) {
            $this->login();
        }

        $file = $this->client->get('download.php?id=' . $downloadId);
        $data = $file->getBody()->getContents();
        $torrentFile = TorrentFile::loadFromString($data);
        $torrent->setUrl($torrentFile->getMagnetLink(false));

        $files = $torrentFile->getFileList();
        $files = array_map(function($item) {
            return new File($item['path'], (int) $item['size']);
        }, $files);
        $torrent->setFiles($files);

        $this->torrentService->updateTorrent($torrent);
    }

    private function getImdbByTitle(string $titleStr): ?string
    {
        preg_match('#^(.*)\[(\d{4})[^p].*?\]#', $titleStr, $match);
        $titleStr = preg_replace('#\(.*?\).*#', '', $titleStr);
        $titleStr = preg_replace('#\[.*?\]#', '', $titleStr);
        $titles = array_map('trim', explode('/', $titleStr));
        $year = isset($match[2]) ? (int)$match[2] : null;

        $names = [];
        $isSerial = false;
        foreach ($titles as $title) {
            if (mb_stripos($title, 'сезон') !== false ||
                mb_stripos($title, 'серії') !== false ||
                mb_stripos($title, 'серія') !== false ||
                mb_stripos($title, 'серіал') !== false
            ) {
                $isSerial = true;
                continue;
            }
            $names[] = $title;
        }
        $names = array_filter(array_map('trim', $names));

        foreach ($names as $name) {
            $imdb = false;
            if (!$isSerial && $year) {
                $imdb = $this->torrentService->searchMovieByTitleAndYear($name, $year);
            }
            if (!$imdb) {
                $imdb = $this->torrentService->searchShowByTitle($name);
            }
            if ($imdb) {
                return $imdb;
            }
        }
        return null;
    }

}
