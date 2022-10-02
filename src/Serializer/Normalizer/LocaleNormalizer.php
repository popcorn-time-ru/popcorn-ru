<?php

namespace App\Serializer\Normalizer;

use App\Entity\Locale\BaseLocale;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class LocaleNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface, NormalizerAwareInterface
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
        if (!$object instanceof BaseLocale) {
            return [];
        }

        switch ($context['mode']) {
            default:
                return [
                    'title' => $object->getTitle(),
                    'synopsis' => $object->getSynopsis(),
                ] + $object->getImages()->getApiArray();
        }
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof BaseLocale;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
