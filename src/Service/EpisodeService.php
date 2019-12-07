<?php

namespace App\Service;

use App\Entity\Episode;
use App\Entity\ShowTorrent;
use App\Repository\TorrentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\UuidInterface;
use Tmdb\Model\Tv\Season;

class EpisodeService
{
    /** @var TorrentRepository */
    protected $torrentRepo;

    /** @var TmdbExtractor */
    protected $movieInfo;

    /** @var EntityManagerInterface */
    protected $em;

    /**
     * EpisodeService constructor.
     *
     * @param TorrentRepository      $torrentRepo
     * @param TmdbExtractor          $movieInfo
     * @param EntityManagerInterface $em
     */
    public function __construct(TorrentRepository $torrentRepo, TmdbExtractor $movieInfo, EntityManagerInterface $em)
    {
        $this->torrentRepo = $torrentRepo;
        $this->movieInfo = $movieInfo;
        $this->em = $em;
    }

    public function link(UuidInterface $torrentId): void
    {
        $torrent = $this->torrentRepo->find($torrentId);
        if (!$torrent instanceof ShowTorrent) {
            return;
        }

        $showInfo = [];
        foreach ($torrent->getFiles() as $file) {
            [$s, $e] = $this->getSEFromName($file->getName());
            if ($s === false) {
                continue;
            }

            $item = null;
            foreach ($torrent->getShow()->getEpisodes() as $episode) {
                if ($episode->getSeason() === $s && $episode->getEpisode() === $e) {
                    $item = $episode;
                    break;
                }
            }
            if (!$item) {
                $item = new Episode();
                $item
                    ->setShow($torrent->getShow())
                    ->setSeason($s)
                    ->setEpisode($e)
                ;
            }

            $episodeInfo = null;

            if (empty($showInfo[$s])) {
                $showInfo[$s] = $this->movieInfo
                    ->getSeasonEpisodes($torrent->getShow(), $s);
            }
            foreach ($showInfo[$s] as $episodeInfo) {
                if ($episodeInfo['episode_number'] == $e) {
                    $item
                        ->setTitle($episodeInfo['name'])
                        ->setOverview($episodeInfo['overview'])
                        ->setFirstAired((new \DateTime($episodeInfo['air_date'] ?? 'now'))->getTimestamp())
                        ->setTvdb(random_int(100000, 1000000))
                        // TODO: нужно откуда-то все дергать, смотрим что реально нужно клиенту
                    ;
                }
            }
            if (!$item->getTitle()) {
                // TODO: что-то левое
                continue;
            }

            $item->addFile($file);
            try {
                $this->em->persist($item);
                $this->em->flush();
            } catch (\Exception $e) {
                var_dump($e->getMessage());die();
            }
        }
    }

    protected function getSEFromName($fileName)
    {
        // TODO: это пока тестово
        if (preg_match('#s(\d\d)e(\d\d).*\.(avi|mkv)#i', $fileName, $m)) {
            return [(int)$m[1], (int)$m[2]];
        }

        return [false, false];
    }
}
