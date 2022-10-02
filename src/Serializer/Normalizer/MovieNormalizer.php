<?php

namespace App\Serializer\Normalizer;

use App\Entity\Movie;
use App\Repository\Locale\BaseLocaleRepository;
use App\Repository\TorrentRepository;
use App\Request\LocaleRequest;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class MovieNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface, NormalizerAwareInterface
{
    private $normalizer;

    private TorrentRepository $torrents;
    private BaseLocaleRepository $locale;

    public function __construct(TorrentRepository $torrents, BaseLocaleRepository $locale)
    {
        $this->torrents = $torrents;
        $this->locale = $locale;
    }

    public function setNormalizer(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($object, $format = null, array $context = array()): array
    {
        if (!$object instanceof Movie) {
            return [];
        }
        $torrents = [];
        /** @var LocaleRequest $localeParams */
        $localeParams = $context['localeParams'];
        foreach ($this->torrents->getMediaTorrents($object, $localeParams->contentLocales) as $torrent) {
            $torrents[$torrent->getLanguage()][$torrent->getQuality()] =
                $this->normalizer->normalize($torrent, $format, $context);
        }
        $locale = [];
        if ($localeParams->needLocale) {
            $l = $this->locale->findByMediaAndLocale($object, $localeParams->locale);
            if ($l) {
                $locale['locale'] = $this->normalizer->normalize($l, $format, $context);
            }
        }

        return [
            '_id' => $object->getImdb(),
            'imdb_id' => $object->getImdb(),
            'tmdb_id' => $object->getTmdb(),
            'title' => $object->getTitle(),
            'year' => $object->getYear(),
            'original_language' => $object->getOrigLang(),
            'exist_translations' => $object->getExistTranslations(),
            'contextLocale' => $this->findBestLocale($localeParams, $object),
            'synopsis' => $object->getSynopsis(),
            'runtime' => $object->getRuntime(),
            'released' => $object->getReleased()->getTimestamp(),
            'certification' => $object->getCertification(),
            'torrents' => $torrents,
            'trailer' => $object->getTrailer(),
            'genres' => $object->getGenres(),
            'images' => $object->getImages()->getApiArray(),
            'rating' => $object->getRating()->getApiArray(),
        ] + $locale;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Movie;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    private function findBestLocale(LocaleRequest $localeParams, Movie $object)
    {
        $locales = $localeParams->contentLocales ?: $object->getExistTranslations();
        $locales = array_intersect($locales, $object->getExistTranslations());
        if (in_array($localeParams->bestContentLocale, $locales)) {
            return $localeParams->bestContentLocale;
        }
        if (in_array($object->getOrigLang(), $locales)) {
            return $object->getOrigLang();
        }
        if (in_array('en', $locales)) {
            return 'en';
        }
        return current($locales);
    }
}
