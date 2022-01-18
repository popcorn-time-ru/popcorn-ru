<?php

namespace App\Spider;

use App\Entity\File;
use App\Entity\Torrent\BaseTorrent;
use App\Service\EpisodeService;
use App\Service\TorrentService;
use App\Spider\Dto\ForumDto;
use App\Spider\Dto\TopicDto;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class Rutor extends AbstractSpider
{
    public const BASE_URL = 'http://rutor.info';

    public const BASE_URL_TOR = 'http://rutorc6mqdinc4cz.onion';

    /** @var Client */
    private $client;

    public function __construct(string $torProxy)
    {
        $this->client = new Client([
            'base_uri' => $torProxy ? self::BASE_URL_TOR : self::BASE_URL,
            RequestOptions::TIMEOUT => $torProxy ? 30 : 10,
            RequestOptions::PROXY => $torProxy,
            'curl' => [
                CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5_HOSTNAME
            ],
        ]);
    }

    public function getSource(BaseTorrent $torrent): string
    {
        return self::BASE_URL . $torrent->getProviderExternalId();
    }

    public function getForumKeys(): array
    {
        return [
            1, // Зарубежные фильмы
            4, // Зарубежные сериалы
            7, // Мультипликация
        ];
    }

    public function getPage(ForumDto $forum): \Generator
    {
        $res = $this->client->get(sprintf('/browse/%d/%d/0/0', $forum->page - 1, $forum->id));
        $html = $res->getBody()->getContents();
        $crawler = new Crawler($html);

        $table = $crawler->filter('#index table');
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

        $after = $forum->last ? new \DateTime($forum->last . ' hours ago') : false;
        $exist = false;

        foreach ($lines as $n => $line) {
            /** @var Crawler $line */
            if (preg_match('#href="(/torrent/[^"]+)"#', $line->html(), $m)) {
                $time = $line->filter('td')->first()->html();
                $time = preg_replace('#[^0-9а-яА-Я]#u', ' ', $time);
                if ($this->ruStrToTime('d F y', $time) < $after) {
                    continue;
                }

                $seed = $line->filter('span.green')->first()->text();
                $seed = preg_replace('#[^0-9]#', '', $seed);
                $leech = $line->filter('span.red')->text();
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

        if (strpos($crawler->html(), sprintf('/browse/%d/%d/0/0', $forum->page, $forum->id)) !== false) {
            yield new ForumDto($forum->id, $forum->page + 1, $forum->last, random_int(1800, 3600));
        }
    }

    public function getTopic(TopicDto $topic)
    {
        $this->context = ['spider' => $this->getName(), 'topicId' => $topic->id];

        $res = $this->client->get($topic->id);
        $html = $res->getBody()->getContents();
        $crawler = new Crawler($html);

        if (strpos($crawler->html(), '<h1>Раздача не существует!</h1>')) {
            $this->torrentService->deleteTorrent($this->getName(), $topic->id);
            return;
        }

        preg_match('#\'/descriptions/(\d+).files\'#', $crawler->html(), $m);
        if (empty($m)) {
            $this->logger->error('No File List', $this->context + ['html' => $crawler->html()]);

            // нету списка файлов
            return;
        }
        $fileListId = $m[1];

        $post = $crawler->filter('#details tr')->first();
        $title = $crawler->filter('#all h1')->first()->text();

        $imdb = $this->parseHelper->getImdb($post);

        if (!$imdb) {
            $this->logger->info('No IMDB', $this->context);
            $imdb = $this->getImdbByTitle($title);
            if (!$imdb) {
                return;
            }
        }

        $quality = $this->parseHelper->getQuality($title, $post);

        $torrentBlock = $crawler->filter('#download')->first();

        preg_match('#"(magnet[^"]+)"#', $torrentBlock->html(), $m);
        if (empty($m[1])) {
            $this->logger->warning('Not Magnet torrent', $this->context);

            return;
        }
        $url = $m[1];

        $files = $this->getFiles($fileListId);

        $post->filter('tr')->each(
            static function (Crawler $c) use ($topic) {
                if (strpos($c->html(), 'Раздают')) {
                    $topic->seed = (int) $c->filter('td')->last()->html();
                }
                if (strpos($c->html(), 'Качают')) {
                    $topic->leech = (int) $c->filter('td')->last()->html();
                }
            }
        );

        $torrent = $this->getTorrentByImdb($topic->id, $imdb);
        if (!$torrent) {
            return;
        }
        $torrent
            ->setProviderTitle($title)
            ->setUrl($url)
            ->setSeed($topic->seed)
            ->setPeer($topic->seed + $topic->leech)
            ->setQuality($quality)
            ->setLanguage('ru')
        ;

        $torrent->setFiles($files);

        $this->torrentService->updateTorrent($torrent);
    }

    protected function getFiles($fileListId): array
    {
        $res = $this->client->get('/descriptions/' . $fileListId . '.files');
        $html = $res->getBody()->getContents();
        $crawlerFiles = new Crawler();
        $crawlerFiles->addHtmlContent($html, 'UTF-8');

        $files = $crawlerFiles->filter('tr')->each(
            function (Crawler $c) {
                $name = trim($c->filter('td')->first()->text());
                preg_match('#\((\d+)\)#', $c->filter('td')->last()->html(), $m);
                $size = $m[1];
                if (!$name) {
                    $this->logger->error('Files parsing error', $this->context + ['html' => $c->html()]);
                }
                if ($size === '') {
                    return false;
                }

                return new File($name, (int) $size);
            }
        );

        return array_filter($files);
    }

    private function getImdbByTitle(string $titleStr): ?string
    {
        $isSerial = false;
        if (preg_match('#\[.*\]#', $titleStr)) {
            $isSerial = true;
        }
        preg_match('#\((\d{4})\)#', $titleStr, $match);
        $year = $match ? (int) $match[1] : -1;

        preg_match('#^(.*?)[(\[].*#', $titleStr, $match);
        if (count($match) != 2) {
            return null;
        }
        $names = array_map('trim', explode('/', $match[1]));
        $names = array_filter($names);

        foreach ($names as $name) {
            $imdb = $isSerial
                ? $this->torrentService->searchShowByTitle($name)
                : $this->torrentService->searchMovieByTitleAndYear($name, $year);
            if ($imdb) {
                return $imdb;
            }
        }

        return null;
    }
}
