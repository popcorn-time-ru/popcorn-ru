<?php

namespace App\Service;

use App\Entity\Anime;
use App\Entity\Episode\AnimeEpisode;
use App\Entity\Show;
use App\Entity\Episode\ShowEpisode;
use App\Entity\Torrent\AnimeTorrent;
use App\Entity\Torrent\ShowTorrent;
use App\Repository\TorrentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;

class EpisodeService
{
    /** @var TorrentRepository */
    protected $torrentRepo;

    /** @var MediaService */
    protected $mediaInfo;

    /** @var EntityManagerInterface */
    protected $em;

    /** @var LocaleService */
    protected $localeService;

    /** @var \App\Traktor\Client */
    private $trakt;

    /** @var LoggerInterface */
    private $logger;

    /**
     * EpisodeService constructor.
     *
     * @param TorrentRepository      $torrentRepo
     * @param MediaService           $mediaInfo
     * @param EntityManagerInterface $em
     * @param LocaleService          $localeService
     * @param LoggerInterface        $logger
     * @param \App\Traktor\Client    $trakt
     */
    public function __construct(
        TorrentRepository $torrentRepo,
        MediaService $mediaInfo,
        EntityManagerInterface $em,
        LocaleService $localeService,
        LoggerInterface $logger,
        \App\Traktor\Client $trakt
    )
    {
        $this->torrentRepo = $torrentRepo;
        $this->mediaInfo = $mediaInfo;
        $this->em = $em;
        $this->localeService = $localeService;
        $this->trakt = $trakt;
        $this->logger = $logger;
    }

    public function link(UuidInterface $torrentId): void
    {
        $torrent = $this->torrentRepo->find($torrentId);
        if ($torrent instanceof ShowTorrent) {
            foreach ($torrent->getFiles() as $file) {
                [$s, $e] = $this->getSEFromName($file->getName(), $torrent->getShow());
                if ($s === false) {
                    continue;
                }

                $this->logger->debug("Parsed season/episode", ["filepath" => $file->getName(), "season" => $s, "episode" => $e]);

                $item = $this->getEpisode($torrent->getShow(), $s, $e);
                if (!$item) {
                    $this->logger->debug("Episode not found in season.", ["showId" => $torrent->getShow()->getId(), "season" => $s, "episode" => $e]);
                    continue;
                }

                $item->addFile($file);
                $this->em->flush();
            }
        }
        if ($torrent instanceof AnimeTorrent) {
            foreach ($torrent->getFiles() as $file) {
                [$s, $e] = $this->getSEFromAnimeName($file->getName(), $torrent->getAnime());
                if ($s === false) {
                    continue;
                }

                $this->logger->debug("Parsed season/episode", ["filepath" => $file->getName(), "season" => $s, "episode" => $e]);

                $item = $this->getAnimeEpisode($torrent->getAnime(), $s, $e);
                if (!$item) {
                    $this->logger->debug("Episode not found in season.", ["showId" => $torrent->getAnime()->getId(), "season" => $s, "episode" => $e]);
                    continue;
                }

                $item->addFile($file);
                $this->em->flush();
            }
        }
    }

