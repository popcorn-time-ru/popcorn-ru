<?php

namespace App\Service;

use App\Entity\Episode;
use App\Entity\Show;
use App\Entity\Torrent\ShowTorrent;
use App\Repository\TorrentRepository;
use App\Traktor\Client;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Contracts\Service\Attribute\Required;

class EpisodeService
{
    #[Required] public TorrentRepository $torrentRepo;
    #[Required] public MediaService $mediaInfo;
    #[Required] public EntityManagerInterface $em;
    #[Required] public LocaleService $localeService;
    #[Required] public Client $trakt;
    #[Required] public LoggerInterface $logger;

    public function link(UuidInterface $torrentId): void
    {
        $torrent = $this->torrentRepo->find($torrentId);
        if (!$torrent instanceof ShowTorrent) {
            return;
        }

        foreach ($torrent->getFiles() as $file) {
            [$s, $e] = $this->getSEFromName($file->getName(), $torrent->getShow());
            if ($s === false) {
                continue;
            }

            $item = $this->getEpisode($torrent->getShow(), $s, $e);
            if (!$item) {
                continue;
            }

            $item->addFile($file);
            $this->em->flush();
        }
    }

    public function getEpisode(Show $show, int $s, int $e): ?Episode
    {
        foreach ($show->getEpisodes() as $episode) {
            if ($episode->getSeason() === $s && $episode->getEpisode() === $e) {
                return $episode;
            }
        }

        $item = new Episode();
        $item
            ->setShow($show)
            ->setSeason($s)
            ->setEpisode($e)
        ;
        $this->em->persist($item);
        $show->addEpisode($item);
        $this->em->flush();

        $this->mediaInfo->updateEpisode($item);
        $this->em->flush();

        return $item;
    }

    protected function getSEFromName($filePathAndName, Show $show): array
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

        return [false, false];
    }
}
