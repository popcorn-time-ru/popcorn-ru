<?php

namespace App\Service;

use App\Entity\BaseMedia;
use App\Entity\Movie;
use App\Entity\Show;
use App\Entity\Torrent\AnimeTorrent;
use App\Entity\Torrent\BaseTorrent;
use App\Entity\Torrent\MovieTorrent;
use App\Entity\Torrent\ShowTorrent;
use App\Processors\TorrentFilesLinkProcessor;
use App\Processors\TorrentActiveProcessor;
use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use App\Repository\AnimeRepository;
use App\Repository\TorrentRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Prometheus\CollectorRegistry;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Service\Attribute\Required;

class TorrentService
{
    #[Required] public MediaService $mediaInfo;
    #[Required] public EntityManagerInterface $em;
    #[Required] public TorrentRepository $torrentRepo;
    #[Required] public MovieRepository $movieRepo;
    #[Required] public ShowRepository $showRepo;
    #[Required] public AnimeRepository $animeRepo;
    #[Required] public ProducerInterface $producer;
    #[Required] public LoggerInterface $logger;
    #[Required] public ContainerInterface $container;
    #[Required] public CollectorRegistry $cr;

    public function searchMovieByTitleAndYear(string $title, int $year)
    {
        return $this->mediaInfo->searchMovieByTitleAndYear($title, $year);
    }
    public function searchShowByTitle(string $title)
    {
        return $this->mediaInfo->searchShowByTitle($title);
    }
    public function searchAnimeByTitle(string $title, ?string $year = null)
    {
        return $this->mediaInfo->searchAnimeByTitle($title, $year);
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

    public function getMediaByKitsu(string $kitsuId): ?BaseMedia
    {
        $anime = $this->animeRepo->findByKitsu($kitsuId);

        if (!$anime) {
            $anime = $this->mediaInfo->fetchByKitsu($kitsuId);
            if (!$anime) {
                $this->logger->warning('Not found anime', ['kitsu' => $kitsuId]);
                return null;
            }

            // IMDB ID is a unique key, so don't try to insert it twice
            $imdbId = $anime->getImdb();
            if ($imdbId) {
                $animeImdb = $this->animeRepo->findByImdb($imdbId);
                if ($animeImdb) {
                    return $animeImdb;
                }
            } else {
                $anime->setImdb("kitsu-" . $kitsuId);
            }

            $anime->sync();
            $this->em->persist($anime);
            $this->em->flush();
        }

        return $anime;
    }

    public function findExistOrCreateTorrent(string $provider, string $externalId, BaseTorrent $new): BaseTorrent
    {
        $torrent = $this->torrentRepo->findByProviderAndExternalId(
            $provider,
            $externalId
        );

        if ($torrent) {
            return $torrent;
        }

        $metric = $this->cr->getOrRegisterCounter('popcorn', 'createTorrent', 'torrent created', ['provider']);
        $metric->inc([$provider]);

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
        $this->logger->debug("Indexing torrent", ['title' => $torrent->getProviderTitle(), 'provider', $torrent->getProvider()]);
        $torrent->sync();
        $torrent->setActive(true);
        $this->em->flush();

        $torrent->getMedia()->addExistTranslation($torrent->getLanguage());
        $this->em->flush();

        $metric = $this->cr->getOrRegisterCounter('popcorn', 'updateTorrent', 'torrent updated', ['provider']);
        $metric->inc([$torrent->getProvider()]);

        $torrentMessage = new \Enqueue\Client\Message(JSON::encode([
            'torrentId' => $torrent->getId()->toString(),
        ]));
        $torrentMessage->setDelay(3600);

        $topic = TorrentActiveProcessor::TOPIC;
        if ($torrent instanceof ShowTorrent || $torrent instanceof AnimeTorrent) {
            $topic = TorrentFilesLinkProcessor::TOPIC;
        }
        $this->producer->sendEvent($topic, $torrentMessage);
    }

    public function deleteTorrent(string $provider, string $externalId)
    {
        $torrent = $this->torrentRepo->findByProviderAndExternalId(
            $provider,
            $externalId
        );

        if (!$torrent) {
            return;
        }

        $metric = $this->cr->getOrRegisterCounter('popcorn', 'deleteTorrent', 'torrent deleted', ['provider']);
        $metric->inc([$torrent->getProvider()]);

        $media = $torrent->getMedia();
        $this->torrentRepo->delete($torrent);

        $media->syncTranslations();
        $this->em->flush();

        $this->selectActive($media, $torrent->getLanguage(), false);
    }

    public function updateActive(UuidInterface $torrentId)
    {
        $torrent = $this->torrentRepo->find($torrentId);
        if (!$torrent) {
            return;
        }
        $media = $torrent->getMedia();
        if ($media->getLastActiveCheck()->diff(new DateTime())->days > 3) {
            $this->selectActive($media, $torrent->getLanguage());
        }
    }

    protected function selectActive(BaseMedia $media, string $language, bool $onlyActive = true)
    {
        if ($media instanceof Movie) {
            $this->selectActiveForMovie($media, $language, $onlyActive);
        } else {
            $this->selectActiveForShow($media, $language, $onlyActive);
        }
        $media->setLastActiveCheck(new DateTime());
        $this->em->flush();
    }

    protected function selectActiveForMovie(Movie $movie, string $language, bool $onlyActive = true)
    {
        /** @var BaseTorrent[] $active */
        $active = [];
        foreach ($this->torrentRepo->getMediaTorrents($movie, [$language], $onlyActive) as $torrent) {
            $torrent->setActive(false);
            if (empty($active[$torrent->getQuality()])
                || $this->needReplaceTorrent($active[$torrent->getQuality()], $torrent)) {
                $active[$torrent->getQuality()] = $torrent;
            }
        }
        /** @var BaseTorrent[] $all */
        foreach ($active as $q => $t) {
            $t->setActive(true);
        }
    }

    protected function selectActiveForShow(Show $show, string $language, bool $onlyActive = true)
    {
        /** @var BaseTorrent[][] $active */
        $active = [];
        foreach ($show->getEpisodes() as $episode) {
            $key = $episode->getSeason() . ':' . $episode->getEpisode();
            foreach ($this->torrentRepo->getEpisodeTorrents($episode, [$language], $onlyActive) as $torrent) {
                $torrent->setActive(false);
                if (empty($active[$key][$torrent->getQuality()])
                    || $this->needReplaceTorrent($active[$key][$torrent->getQuality()], $torrent)) {
                    $active[$key][$torrent->getQuality()] = $torrent;
                }
            }
            foreach ($this->torrentRepo->getMediaTorrents($show, [$language], $onlyActive) as $torrent) {
                /** @var ShowTorrent $torrent */
                $torrent->setActive(false);
                $file = null;
                foreach ($torrent->getFiles() as $torrentFile) {
                    if ($torrentFile->isEpisode($episode)) {
                        $file = $torrentFile;
                        break;
                    }
                }

                if (!$file) {
                    continue;
                }
                if (empty($active[$key][$torrent->getQuality()])
                    || $this->needReplaceTorrent($active[$key][$torrent->getQuality()], $torrent)) {
                    $active[$key][$torrent->getQuality()] = $torrent;
                }
            }
        }
        /** @var BaseTorrent[] $all */
        foreach ($active as $key => $ql) {
            foreach ($ql as $q => $t) {
                $t->setActive(true);
            }
        }
    }

    protected function needReplaceTorrent(BaseTorrent $current, BaseTorrent $new): bool
    {
        /** @var SpiderSelector $selector */
        $selector = $this->container->get(SpiderSelector::class);
        $priorityCurrent = $selector->get($current->getProvider())->getPriority($current);
        $priorityNew = $selector->get($new->getProvider())->getPriority($new);

        if ($priorityCurrent !== $priorityNew) {
            return $priorityCurrent < $priorityNew;
        }
        return $current->getPeer() < $new->getPeer();
    }
}
