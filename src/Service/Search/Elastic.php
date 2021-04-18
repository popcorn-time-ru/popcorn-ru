<?php

namespace App\Service\Search;

use App\Entity\BaseMedia;
use App\Entity\Movie;
use App\Entity\Show;
use App\Request\PageRequest;
use FOS\ElasticaBundle\Finder\TransformedFinder;

class Elastic implements SearchInterface
{
    protected TransformedFinder $moviesFiner;
    protected TransformedFinder $showFiner;

    /**
     * Elastic constructor.
     *
     * @param TransformedFinder $moviesFiner
     * @param TransformedFinder $showFiner
     */
    public function __construct(TransformedFinder $moviesFiner, TransformedFinder $showFiner)
    {
        $this->moviesFiner = $moviesFiner;
        $this->showFiner = $showFiner;
    }

    public function isIndexMovie(Movie $movie)
    {
        return true;
    }

    public function isIndexShow(Show $show)
    {
        return $show->getEpisodes()->count() > 0;
    }

    public function search($qb, $class, PageRequest $pageRequest, string $locale)
    {
        $boolQuery = new \Elastica\Query\BoolQuery();

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

        $lang = new \Elastica\Query\Term();
        $lang->setTerm('existTranslations', $locale);

        $boolQuery->addMust($lang);
        if ($pageRequest->genre) {
            $genre = new \Elastica\Query\Term();
            $genre->setTerm('genres', $pageRequest->genre);
        }

        $boolQuery->setMinimumShouldMatch(1);

        $finder = $class === Show::class ? $this->showFiner : $this->moviesFiner;
        /** @var BaseMedia[] $result */
        $result = $finder->find($boolQuery, 200);
        $ids= [];
        foreach($result as $item) {
            $ids[] = $item->getId();
        }
        $qb
            ->andWhere('m.id IN (:ids)')
            ->setParameters(['ids' => $ids]);

        return $qb;
    }
}