    protected $showCache = [];
    protected $animeCache = [];
    public function getEpisode(Show $show, int $s, int $e): ?ShowEpisode
    {
        $item = null;
        foreach ($show->getEpisodes() as $episode) {
            if ($episode->getSeason() === $s && $episode->getEpisode() === $e) {
                $item = $episode;
                break;
            }
        }
        if (!$item) {
            $item = new ShowEpisode();
            $item
                ->setShow($show)
                ->setSeason($s)
                ->setEpisode($e)
            ;
        }

        $key = $show->getImdb().':'.$s;
        if (empty($this->showCache[$key])) {
            $this->showCache[$key] = $this->mediaInfo
                ->getSeasonEpisodes($show, $s);
        }
        $found = false;
        foreach ($this->showCache[$key] as $episodeInfo) {
            if ($episodeInfo['episode_number'] == $e) {
                // Hack for unknown tvdbID in track
                // tvdbID now is primary key on client
                if ($item->getTvdb() <= 0) {
                    try {
                        $trakt = $this->trakt->get("shows/{$show->getImdb()}/seasons/{$s}/episodes/{$e}");
                        $item->setTvdb($trakt->ids->tvdb ?? -$trakt->ids->trakt ?? 0);
                    } catch (\Exception $exception) {
                        $this->logger->error($exception->getMessage());
                    }
                }
                // if (!$item->getTvdb()) {
                //     continue;
                // }

                $item
                    ->setTitle($episodeInfo['name'] ?: '')
                    ->setOverview($episodeInfo['overview'] ?: '')
                    ->setFirstAired((new \DateTime($episodeInfo['air_date'] ?? 'now'))->getTimestamp())
                ;
                $found = true;
            }
        }
        if (!$found) {
            return null;
        }

        $this->em->persist($item);
        $show->addEpisode($item);
        $this->em->flush();

        if ($this->localeService->needFillEpisode($item)) {
            $translations = $this->mediaInfo->getEpisodeTranslations($show, $s, $e);
            $this->localeService->fillEpisode($item, $translations);
        }

        $this->em->flush();

        return $item;
    }

    public function getAnimeEpisode(Anime $anime, int $s, int $e): ?AnimeEpisode
    {
        $item = null;
        foreach ($anime->getEpisodes() as $episode) {
            if ($episode->getSeason() === $s && $episode->getEpisode() === $e) {
                $item = $episode;
                break;
            }
        }
        if (!$item) {
            $item = new AnimeEpisode();
            $item
                ->setAnime($anime)
                ->setSeason($s)
                ->setEpisode($e)
            ;
        }

        $key = $anime->getKitsu().':'.$s;
        if (empty($this->animeCache[$key])) {
            $this->animeCache[$key] = $this->mediaInfo
                ->getSeasonEpisodes($anime, $s); // TODO
        }
        $found = false;
        foreach ($this->animeCache[$key] as $episodeInfo) {
            if ($episodeInfo['episode_number'] == $e) {
                // // Hack for unknown tvdbID in track
                // // tvdbID now is primary key on client
                // if ($item->getTvdb() <= 0) {
                //     try {
                //         $trakt = $this->trakt->get("shows/{$show->getImdb()}/seasons/{$s}/episodes/{$e}");
                //         $item->setTvdb($trakt->ids->tvdb ?? -$trakt->ids->trakt ?? 0);
                //     } catch (\Exception $exception) {
                //         $this->logger->error($exception->getMessage());
                //     }
                // }
                // // if (!$item->getTvdb()) {
                // //     continue;
                // // }

                $item
                    ->setTitle($episodeInfo['name'] ?: '')
                    ->setOverview($episodeInfo['overview'] ?: '')
                    ->setFirstAired((new \DateTime($episodeInfo['air_date'] ?? 'now'))->getTimestamp())
                ;
                $found = true;
            }
        }
        if (!$found) {
            return null;
        }

        $this->em->persist($item);
        $anime->addEpisode($item);
        $this->em->flush();

        // if ($this->localeService->needFillEpisode($item)) {
        //     $translations = $this->mediaInfo->getEpisodeTranslations($show, $s, $e);
        //     $this->localeService->fillEpisode($item, $translations);
        // }

        $this->em->flush();

        return $item;
    }

