<?php

namespace App\Serializer\Normalizer;

use App\Entity\Show;
use App\Repository\Locale\BaseLocaleRepository;
use App\Repository\Locale\EpisodeLocaleRepository;
use App\Request\LocaleRequest;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ShowNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface, NormalizerAwareInterface
{
    private $normalizer;

    private BaseLocaleRepository $locale;
    private EpisodeLocaleRepository $episodeLocale;

    public function __construct(BaseLocaleRepository $locale, EpisodeLocaleRepository $episodeLocale)
    {
        $this->locale = $locale;
        $this->episodeLocale = $episodeLocale;
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
            'tmdb_id' => $object->getTmdb(),
            'tvdb_id' => $object->getTvdb(),
            'title' => $object->getTitle(),
            'year' => $object->getYear(),
            'slug' => $object->getSlug(),
            'original_language' => $object->getOrigLang(),
            'exist_translations' => $object->getExistTranslations(),
            'num_seasons' => $object->getNumSeasons(),
            'images' => $object->getImages()->getApiArray(),
            'rating' => $object->getRating()->getApiArray(),
        ];
        /** @var LocaleRequest $localeParams */
        $localeParams = $context['localeParams'];
        if ($localeParams->needLocale) {
            $l = $this->locale->findByMediaAndLocale($object, $localeParams->locale);
            if ($l) {
                $base['locale'] = $this->normalizer->normalize($l, $format, $context);
            }
        }
        $base['contextLocale'] = $this->findBestLocale($localeParams, $object);

        switch ($context['mode']) {
            case 'list':
                return $base;
            case 'item':
                $episodes = $this->normalizer->normalize($object->getEpisodes(), $format, $context + [
                        'episodesLocales' => $this->episodeLocale->findByShowAndLocale($object, $base['contextLocale']),
                        'locale' => $base['contextLocale'],
                    ]);
                $episodes = array_values(array_filter($episodes, static function ($episode) {
                    return !empty($episode['torrents']);
                }));
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
                        'episodes' => $episodes,
                    ]
                );
            default:
                return [];
        }
    }

    private function findBestLocale(LocaleRequest $localeParams, Show $object)
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

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Show;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
