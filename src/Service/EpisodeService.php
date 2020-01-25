<?php

namespace App\Service;

use App\Entity\Episode;
use App\Entity\Show;
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

    /** @var LocaleService */
    protected $localeService;

    /**
     * EpisodeService constructor.
     *
     * @param TorrentRepository      $torrentRepo
     * @param TmdbExtractor          $movieInfo
     * @param EntityManagerInterface $em
     * @param LocaleService          $localeService
     */
    public function __construct(
        TorrentRepository $torrentRepo,
        TmdbExtractor $movieInfo,
        EntityManagerInterface $em,
        LocaleService $localeService
    )
    {
        $this->torrentRepo = $torrentRepo;
        $this->movieInfo = $movieInfo;
        $this->em = $em;
        $this->localeService = $localeService;
    }

    public function link(UuidInterface $torrentId): void
    {
        $torrent = $this->torrentRepo->find($torrentId);
        if (!$torrent instanceof ShowTorrent) {
            return;
        }

        $showInfo = [];
        foreach ($torrent->getFiles() as $file) {
            [$s, $e] = $this->getSEFromName($file->getName(), $torrent->getShow());
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
                        ->setTitle($episodeInfo['name'] ?: '')
                        ->setOverview($episodeInfo['overview'] ?: '')
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
            $this->em->persist($item);

            if ($this->localeService->needFillEpisode($item)) {
                $translations = $this->movieInfo->getEpisodeTranslations($torrent->getShow(), $s, $e);
                $this->localeService->fillEpisode($item, $translations);
            }

            $this->em->flush();
        }
    }

    protected function getSEFromName($filePathAndName, Show $show)
    {
        $components = pathinfo($filePathAndName);
        $dir = $components['dirname'];
        $file = $components['filename'];
        $ext = $components['extension'];
        if (!in_array(strtolower($ext), ['avi', 'mkv', 'mp4'])) {
            return [false, false];
        }

        $file = str_replace(["\'", '_', '.'], ["'", ' ', ' '], $file);
        $dir = str_replace(["\'", '_', '.'], ["'", ' ', ' '], $dir);

        // S01E02 S01 E02
        $patterns = [
            '#s\s*(\d\d?)\s*ep?\s*(\d\d?)#i', //S01 E01
            '#(?:\s|^)(\d\d?)\s*[xXхХ]\s*(\d\d?)(?:\s|$)#iu', // ' 01x01 '
            '#\((\d\d?)[xXхХ](\d\d?)\)#iu', // (01x01)
            '#\s*(\d\d?)[xXхХ](\d\d?)\s*#iu', // (01x01)
        ];
        foreach($patterns as $pattern) {
            if (preg_match($pattern, $dir . '/' . $file, $m)) {
                return [(int) $m[1], (int) $m[2]];
            }
        }
        $patterns = [
            '#ep?\s*(\d\d?)\s*s\s*(\d\d?)#i', //E01 S01
        ];
        foreach($patterns as $pattern) {
            if (preg_match($pattern, $dir . '/' . $file, $m)) {
                return [(int) $m[2], (int) $m[1]];
            }
        }

        $th = '-?(?:th)?';
        $season = '(?:сезон|season|sezon)';
        $episode = '(?:серия|episode|seri?y?a)';

        //S - EE серия
        if (preg_match('#(\d+) ?- ?(\d+) '.$episode.'#iu', $file, $m)) {
            return [(int)$m[1], (int)$m[2]];
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

        if (preg_match('#^(\d+)(?:$|\s+.*)#', $file, $e)) {
            if ($show->getNumSeasons() === 1) {
                return [1, (int) $e[1]];
            }
            // эпизод у нас только число - ищем сезон
            $patterns = [
                '#(\d+)'.$th.' '.$season.'#iu',
                '#'.$season.' (\d+)'.$th.'#iu',
            ];
            foreach($patterns as $pattern) {
                if (preg_match($pattern, $dir, $m)) {
                    return [(int) $m[1], (int) $e[1]];
                }
            }
        }
        if ($show->getNumSeasons() === 1) {
            $patterns = [
                '#\s+e(\d\d?)#i', //S01 E01
                '#(?:\s|^)(\d\d?)(?:\s|$)#iu', // ' 01 '
                '#\((\d\d?)\s+(?:iz|of)\s+(?:\d\d?)\)#iu', // (01x01)
            ];
            foreach($patterns as $pattern) {
                if (preg_match($pattern, $file, $m)) {
                    return [1, (int) $m[1]];
                }
            }
            if (preg_match('#e(\d\d)#iu', $file, $m)) {
                return [1, (int) $m[1]];
            }
            if (preg_match('#(?:\S) - (\d+) ' . $episode . '#iu', $file, $m)) {
                return [1, (int) $m[1]];
            }
        }

        return [false, false];
    }
}
