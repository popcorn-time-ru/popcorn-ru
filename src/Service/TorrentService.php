<?php

namespace App\Service;

use App\Entity\BaseMedia;
use App\Entity\Torrent\BaseTorrent;
use App\Entity\Torrent\ShowTorrent;
use App\Processors\ShowTorrentProcessor;
use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use App\Repository\TorrentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;

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

    /**
     * TorrentService constructor.
     *
     * @param MediaService           $mediaInfo
     * @param EntityManagerInterface $em
     * @param ProducerInterface      $producer
     * @param TorrentRepository      $torrentRepo
     * @param MovieRepository        $movieRepo
     * @param ShowRepository         $showRepo
     */
    public function __construct(
        MediaService $mediaInfo,
        EntityManagerInterface $em,
        ProducerInterface $producer,
        TorrentRepository $torrentRepo,
        MovieRepository $movieRepo,
        ShowRepository $showRepo
    ) {
        $this->mediaInfo = $mediaInfo;
        $this->torrentRepo = $torrentRepo;
        $this->movieRepo = $movieRepo;
        $this->showRepo = $showRepo;
        $this->producer = $producer;
        $this->em = $em;
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
                // TODO: log
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
        $this->em->flush();

        $torrent->getMedia()->addExistTranslation($torrent->getLanguage());
        $this->em->flush();

        if ($torrent instanceof ShowTorrent) {
            $torrentMessage = new \Enqueue\Client\Message(JSON::encode([
                'torrentId' => $torrent->getId()->toString(),
            ]));
            $this->producer->sendEvent(ShowTorrentProcessor::TOPIC, $torrentMessage);
        }
    }
}
