<?php

namespace App\Serializer\Normalizer;

use App\Entity\Episode;
use App\Entity\Show;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class EpisodeNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface, NormalizerAwareInterface
{
    private $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function setNormalizer(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($object, $format = null, array $context = array()): array
    {
        if (!$object instanceof Episode) {
            return [];
        }

        $torrents = [];
        foreach ($object->getShow()->getTorrents() as $torrent) {
            $file = null;
            foreach ($torrent->getFiles() as $torrentFile) {
                foreach ($object->getFiles() as $objectFile) {
                    if ($objectFile->getId() === $torrentFile->getId()) {
                        $file = $objectFile;
                        break 2;
                    }
                }
            }

            if (!$file) {
                continue;
            }
            // force english
            $torrents[$torrent->getQuality()] =
                $this->normalizer->normalize($torrent, $format, $context + ['file' => $file]);
        }
        if (empty($torrents['0'])) {
            $torrents['0'] = current($torrents);
        }

        $locale = [];
        if (!empty($context['locale'])) {
            $l = $object->getLocale($context['locale']);
            if ($l) {
                $locale['locale'][$context['locale']] = $this->normalizer->normalize($l, $format, $context);
            }
        }

        switch ($context['mode']) {
            case 'item':
                return [
                    'date_based' => false,
                    'season' => $object->getSeason(),
                    'episode' => $object->getEpisode(),
                    'first_aired' => $object->getFirstAired(),
                    'title' => $object->getTitle(),
                    'overview' => $object->getOverview(),
                    'watched' => [
                        'watched' => false,
                    ],
                    'tvdb_id' => (int)$object->getTvdb(),
                    'torrents' => $torrents,
                ] + $locale;
            default:
                return [];
        }
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Episode;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
