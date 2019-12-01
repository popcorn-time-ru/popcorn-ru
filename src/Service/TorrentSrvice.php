<?php

namespace App\Service;

use App\Entity\Torrent;
use App\Repository\TorrentRepository;

class TorrentSrvice
{
    /** @var MovieInfo */
    protected $movieInfo;

    /** @var TorrentRepository */
    protected $torrentRepo;

    /**
     * TorrentSrvice constructor.
     *
     * @param MovieInfo         $movieInfo
     * @param TorrentRepository $torrentRepo
     */
    public function __construct(MovieInfo $movieInfo, TorrentRepository $torrentRepo)
    {
        $this->movieInfo = $movieInfo;
        $this->torrentRepo = $torrentRepo;
    }

    public function updateTorrent(Torrent $newTorrent, string $imdbId)
    {
        $movie = $this->movieInfo->getByImdb($imdbId);

        $torrent = $this->torrentRepo->findOrCreateByProviderAndExternalId(
            $newTorrent->getProvider(),
            $newTorrent->getProviderExternalId()
        );

        $torrent->setMovie($movie);
        $torrent
            ->setUrl($newTorrent->getUrl())
            ->setLanguage('en')
            ->setQuality($newTorrent->getQuality())
            ->setFilesize($newTorrent->getFilesize())
            ->setSize($newTorrent->getSize())
            ->setPeer($newTorrent->getPeer())
            ->setSeed($newTorrent->getSeed())
        ;

        $this->torrentRepo->flush();
    }
}
