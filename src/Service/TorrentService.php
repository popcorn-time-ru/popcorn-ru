<?php

namespace App\Service;

use App\Entity\BaseTorrent;
use App\Entity\File;
use App\Entity\Movie;
use App\Entity\MovieTorrent;
use App\Entity\Show;
use App\Entity\ShowTorrent;
use App\Processors\ShowTorrentProcessor;
use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use App\Repository\TorrentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;

class TorrentService
{
    private const SYNC_TIMEOUT = 3600 * 24 * 7;

    /** @var TmdbExtractor */
    protected $movieInfo;

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
     * @param TmdbExtractor          $movieInfo
     * @param EntityManagerInterface $em
     * @param ProducerInterface      $producer
     * @param TorrentRepository      $torrentRepo
     * @param MovieRepository        $movieRepo
     * @param ShowRepository         $showRepo
     */
    public function __construct(
        TmdbExtractor $movieInfo,
        EntityManagerInterface $em,
        ProducerInterface $producer,
        TorrentRepository $torrentRepo,
        MovieRepository $movieRepo,
        ShowRepository $showRepo
    ) {
        $this->movieInfo = $movieInfo;
        $this->torrentRepo = $torrentRepo;
        $this->movieRepo = $movieRepo;
        $this->showRepo = $showRepo;
        $this->producer = $producer;
        $this->em = $em;
    }

    public function searchMovieByTitleAndYear(string $title, int $year)
    {
        return $this->movieInfo->searchMovieByTitleAndYear($title, $year);
    }
    public function searchShowByTitle(string $title)
    {
        return $this->movieInfo->searchShowByTitle($title);
    }

    /**
     * @param BaseTorrent $newTorrent
     * @param string      $imdbId
     * @param File[]      $files
     */
    public function updateTorrent(BaseTorrent $newTorrent, string $imdbId, array $files)
    {
        $media = $this->movieRepo->findByImdb($imdbId);
        if (!$media) {
            $media = $this->showRepo->findByImdb($imdbId);
        }

        if (!$media) {
            $media = $this->movieInfo->fetchByImdb($imdbId);
            if (!$media) {
                // TODO: log
                return;
            }
            $media->sync();
            $this->em->persist($media);
            $this->em->flush();
        }

        $torrent = $this->torrentRepo->findByProviderAndExternalId(
            $newTorrent->getProvider(),
            $newTorrent->getProviderExternalId()
        );

        if (!$torrent) {
            if ($media instanceof Movie) {
                $torrent = new MovieTorrent();
                $torrent->setMovie($media);
            }
            if ($media instanceof Show) {
                $torrent = new ShowTorrent();
                $torrent->setShow($media);
            }
            $torrent->setProviderExternalId($newTorrent->getProviderExternalId());
            $torrent->setProvider($newTorrent->getProvider());
            $this->em->persist($torrent);
        }

        $torrent->setProviderTitle($newTorrent->getProviderTitle());
        $torrent
            ->setUrl($newTorrent->getUrl())
            ->setLanguage($newTorrent->getLanguage())
            ->setQuality($newTorrent->getQuality())
            ->setPeer($newTorrent->getPeer())
            ->setSeed($newTorrent->getSeed())
        ;

        $size = 0;
        foreach ($files as $file) {
            $size+=$file->getSize();
        }

        $torrent
            ->setFiles($files)
            ->setFilesize($this->formatBytes($size))
            ->setSize($size)
        ;
        $torrent->sync();

        $this->em->flush();

        if ($torrent instanceof ShowTorrent) {
            $torrentMessage = new \Enqueue\Client\Message(JSON::encode([
                'torrentId' => $torrent->getId()->toString(),
            ]));
            $this->producer->sendEvent(ShowTorrentProcessor::TOPIC, $torrentMessage);
        }
    }

    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= 1024 ** $pow;

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

}
