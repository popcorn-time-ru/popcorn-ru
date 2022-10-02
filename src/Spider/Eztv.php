<?php

namespace App\Spider;

use App\Entity\Episode;
use App\Entity\Torrent\EpisodeTorrent;
use App\Entity\Show;
use App\Spider\Dto\ForumDto;
use App\Spider\Dto\TopicDto;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

class Eztv extends AbstractSpider
{
    public const BASE_URL = 'https://eztv.re/';

    /** @var Client */
    private Client $client;

    public function __construct(string $torProxy)
    {
        $this->client = new Client([
            'base_uri' => self::BASE_URL,
            RequestOptions::TIMEOUT => $this->useTor() ? 30 : 10,
            RequestOptions::PROXY => $this->useTor() ? $torProxy : '',
            'curl' => [
                CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5_HOSTNAME
            ],
        ]);
    }

    public function getForumKeys(): array
    {
        return [1];
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
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

        if (!$after || $this->hasNewTorrents($data, $after)) {
            yield new ForumDto($forum->id, $forum->page + 1, $forum->last, random_int(1800, 3600));
        }

        foreach ($data['torrents'] as $torrentData) {
            $this->buildFromTorrentData($torrentData);
        }
    }

    private function hasNewTorrents($data, \DateTime $after): bool
    {
        foreach ($data['torrents'] as $torrentData) {
            if ($after->getTimestamp() < $torrentData['date_released_unix']) {
                return true;
            }
        }
        return false;
    }

    public function getTopic(TopicDto $topic)
    {
        [$showId, ] = explode(':', $topic->id);
        $page = 1;
        while (true) {
            $res = $this->client->get('/api/get-torrents', [
                'query' => [
                    'imdb_id' => $showId,
                    'page' => $page++,
                ]
            ]);
            $json = $res->getBody()->getContents();
            $data = json_decode($json, true);
            if (empty($data['torrents'])) {
                return ;
            }

            foreach ($data['torrents'] as $torrentData) {
                $this->buildFromTorrentData($torrentData);
            }
        }
    }

    /**
     * @param array $torrentData
     */
    private function buildFromTorrentData(array $torrentData): void
    {
        $title = $torrentData['title'];
        if (!preg_match('#S\d+E\d+#i', $title)) {
            return;
        }
        $quality = '480p';
        if (preg_match('#[^0-9a-z](\d+p)[^0-9a-z]#', $title, $m)) {
            $quality = $m[1];
        }

        $media = $this->torrentService->getMediaByImdb('tt'.$torrentData['imdb_id']);
        if (!($media instanceof Show)) {
            return;
        }
        $episode = $this->episodeService->getEpisode($media, (int)$torrentData['season'], (int)$torrentData['episode']);
        if (!($episode instanceof Episode)) {
            return;
        }
        $newTorrent = new EpisodeTorrent();
        $newTorrent->setEpisode($episode);
        $torrent = $this->torrentService->findExistOrCreateTorrent(
            $this->getName(),
            $torrentData['imdb_id'] . ':' . $torrentData['id'],
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
}
