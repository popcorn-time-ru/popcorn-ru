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

class Rutor extends AbstractSpider
{
    public const BASE_URL = 'http://rutor.info';
    public const BASE_URL_TOR = 'http://rutorc6mqdinc4cz.onion';

    /** @var Client */
    private $client;

    private $context;

    public function __construct(TorrentService $torrentService, LoggerInterface $logger, string $torProxy)
    {
        parent::__construct($torrentService, $logger);
        $this->client = new Client([
            'base_uri' => $torProxy ? self::BASE_URL_TOR : self::BASE_URL,
            RequestOptions::TIMEOUT => $torProxy ? 30 : 10,
            RequestOptions::PROXY => $torProxy,
            'curl'  => [
                CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5_HOSTNAME
            ],
        ]);
    }
    public function getForumKeys(): array
    {
        return [
            1, // Зарубежные фильмы
            4, // Зарубежные сериалы
        ];
    }

    public function getPage(ForumDto $forum): \Generator
    {
        try {
            $res = $this->client->get(sprintf('/browse/%d/%d/0/0', $forum->page - 1, $forum->id));
            $html = $res->getBody()->getContents();
            $crawler = new Crawler($html);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }

        $table = $crawler->filter('#index table');
        $lines = array_filter(
            $table->filter('tr')->each(static function (Crawler $c) { return $c;}),
            function (Crawler $c) use ($forum){
                return strpos($c->html(), 'href="/torrent') !== false;
            }
        );

        foreach($lines as $n => $line) {
            /** @var Crawler $line */
            if (preg_match('#href="(/torrent/[^"]+)"#', $line->html(), $m)) {

                $seed = $line->filter('span.green')->first()->text();
                $seed = preg_replace('#[^0-9]#', '', $seed);
                $leech = $line->filter('span.red')->text();
                $leech = preg_replace('#[^0-9]#', '', $leech);

                yield new TopicDto(
                    $m[1],
                    (int) $seed,
                    (int) $leech,
                    $n * 30 + random_int(10, 20)
                );
                continue;
            }
        }

        if (strpos($crawler->html(), sprintf('/browse/%d/%d/0/0', $forum->page, $forum->id)) !== false) {
            yield new ForumDto($forum->id, $forum->page + 1, random_int(1800, 3600));
        }
    }

    public function getTopic(TopicDto $topic)
    {
        $this->context = ['spider' => $this->getName(), 'topicId' => $topic->id];

        $res = $this->client->get($topic->id);
        $html = $res->getBody()->getContents();
        $crawler = new Crawler($html);

        preg_match('#\'/descriptions/(\d+).files\'#', $crawler->html(), $m);
        if (empty($m)) {
            $this->logger->info('No File List', $this->context + ['html' => $crawler->html()]);
            // нету списка файлов
            return;
        }
        $fileListId = $m[1];

        $post = $crawler->filter('#details')->first();

        $imdb = $this->getImdb($post);

        if (!$imdb) {
            $this->logger->info('No IMDB', $this->context);
            // TODO: пока так, только imdb
            return;
        }

        $quality = $this->getQuality($post);

        $torrentBlock = $crawler->filter('#download')->first();

        preg_match('#"(magnet[^"]+)"#', $torrentBlock->html(), $m);
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
        $res = $this->client->get('/descriptions/'.$fileListId.'.files');
        $html = $res->getBody()->getContents();
        $crawlerFiles = new Crawler();
        $crawlerFiles->addHtmlContent($html, 'UTF-8');

        $files = $crawlerFiles->filter('tr')->each(function (Crawler $c) {
            $name = trim($c->filter('td')->first()->text());
            preg_match('#\((\d+)\)#', $c->filter('td')->last()->html(), $m);
            $size = $m[1];
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

        $links = $post->filter('a[href*="imdb.com"]')->each(function (Crawler $c) {
            preg_match('#tt\d+#', $c->attr('href'), $m);
            return $m[0] ?? false;
        });

        $ids = array_unique(array_filter($links));

        // пропускаем сборники
        return count($ids) == 1 ? current($ids) : null;
    }
}
