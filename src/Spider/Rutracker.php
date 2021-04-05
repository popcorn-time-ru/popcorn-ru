<?php

namespace App\Spider;

use App\Entity\File;
use App\Entity\Torrent\BaseTorrent;
use App\Entity\Torrent\MovieTorrent;
use App\Service\EpisodeService;
use App\Service\TorrentService;
use App\Spider\Dto\ForumDto;
use App\Spider\Dto\TopicDto;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class Rutracker extends AbstractSpider
{
    public const BASE_URL = 'https://rutracker.org/forum/';

    private const PAGE_SIZE = 50;

    private const LOGIN = 'nataly2019s';
    private const PASS = 'B9Z98RQ94CFjBJSG2PC7';

    /** @var Client */
    private $client;

    public function __construct(TorrentService $torrentService, EpisodeService $episodeService, LoggerInterface $logger)
    {
        parent::__construct($torrentService, $episodeService, $logger);
        $this->client = new Client([
            'base_uri' => self::BASE_URL,
            RequestOptions::TIMEOUT => 10,
            'cookies' => new FileCookieJar(sys_get_temp_dir() . '/rutracker.cookie.json', true)
        ]);
    }

    public function getSource(BaseTorrent $torrent): string
    {
        return self::BASE_URL . 'viewtopic.php?t='.$torrent->getProviderExternalId();
    }

    public function getForumKeys(): array
    {
        return [
            7, // Зарубежное кино
            313, // Зарубежное кино HD

            189, // Зарубежные сериалы
            2366, // Зарубежные сериалы HD

            209, // Зарубежные мультфильмы
            930, // Зарубежные мультфильмы HD

            815, // Зарубежные мультсериалы
            1460, // Зарубежные мультсериалы HD
        ];
    }

    protected function blackListForums(): array
    {
        return [
            // кино-говно.com
            934, //Азиатское кино
            505, //Идийское кино
            1235, //Грайндхаус
            2459, //короткометражки

            // мы не поддерживаем несколько фильмов в одном торренте
            212, //Сборники фильмов
            // чтобы не бегать по огромным форумам без фильмов
            1640, // подборки ссылок
            185, //Звуковые дорожки
            254, //список актеров
            771, //список режиссеров
            906, //ищу звук
            69, //архив
            44, //обсуждение

            // сериалы
            195, //некондиционные
            190, //архив
            1147, //обсуждение
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

        $table = $crawler->filter('#main_content table.forumline');
        $lines = array_filter(
            $table->filter('tr')->each(static function (Crawler $c) { return $c;}),
            function (Crawler $c) use ($forum){
                if (strpos($c->html(), 'href="viewforum.php') !== false) {
                    return true;
                }

                return strpos($c->html(), 'href="dl.php') !== false;
            }
        );

        $after = $forum->last ? new \DateTime($forum->last.' hours ago') : false;
        $exist = false;

        foreach($lines as $n => $line) {
            /** @var Crawler $line */
            if (preg_match('#viewforum\.php\?f=(\d+)#', $line->html(), $m)) {
                if (!in_array((int) $m[1], $this->blackListForums())) {
                    yield new ForumDto($m[1], 1, $forum->last, random_int(1800, 3600));
                }
                continue;
            }
            if (preg_match('#viewtopic\.php\?t=(\d+)#', $line->html(), $m)) {
                $time = $line->filter('td.vf-col-last-post p')->first()->html();
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
                continue;
            }
        }

        if (!$exist) {
            return;
        }

        $pages = $crawler->filter('#pagination');
        if ($pages->count() && strpos($pages->html(), 'След.') !== false) {
            yield new ForumDto($forum->id, $forum->page + 1, $forum->last, random_int(1800, 3600));
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

        $post = $crawler->filter('#topic_main tbody.row1')->first();
        $title = $crawler->filter('h1.maintitle a#topic-title')->first()->text();

        $imdb = $this->getImdb($post);

        if (!$imdb) {
            $this->logger->info('No IMDB', $this->context);
            $imdb = $this->getImdbByTitle($title);
            if (!$imdb) {
                return;
            }
        }

        $quality = $this->getQuality($post);

        $torrentBlock = $post->filter('fieldset.attach')->first();
        if ($torrentBlock->count() == 0) {
            $torrentBlock = $post->filter('table.attach')->first();
        }

        preg_match('#"(magnet[^"]+)"#', $torrentBlock->html(), $m);
        if (empty($m[1])) {
            $this->logger->warning('Not Magnet torrent', $this->context);
            return;
        }
        $url = $m[1];

        //Так, таки надо тянуть файлы, проверяем залогиены ли мы, и если нет, то логинимся
        if (strpos($crawler->filter('.topmenu')->html(), self::LOGIN) === false) {
            $resp = $this->client->post('login.php', [
                'form_params' => [
                    'login_username' => self::LOGIN,
                    'login_password' => self::PASS,
                    'login' => 'вход',
                ]
            ]);
        }

        $files = $this->getFiles($topic->id);

        $seed = $crawler->filter('span.seed b');
        $seed = $seed->count() ? $seed->first()->text() : 0;
        $leech = $crawler->filter('span.leech b');
        $leech = $leech->count() ? $leech->first()->text() : 0;

        $topic->seed = (int) $seed;
        $topic->leech = (int) $leech;

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
        $res = $this->client->post('viewtorrent.php', [
            'form_params' => [
                't' => $fileListId,
            ]
        ]);
        $html = $res->getBody()->getContents();
        $crawlerFiles = new Crawler();
        $crawlerFiles->addHtmlContent($html, 'UTF-8');

        $files = $crawlerFiles->filter('ul.ftree > li')->each(\Closure::fromCallable([$this, 'subTree']));
        $flat = array();
        array_walk_recursive($files, function($a) use (&$flat) { $flat[] = $a; });
        return array_filter($flat);
    }

    public function subTree(Crawler $c): array
    {
        $files = [];
        if ($c->attr('class') === 'dir') {
            $dir = ltrim($c->children('div')->filter('b')->html(), './');

            $items = $c->children('ul > li')->each(\Closure::fromCallable([$this, 'subTree']));
            $subfiles = array();
            array_walk_recursive($items, function($a) use (&$subfiles) { $subfiles[] = $a; });
            foreach($subfiles as $item) {
                /** @var File $item */
                $item->setName($dir . '/' . $item->getName());
                $files[] = $item;
            }
        } else {
            $files[] = new File(
                $c->filter('b')->html(),
                (int) $c->filter('i')->html()
            );
        }

        return $files;
    }

    private function getImdbByTitle(string $titleStr): ?string
    {
        $titleStr = preg_replace('#\(.*?\)#', '', $titleStr);
        preg_match('#^(.*)\[(\d{4}).*\]#', $titleStr, $match);
        if (count($match) != 3) {
            return null;
        }
        $titles = array_map('trim', explode('/', $match[1]));
        $year = (int)$match[2];

        $names = [];
        $isSerial = false;
        foreach ($titles as $title) {
            if (mb_stripos($title, 'сезон') !== false ||
                mb_stripos($title, 'серии') !== false ||
                mb_stripos($title, 'сериал') !== false
            ) {
                $isSerial = true;
                continue;
            }
            $names[] = $title;
        }

        foreach ($names as $name) {
            $imdb = $isSerial
                ? $this->torrentService->searchShowByTitle($name)
                : $this->torrentService->searchMovieByTitleAndYear($name, $year)
            ;
            if ($imdb) {
                return $imdb;
            }
        }
        return null;
    }
}
