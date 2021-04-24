<?php

namespace App\Service\Search;

use App\Entity\Locale\MovieLocale;
use App\Entity\Locale\ShowLocale;
use App\Entity\Show;
use App\Repository\Locale\BaseLocaleRepository;
use App\Request\PageRequest;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

class Mysql implements SearchInterface
{
    public BaseLocaleRepository $localeRepository;

    public function __construct(BaseLocaleRepository $localeRepository)
    {
        $this->localeRepository = $localeRepository;
    }

    public function search(QueryBuilder $qb, ClassMetadata $class, PageRequest $pageRequest, string $locale, int $offset, int $limit): QueryBuilder
    {
        if ($pageRequest->keywords) {
            $localeClass = $class->getName() === Show::class ? ShowLocale::class : MovieLocale::class;
            $mediaIds = $this->localeRepository->findMediaIdsByTitle($pageRequest->keywords, $localeClass);
            $qb
                ->andWhere('m.title LIKE :title OR m.imdb = :imdb OR m.id IN (:ids)')
                ->setParameters([
                    'ids' => $mediaIds,
                    'imdb' => $pageRequest->keywords,
                    'title' => '%'.str_replace('%', '%%', $pageRequest->keywords).'%',
                ]);
        }
        if ($pageRequest->genre) {
            $qb->andWhere('m.genres LIKE :genre')->setParameter('genre', '%'.$pageRequest->genre.'%');
        }
        $qb->andWhere('m.existTranslations LIKE :locale')
            ->setParameter('locale', '%'.$locale.'%');
        if ($class->getName() === Show::class) {
            $qb->andWhere('m.episodes IS NOT EMPTY');
        }
        $qb->setFirstResult($offset)->setMaxResults($limit);
        return $qb;
    }
}
