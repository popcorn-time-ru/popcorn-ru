<?php

namespace App\Service\Search;

use App\Entity\Anime;
use App\Entity\BaseMedia;
use App\Entity\Movie;
use App\Entity\Show;
use App\Request\LocaleRequest;
use App\Request\PageRequest;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Elastica\Query;
use FOS\ElasticaBundle\Finder\TransformedFinder;

class Elastic implements SearchInterface
{
    const MAX_LIMIT = 10000;

    protected TransformedFinder $moviesFiner;
    protected TransformedFinder $showFiner;
    protected TransformedFinder $animesFiner;

    /**
     * Elastic constructor.
     *
     * @param TransformedFinder $moviesFiner
     * @param TransformedFinder $showFiner
     * @param TransformedFinder $animesFiner
     */
    public function __construct(TransformedFinder $moviesFiner, TransformedFinder $showFiner, TransformedFinder $animesFiner)
    {
        $this->moviesFiner = $moviesFiner;
        $this->showFiner = $showFiner;
        $this->animesFiner = $animesFiner;
    }

    public static function isIndexMovie(Movie $movie): bool
    {
        return true;
    }

    public static function isIndexShow(Show $show): bool
    {
        return $show->getEpisodes()->count() > 0;
    }

     public static function isIndexAnime(Anime $anime)
    {
        return $anime->getEpisodes()->count() > 0;
    }

    public function search(QueryBuilder $qb, ClassMetadata $class, PageRequest $pageRequest, LocaleRequest $localeParams, int $offset, int $limit): array
    {
        if (($offset + $limit) > self::MAX_LIMIT) {
            return [];
        }

        $boolQuery = new \Elastica\Query\BoolQuery();

        if ($pageRequest->keywords) {
            $nestedLocale = new \Elastica\Query\Nested();
            $nestedLocale->setPath('locales');

            $fieldQuery = new \Elastica\Query\MatchPhrasePrefix();
            $fieldQuery->setFieldQuery('locales.title', $pageRequest->keywords);
            $nestedLocale->setQuery($fieldQuery);
            $boolQuery->addShould($nestedLocale);

            $fieldQuery = new \Elastica\Query\MatchPhrasePrefix();
            $fieldQuery->setFieldQuery('title', $pageRequest->keywords);
            $boolQuery->addShould($fieldQuery);

            $imdb = new \Elastica\Query\Term();
            $imdb->setTerm('imdb', $pageRequest->keywords);
            $boolQuery->addShould($imdb);

            $boolQuery->setMinimumShouldMatch(1);
        }
        if ($pageRequest->genre) {
            $genre = new \Elastica\Query\Term();
            $genre->setTerm('genres', $pageRequest->genre);
            $boolQuery->addMust($genre);
        }

        if ($localeParams->contentLocales) {
            $langs = new \Elastica\Query\BoolQuery();
            foreach ($localeParams->contentLocales as $locale) {
                $lang = new \Elastica\Query\Term();
                $lang->setTerm('existTranslations', $locale);
                $langs->addShould($lang);
            }
            $langs->setMinimumShouldMatch(1);
            $boolQuery->addMust($langs);
        }

        $query = Query::create($boolQuery);
        $query->setFrom($offset)->setSize($limit);

        $query->setSort($this->buildSort($pageRequest->sort, $pageRequest->order));

        $finder = null;
        switch ($class->getName()) {
            case Movie::class:
                $finder = $this->moviesFiner;
                break;
            case Show::class:
                $finder = $this->showFiner;
                break;
            case Anime::class:
                $finder = $this->animesFiner;
                break;
        }
        /** @var BaseMedia[] $result */
        return $finder ? $finder->find($query) : [];
    }

    private function buildSort(string $sort, string $order): array
    {
        $n = ['nested' => ['path' => 'rating']];
        return match ($sort) {
            'title', 'name' => ['title' => $order, 'locales.title' => $order],
            'popularity' => [
                'rating.popularity' => $n + ['order' => $order],
                'rating.watchers' => $n + ['order' => $order],
            ],
            'rating' => [
                'rating.weightRating' => $n + ['order' => $order],
            ],
            'released', 'updated' => ['released' => $order],
            'last added' => ['created' => $order],
            'trending' => [
                'rating.watching' => $n + ['order' => $order],
                'rating.watchers' => $n + ['order' => $order],
            ],
            'year' => ['year' => $order],
            default => [
                'rating.popularity' => $n + ['order' => 'desc'],
                'rating.watchers' => $n + ['order' => 'desc'],
            ],
        };
    }
}
