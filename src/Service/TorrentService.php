<?php

namespace App\Service;

use App\Entity\BaseTorrent;
use App\Entity\File;
use App\Repository\TorrentRepository;

class TorrentService
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

    /**
     * @param BaseTorrent $newTorrent
     * @param string  $imdbId
     * @param File[]  $files
     */
    public function updateTorrent(BaseTorrent $newTorrent, string $imdbId, array $files)
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


        $this->torrentRepo->flush();
    }

    function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= 1024 ** $pow;

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

}
