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
            // TODO: для сериалов с одним сезоном только серии
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
                        ->setTvdb($episodeInfo['id'] ?? random_int(100000, 1000000))
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
        $components = pathinfo($fileName);
        $dir = $components['dirname'];
        $file = $components['filename'];
        $ext = $components['extension'];
        if (!in_array(strtolower($ext), ['avi', 'mkv', 'mp4'])) {
            return [false, false];
        }
        $file = str_replace(["\'", '_', '.'], ["'", ' ', ' '], $file);

        // S01E02 S01 E02
        $patterns = [
            '#s(\d\d?)\s*e(\d\d?)#i', //S01 E01
            '#(?:\s|^)(\d\d?)[xXхХ](\d\d?)(?:\s|$)#iu', // ' 01x01 '
            '#\((\d\d?)[xXхХ](\d\d?)\)#iu', // (01x01)
        ];
        foreach($patterns as $pattern) {
            if (preg_match($pattern, $dir . '/' . $file, $m)) {
                return [(int) $m[1], (int) $m[2]];
            }
        }

        $th = '-?(?:th)?';
        $season = '(?:сезон|season|sezon)';
        $episode = '(?:серия|episode|seriya)';

        //S - EE серия
        if (preg_match('#(\d+) ?- ?(\d+) '.$episode.'#iu', $file, $m)) {
            return [(int)$m[1], (int)$m[2]];
        }
        if (preg_match('#(?:\S) - (\d+) '.$episode.'#iu', $file, $m)) {
            return [1, (int)$m[1]];
        }

        // где-то 1 сезон и потом 1 серия
        // аналогично сезон 1 1 серия
        // аналогично сезон 1 серия 1
        $patterns = [
            '#(\d+)'.$th.' '.$season.'.*(\d+)'.$th.' '.$episode.'#iu',
            '#(\d+)'.$th.' '.$season.'.*'.$episode.' (\d+)'.$th.'#iu',
            '#'.$season.' (\d+)'.$th.'.*(\d+)'.$th.' '.$episode.'#iu',
            '#'.$season.' (\d+)'.$th.'.*'.$episode.' (\d+)'.$th.'#iu',
        ];
        foreach($patterns as $pattern) {
            if (preg_match($pattern, $dir . '/' . $file, $m)) {
                return [(int) $m[1], (int) $m[2]];
            }
        }

        return [false, false];
    }
}