    protected function getSEFromName($filePathAndName, Show $show)
    {
        $components = pathinfo($filePathAndName);
        $dir = $components['dirname'];
        $file = $components['filename'];
        $ext = $components['extension'] ?? '';
        if (!in_array(strtolower($ext), ['avi', 'mkv', 'mp4'])) {
            return [false, false];
        }

        $file = str_replace(["\'", '_', '.'], ["'", ' ', ' '], $file);
        $dir = str_replace(["\'", '_', '.'], ["'", ' ', ' '], $dir);

        // S01E02 S01 E02
        $patterns = [
            '#s\s*(\d+)\s*ep?\s*(\d+)#i', //S01 E01
            '#(?:\s|^)(\d+)\s*[xXхХ]\s*(\d+)(?:\s|$)#iu', // ' 01x01 '
            '#\((\d+)[xXхХ](\d+)\)#iu', // (01x01)
            '#\s*(\d+)[xXхХ](\d+)\s*#iu', // (01x01)
        ];
        foreach($patterns as $pattern) {
            if (preg_match($pattern, $dir . '/' . $file, $m)) {
                return [(int) $m[1], (int) $m[2]];
            }
        }
        $patterns = [
            '#ep?\s*(\d+)\s*s\s*(\d+)#i', //E01 S01
        ];
        foreach($patterns as $pattern) {
            if (preg_match($pattern, $dir . '/' . $file, $m)) {
                return [(int) $m[2], (int) $m[1]];
            }
        }

        $th = '-?(?:th)?';
        $season = '(?:сезон|season|sezon)';
        $episode = '(?:серия|episode|ser[iyj]+a)';

        //S - EE серия
        if (preg_match('#(\d+) ?- ?(\d+) '.$episode.'#iu', $file, $m)) {
            return [(int)$m[1], (int)$m[2]];
        }

        // где-то 1 сезон и потом 1 серия
        // аналогично сезон 1 1 серия
        // аналогично сезон 1 серия 1
        $patterns = [
            '#S(\d+).*?(\d+)'.$th.' '.$episode.'#iu',
            '#(\d+)'.$th.' '.$season.'.*?(\d+)'.$th.' '.$episode.'#iu',
            '#(\d+)'.$th.' '.$season.'.*?'.$episode.' (\d+)'.$th.'#iu',
            '#'.$season.' (\d+)'.$th.'.*?E(\d+)#iu',
            '#'.$season.' (\d+)'.$th.'.*?(\d+)'.$th.' '.$episode.'#iu',
            '#'.$season.' (\d+)'.$th.'.*?'.$episode.' (\d+)'.$th.'#iu',
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
                '#\s+e(\d+)#i', //S01 E01
                '#(?:\s|^)(\d+)(?:\s|$)#iu', // ' 01 '
                '#\((\d+)\s+(?:iz|of)\s+(?:\d+)\)#iu', // (01x01)
            ];
            foreach($patterns as $pattern) {
                if (preg_match($pattern, $file, $m)) {
                    return [1, (int) $m[1]];
                }
            }
            if (preg_match('#e(\d+)#iu', $file, $m)) {
                return [1, (int) $m[1]];
            }
            if (preg_match('#(?:\S) - (\d+) ' . $episode . '#iu', $file, $m)) {
                return [1, (int) $m[1]];
            }
        }

        $this->logger->debug("Failed to parse season/episode", ["filename" =>  $file]);

        return [false, false];
    }

    protected function getSEFromAnimeName($filePathAndName, Anime $show)
    {
        $components = pathinfo($filePathAndName);
        $dir = $components['dirname'];
        $file = $components['filename'];
        $ext = $components['extension'] ?? null;

        if (!$ext || !in_array(strtolower($ext), ['avi', 'mkv', 'mp4'])) {
            return [false, false];
        }

        if (preg_match('#(/SP[s]?\b|/CD[s]?\b|/Special[s]?\b|/Extra[s]?\b|/BK\b)#i', $dir)) {
            return [false, false];
        }

        $filename = $file . "." . $ext;
        $anitomy = anitomy_parse($filename);
        $episode = @$anitomy["episode_number"];
        if ($episode && preg_match("#(\d+)#", $episode, $n)) {
            $season = 1;
            if (preg_match('#\b[sS]eason[\s]+(\d+)\b#', $filename, $m)) {
                $season = (int)$m[1];
            } elseif (preg_match('#\b[sS](\d+)\b#', $filename, $m)) {
                $season = (int)$m[1];
            }
            return [$season, (int) $n[1]];
        }

        return [false, false];
    }
}
