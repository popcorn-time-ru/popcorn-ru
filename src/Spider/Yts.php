<?php

namespace App\Spider;

use App\Entity\Movie;
use App\Entity\Torrent\BaseTorrent;
use App\Entity\Torrent\MovieTorrent;
use App\Service\EpisodeService;
use App\Service\TorrentService;
use App\Spider\Dto\ForumDto;
use App\Spider\Dto\TopicDto;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

class Yts extends AbstractSpider
{
    public const BASE_URL = 'https://yts.mx/';

    /** @var Client */
    private $client;

    public function __construct(TorrentService $torrentService, EpisodeService $episodeService, LoggerInterface $logger)
    {
        parent::__construct($torrentService, $episodeService, $logger);
        $this->client = new Client([
            'base_uri' => self::BASE_URL,
            RequestOptions::TIMEOUT => 10,
        ]);
    }

    public function getPriority(BaseTorrent $torrent): int
    {
        if ($torrent->getLanguage() === 'en') {
            return 10;
        }
        return parent::getPriority($torrent);
    }

    public function getForumKeys(): array
    {
        return [1];
    }

    public function getPage(ForumDto $forum): \Generator
    {
        $res = $this->client->get('/api/v2/list_movies.json', [
            'query' => [
                'limit' => 50,
                'page' => $forum->page,
            ]
        ]);
        $json = $res->getBody()->getContents();
        $data = json_decode($json, true);

        if (empty($data['data']['movies'])) {
            return ;
        }

        $after = $forum->last ? new \DateTime($forum->last.' hours ago') : false;
        $exist = false;

        foreach ($data['data']['movies'] as $movieData) {
            $media = $this->torrentService->getMediaByImdb($movieData['imdb_code']);
            if (!($media instanceof Movie)) {
                continue;
            }
            foreach($movieData['torrents'] as $torrentData) {
                if ($after && $after->getTimestamp() > $torrentData['date_uploaded_unix']) {
                    continue;
                }

                $this->buildTorrentFromData($media, $movieData, $torrentData);
                $exist = true;
            }
        }

        if (!$exist) {
            return;
        }

        yield new ForumDto($forum->id, $forum->page + 1, $forum->last, random_int(1800, 3600));
    }

    public function getTopic(TopicDto $topic)
    {
        [$movieId, ] = explode(':', $topic->id);
        $res = $this->client->get('/api/v2/movie_details.json', [
            'query' => [
                'movie_id' => $movieId,
            ]
        ]);
        $json = $res->getBody()->getContents();
        $data = json_decode($json, true);

        if (empty($data['data']['movie'])) {
            return ;
        }
        $movieData = $data['data']['movie'];
        $media = $this->torrentService->getMediaByImdb($movieData['imdb_code']);
        if (!($media instanceof Movie)) {
            return ;
        }

        foreach($movieData['torrents'] as $torrentData) {
            $this->buildTorrentFromData($media, $movieData, $torrentData);
        }
    }

    /**
     * @param Movie $media
     * @param array $movieData
     * @param array $torrentData
     */
    private function buildTorrentFromData(Movie $media, $movieData, $torrentData): void
    {
        $url = 'magnet:?xt=urn:btih:' . $torrentData['hash'] . '&' . implode('&', array_map(function ($item) {
            return 'tr=' . $item;
        }, [
            'udp://open.demonii.com:1337/announce',
            'udp://tracker.openbittorrent.com:80',
            'udp://tracker.coppersurfer.tk:6969',
            'udp://glotorrents.pw:6969/announce',
            'udp://tracker.opentrackr.org:1337/announce',
            'udp://torrent.gresille.org:80/announce',
            'udp://p4p.arenabg.com:1337',
            'udp://tracker.leechers-paradise.org:6969',
        ]));

        $newTorrent = new MovieTorrent();
        $newTorrent->setMovie($media);

        $torrent = $this->torrentService->findExistOrCreateTorrent(
            $this->getName(),
            $movieData['id'] . ':' . $torrentData['hash'],
            $newTorrent
        );
        $torrent
            ->setProviderTitle($movieData['title'])
            ->setUrl($url)
            ->setSeed($torrentData['seeds'])
            ->setPeer($torrentData['peers'])
            ->setQuality($torrentData['quality'])
            ->setLanguage($movieData['language']);

        $torrent->setSize($torrentData['size_bytes']);

        $this->torrentService->updateTorrent($torrent);
    }
}
