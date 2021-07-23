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
        $provider = $this->spiderSelector->get($object->getProvider());
        $data = [
            'url' => $object->getUrl(),
            'provider' => $object->getProvider(),
            'source' => $provider ? $provider->getSource($object) : '',
        ];
        if ($object instanceof MovieTorrent) {
            $data['seed'] = $object->getSeed();
            $data['peer'] = $object->getPeer();
        } else {
            $data['seeds'] = $object->getSeed();
            $data['peers'] = $object->getPeer();
        }
        if ($context['mode'] === 'torrents') {
            $data['title'] = $object->getProviderTitle();
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
