<?php

namespace App\Service\Search;

use App\Entity\Locale\MovieLocale;
use App\Entity\Locale\ShowLocale;
use App\Entity\Show;
use App\Repository\Locale\BaseLocaleRepository;
use App\Repository\MovieRepository;
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

    public function search(QueryBuilder $qb, ClassMetadata $class, PageRequest $pageRequest, string $locale, int $offset, int $limit): array
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

        switch ($pageRequest->sort) {
            case 'title':
            case 'name':
                $qb->addOrderBy('m.title', $pageRequest->order);
                break;
            case 'popularity':
                $qb->addOrderBy('m.rating.popularity', $pageRequest->order);
                $qb->addOrderBy('m.rating.watchers', $pageRequest->order);
                break;
            case 'rating':
                $qb->addOrderBy('m.rating.weightRating', $pageRequest->order);
                break;
            case 'released':
            case 'updated':
                if ($class->getName() === Show::class) {
                    $qb->addOrderBy('m.lastUpdated', $pageRequest->order);
                } else {
                    $qb->addOrderBy('m.released', $pageRequest->order);
                }
                break;
            case 'last added':
                $qb->addOrderBy('m.createdAt', $pageRequest->order);
                break;
            case 'trending':
                $qb->addOrderBy('m.rating.watching', $pageRequest->order);
                $qb->addOrderBy('m.rating.watchers', $pageRequest->order);
                break;
            case 'year':
                $qb->addOrderBy('m.year', $pageRequest->order);
                break;
            default:
                $qb->addOrderBy('m.rating.popularity', 'DESC');
                $qb->addOrderBy('m.rating.watchers', 'DESC');
                break;
        }

        $qb->setFirstResult($offset)->setMaxResults($limit);
        return $qb->getQuery()->getResult();
    }
}
