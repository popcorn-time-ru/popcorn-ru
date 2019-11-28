<?php

namespace App\Serializer\Normalizer;

use App\Entity\Torrent;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class TorrentNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    private $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($object, $format = null, array $context = array()): array
    {
        if (!$object instanceof Torrent) {
            return [];
        }
        return [
            'url' => $object->getUrl(),
            'seed' => $object->getSeed(),
            'peer' => $object->getPeer(),
            'size' => $object->getSize(),
            'filesize' => $object->getFilesize(),
            'provider' => $object->getProvider(),
        ];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Torrent;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
