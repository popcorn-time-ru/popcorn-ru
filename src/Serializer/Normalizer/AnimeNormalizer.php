<?php

namespace App\Serializer\Normalizer;

use App\Entity\Anime;
use App\Repository\TorrentRepository;
use App\Request\LocaleRequest;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class AnimeNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface, NormalizerAwareInterface
{
    private $normalizer;

    /** @var TorrentRepository */
    private $torrentRepo;

    public function __construct(ObjectNormalizer $normalizer, TorrentRepository $torrentRepo)
    {
        $this->normalizer = $normalizer;
        $this->torrentRepo = $torrentRepo;
    }

    public function setNormalizer(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($object, $format = null, array $context = array()): array
    {
        if (!$object instanceof Anime) {
            return [];
        }

        $base = [
            '_id' => $object->getKitsu(),
            'mal_id' => $object->getMal(),
            'title' => $object->getTitle(),
            'year' => $object->getYear(),
            'slug' => $object->getSlug(),
            'type' => $object->getType(),
            'genres' => $object->getGenres(),
            'images' => $object->getImages()->getApiArray(),
            'rating' => $object->getRating()->getApiArray(),
        ];
        /** @var LocaleRequest $localeParams */
        $localeParams = $context['localeParams'];
        if ($localeParams->needLocale) {
            $l = $object->getLocale($localeParams->locale);
            if ($l) {
                $base['locale'] = $this->normalizer->normalize($l, $format, $context);
            }
        }

        switch ($context['mode']) {
            case 'list':
                return $base;
            case 'item':
                if ($object->getType() === 'movie') {
                    $torrents = [];
                    /** @var LocaleRequest $localeParams */
                    $localeParams = $context['localeParams'];
                    foreach ($this->torrentRepo->getMediaTorrents($object, $localeParams->contentLocale) as $torrent) {
                        $torrents[$localeParams->contentLocale][$torrent->getQuality()] =
                            $this->normalizer->normalize($torrent, $format, $context);
                    }
                    $base['torrents'] = $torrents;
                } else { // "show"
                    $episodes = $this->normalizer->normalize($object->getEpisodes(), $format, $context);
                    $episodes = array_values(array_filter($episodes, static function ($episode) {
                        return !empty($episode['torrents']);
                    }));
                    $base = array_merge(
                        $base,
                        [
                            'episodes' => $episodes,
                            'num_seasons' => $object->getNumSeasons(),
                            'air_day' => $object->getAirDay(),
                            'air_time' => $object->getAirTime(),
                        ]
                    );
                }
                return array_merge(
                    $base,
                    [
                        '__v' => 0,
                        'synopsis' => $object->getSynopsis(),
                        'runtime' => $object->getRuntime(),
                        'country' => $object->getCountry(),
                        'network' => $object->getNetwork(),
                        'last_updated' => $object->getSynAt()->getTimestamp(),
                        'status' => $object->getStatus()
                    ]
                );
            default:
                return [];
        }
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Anime;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
