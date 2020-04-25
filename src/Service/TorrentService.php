<?php

namespace App\Service;

use App\Entity\BaseMedia;
use App\Entity\Movie;
use App\Entity\Show;
use App\Entity\Torrent\BaseTorrent;
use App\Entity\Torrent\MovieTorrent;
use App\Entity\Torrent\ShowTorrent;
use App\Processors\ShowTorrentProcessor;
use App\Processors\TorrentActiveProcessor;
use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use App\Repository\TorrentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;

class TorrentService
{
    /** @var MediaService */
    protected $mediaInfo;

    /** @var EntityManagerInterface */
    protected $em;

    /** @var TorrentRepository */
    protected $torrentRepo;

    /** @var MovieRepository */
    protected $movieRepo;

    /** @var ShowRepository */
    protected $showRepo;

    /** @var ProducerInterface */
    private $producer;

    /** @var LoggerInterface */
    private $logger;

    /**
     * TorrentService constructor.
     *
     * @param MediaService           $mediaInfo
     * @param EntityManagerInterface $em
     * @param ProducerInterface      $producer
     * @param TorrentRepository      $torrentRepo
     * @param MovieRepository        $movieRepo
     * @param ShowRepository         $showRepo
     * @param LoggerInterface        $logger
     */
    public function __construct(
        MediaService $mediaInfo,
        EntityManagerInterface $em,
        ProducerInterface $producer,
        TorrentRepository $torrentRepo,
        MovieRepository $movieRepo,
        ShowRepository $showRepo,
        LoggerInterface $logger
    ) {
        $this->mediaInfo = $mediaInfo;
        $this->torrentRepo = $torrentRepo;
        $this->movieRepo = $movieRepo;
        $this->showRepo = $showRepo;
        $this->producer = $producer;
        $this->em = $em;
        $this->logger = $logger;
    }

    public function searchMovieByTitleAndYear(string $title, int $year)
    {
        return $this->mediaInfo->searchMovieByTitleAndYear($title, $year);
    }
    public function searchShowByTitle(string $title)
    {
        return $this->mediaInfo->searchShowByTitle($title);
    }

    public function getMediaByImdb(string $imdbId): ?BaseMedia
    {
        $media = $this->movieRepo->findByImdb($imdbId);
        if (!$media) {
            $media = $this->showRepo->findByImdb($imdbId);
        }

        if (!$media) {
            $media = $this->mediaInfo->fetchByImdb($imdbId);
            if (!$media) {
                $this->logger->warning('Not found media', ['imdb' => $imdbId]);
                return null;
            }
            $media->sync();
            $this->em->persist($media);
            $this->em->flush();
        }

        return $media;
    }

    public function findExistOrCreateTorrent(string $provider, string $externalId, BaseTorrent $new)
    {
        $torrent = $this->torrentRepo->findByProviderAndExternalId(
            $provider,
            $externalId
        );

        if ($torrent) {
            return $torrent;
        }

        $new->setProvider($provider);
        $new->setProviderExternalId($externalId);
        $this->em->persist($new);
        return $new;
    }

    /**
     * @param BaseTorrent $torrent
     */
    public function updateTorrent(BaseTorrent $torrent)
    {
        $torrent->sync();
        $torrent->setActive(true);
        $this->em->flush();

        $torrent->getMedia()->addExistTranslation($torrent->getLanguage());
        $this->em->flush();

        $torrentMessage = new \Enqueue\Client\Message(JSON::encode([
            'torrentId' => $torrent->getId()->toString(),
        ]));
        $topic = $torrent instanceof ShowTorrent ? ShowTorrentProcessor::TOPIC : TorrentActiveProcessor::TOPIC;
        $this->producer->sendEvent($topic, $torrentMessage);
    }

    public function updateActive(UuidInterface $torrentId)
    {
        $torrent = $this->torrentRepo->find($torrentId);
        if ($torrent instanceof MovieTorrent) {
            $this->selectActiveForMovie($torrent->getMedia(), $torrent->getLanguage());
        } else {
            $this->selectActiveForShow($torrent->getMedia(), $torrent->getLanguage());
        }
        $this->torrentRepo->flush();
    }

    protected function selectActiveForMovie(Movie $movie, string $language, bool $onlyActive = true)
    {
        /** @var BaseTorrent[] $active */
        $active = [];
        foreach ($this->torrentRepo->getMediaTorrents($movie, $language, $onlyActive) as $torrent) {
            $torrent->setActive(false);
            if (empty($active[$torrent->getQuality()])
                || $active[$torrent->getQuality()]->getPeer() < $torrent->getPeer()) {
                $active[$torrent->getQuality()] = $torrent;
            }
        }
        /** @var BaseTorrent[] $all */
        foreach ($active as $q => $t) {
            $t->setActive(true);
        }
    }

    protected function selectActiveForShow(Show $show, string $language, bool $onlyActive = true)
    {
        /** @var BaseTorrent[][] $active */
        $active = [];
        foreach ($show->getEpisodes() as $episode) {
            $key = $episode->getSeason() . ':' . $episode->getEpisode();
            foreach ($this->torrentRepo->getEpisodeTorrents($episode, $language, $onlyActive) as $torrent) {
                $torrent->setActive(false);
                if (empty($active[$key][$torrent->getQuality()])
                    || $active[$key][$torrent->getQuality()]->getPeer() < $torrent->getPeer()) {
                    $active[$key][$torrent->getQuality()] = $torrent;
                }
            }
            foreach ($this->torrentRepo->getMediaTorrents($show, $language, $onlyActive) as $torrent) {
                $torrent->setActive(false);
                $file = null;
                foreach ($torrent->getFiles() as $torrentFile) {
                    if ($torrentFile->isEpisode($episode)) {
                        $file = $torrentFile;
                        break;
                    }
                }

                if (!$file) {
                    continue;
                }
                if (empty($active[$key][$torrent->getQuality()])
                    || $active[$key][$torrent->getQuality()]->getPeer() < $torrent->getPeer()) {
                    $active[$key][$torrent->getQuality()] = $torrent;
                }
            }
        }
        /** @var BaseTorrent[] $all */
        foreach ($active as $key => $ql) {
            foreach ($ql as $q => $t) {
                $t->setActive(true);
            }
        }
    }
}
