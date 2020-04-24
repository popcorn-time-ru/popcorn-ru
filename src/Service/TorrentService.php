<?php

namespace App\Service;

use App\Entity\BaseMedia;
use App\Entity\Torrent\BaseTorrent;
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
    }
}
