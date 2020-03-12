<?php

namespace App\Spider;

use App\Entity\Episode;
use App\Entity\File;
use App\Entity\Torrent\EpisodeTorrent;
use App\Entity\Torrent\MovieTorrent;
use App\Entity\Show;
use App\Entity\Torrent\ShowTorrent;
use App\Service\EpisodeService;
use App\Service\TorrentService;
use App\Spider\Dto\ForumDto;
use App\Spider\Dto\TopicDto;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

class Eztv extends AbstractSpider
{
    public const BASE_URL = 'https://eztv.io/';

    /** @var Client */
    private $client;

    /** @var EpisodeService */
    private $episodeService;

    public function __construct(EpisodeService $episodeService, TorrentService $torrentService, LoggerInterface $logger)
    {
        parent::__construct($torrentService, $logger);
        $this->client = new Client([
            'base_uri' => self::BASE_URL,
            RequestOptions::TIMEOUT => 10,
        ]);
        $this->episodeService = $episodeService;
    }

    public function getForumKeys(): array
    {
        return [1];
    }

    public function getPage(ForumDto $forum): \Generator
    {
        $res = $this->client->get('/api/get-torrents', [
            'query' => [
                'limit' => 100,
                'page' => $forum->page,
            ]
        ]);
        $json = $res->getBody()->getContents();
        $data = json_decode($json, true);
        if (empty($data['torrents'])) {
            return ;
        }

        $after = $forum->last ? new \DateTime($forum->last.' hours ago') : false;

        foreach ($data['torrents'] as $torrentData) {
            if ($after && $after->getTimestamp() > $torrentData['date_released_unix']) {
                return ;
            }

            $title = $torrentData['title'];
            if (!preg_match('#S\d+E\d+#i', $title)) {
                continue;
            }
            $quality = '480p';
            if (preg_match('#[^0-9a-z](\d+p)[^0-9a-z]#', $title, $m)) {
                $quality = $m[1];
            }

            $media = $this->torrentService->getMediaByImdb('tt'.$torrentData['imdb_id']);
            if (!($media instanceof Show)) {
                continue;
            }
            $episode = $this->episodeService->getEpisode($media, (int)$torrentData['season'], (int)$torrentData['episode']);
            if (!($episode instanceof Episode)) {
                continue;
            }
            $newTorrent = new EpisodeTorrent();
            $newTorrent->setEpisode($episode);
            $torrent = $this->torrentService->findExistOrCreateTorrent(
                $this->getName(),
                $torrentData['id'],
                $newTorrent
            );
            $torrent
                ->setProviderTitle($torrentData['title'])
                ->setUrl($torrentData['magnet_url'])
                ->setSeed($torrentData['seeds'])
                ->setPeer($torrentData['peers'])
                ->setQuality($quality)
                ->setLanguage('en');

            $torrent->setSize($torrentData['size_bytes']);

            $this->torrentService->updateTorrent($torrent);
        }

        yield new ForumDto($forum->id, $forum->page + 1, $forum->last, random_int(1800, 3600));
    }

    public function getTopic(TopicDto $topic)
    {
    }
}
