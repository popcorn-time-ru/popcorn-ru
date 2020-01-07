<?php

namespace App\Serializer\Normalizer;

use App\Entity\Locale\EpisodeLocale;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class EpisodeLocaleNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface, NormalizerAwareInterface
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
        if (!$object instanceof EpisodeLocale) {
            return [];
        }

        switch ($context['mode']) {
            default:
                return [
                    'title' => $object->getTitle(),
                    'overview' => $object->getOverview(),
                ];
        }
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof EpisodeLocale;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
