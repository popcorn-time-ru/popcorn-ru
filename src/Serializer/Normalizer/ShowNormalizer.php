<?php

namespace App\Serializer\Normalizer;

use App\Entity\Show;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ShowNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface, NormalizerAwareInterface
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
        if (!$object instanceof Show) {
            return [];
        }

        $base = [
            '_id' => $object->getImdb(),
            'imdb_id' => $object->getImdb(),
            'tvdb_id' => $object->getTvdb(),
            'title' => $object->getTitle(),
            'year' => $object->getYear(),
            'slug' => $object->getSlug(),
            'num_seasons' => $object->getNumSeasons(),
            'images' => $object->getImages()->getApiArray(),
            'rating' => $object->getRating()->getApiArray(),
        ];

        switch ($context['mode']) {
            case 'list':
                return $base;
            case 'item':
                return array_merge(
                    $base,
                    [
                        '__v' => 0,
                        'synopsis' => $object->getSynopsis(),
                        'runtime' => $object->getRuntime(),
                        'country' => $object->getCountry(),
                        'network' => $object->getNetwork(),
                        'last_updated' => $object->getSynAt()->getTimestamp(),
                        'air_day' => $object->getAirDay(),
                        'air_time' => $object->getAirTime(),
                        'status' => $object->getStatus(),
                        'genres' => $object->getGenres(),
                        'episodes' => $this->normalizer->normalize($object->getEpisodes(), $format, $context),
                    ]
                );
            default:
                return [];
        }
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Show;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
