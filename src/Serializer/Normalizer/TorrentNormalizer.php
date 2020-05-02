<?php

namespace App\Serializer\Normalizer;

use App\Entity\Torrent\BaseTorrent;
use App\Entity\Torrent\MovieTorrent;
use App\Entity\Torrent\ShowTorrent;
use App\Service\SpiderSelector;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class TorrentNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    /**
     * @var SpiderSelector
     */
    private $spiderSelector;

    /**
     * @var ObjectNormalizer
     */
    private $normalizer;

    public function __construct(ObjectNormalizer $normalizer, SpiderSelector $spiderSelector)
    {
        $this->normalizer = $normalizer;
        $this->spiderSelector = $spiderSelector;
    }

    public function normalize($object, $format = null, array $context = array()): array
    {
        if (!$object instanceof BaseTorrent) {
            return [];
        }
        $data = [
            'url' => $object->getUrl(),
            'seeds' => $object->getSeed(),
            'peers' => $object->getPeer(),
            'provider' => $object->getProvider(),
        ];
        if ($context['mode'] === 'list') {
            $data['title'] = $object->getProviderTitle();
            $provider = $this->spiderSelector->get($object->getProvider());
            $data['source'] = $provider ? $provider->getSource($object) : '';
        }

        if ($object instanceof ShowTorrent) {
            if (!empty($context['file'])) {
                $data['file'] = $context['file']->getName();
            }
            return $data;
        }
        if ($object instanceof MovieTorrent) {
            $data['size'] = $object->getSize();
            $data['filesize'] = $object->getFilesize();
        }
        return $data;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof BaseTorrent;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
