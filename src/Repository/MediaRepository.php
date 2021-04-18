<?php

namespace App\Repository;

use App\Entity\BaseMedia;
use App\Entity\Locale\MovieLocale;
use App\Entity\Locale\ShowLocale;
use App\Entity\Movie;
use App\Repository\Locale\BaseLocaleRepository;
use App\Request\LocaleRequest;
use App\Request\PageRequest;
use App\Service\Search\Mysql;
use App\Service\Search\SearchInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Uuid;

/**
 * @method BaseMedia|null find($id, $lockMode = null, $lockVersion = null)
 * @method BaseMedia|null findOneBy(array $criteria, array $orderBy = null)
 * @method BaseMedia[]    findAll()
 * @method BaseMedia[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
abstract class MediaRepository extends ServiceEntityRepository
{
    /** @var BaseLocaleRepository */
    private $localeRepository;

    public SearchInterface $search;

    /**
     * MediaRepository constructor.
     *
     * @param BaseLocaleRepository $localeRepository
     * @param ManagerRegistry      $registry
     * @param                      $entityClass
     */
    public function __construct(SearchInterface $search, ManagerRegistry $registry, $entityClass)
    {
        parent::__construct($registry, $entityClass);
        $this->search = $search;
    }

    public function flush(): void
    {
        $this->_em->flush();
    }

    public function findByImdb(string $imdbId): ?BaseMedia
    {
        return $this->findOneBy(['imdb' => $imdbId]);
    }

    /**
     * @param \DateTime $before
     * @param int       $limit
     * @return BaseMedia[]
     */
    public function getOld(\DateTime $before, int $limit): array
    {
        $qb = $this->createQueryBuilder('m');
        $qb->where('m.syncAt < :before')->setParameter('before', $before);
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param PageRequest   $pageRequest
     * @param LocaleRequest $localeParams
     * @param int           $offset
     * @param int           $limit
     * @return BaseMedia[]
     */
    public function getPage(PageRequest $pageRequest, LocaleRequest $localeParams, int $offset, int $limit): array
    {
        $qb = $this->createQueryBuilder('m');
        if ($pageRequest->keywords) {
            $qb = $this->search->search($qb, $this->_class, $pageRequest, $localeParams->contentLocale);
        }
        if ($pageRequest->genre) {
            $qb->andWhere('m.genres LIKE :genre')->setParameter('genre', '%'.$pageRequest->genre.'%');
        }
        $qb->andWhere('m.existTranslations LIKE :locale')
            ->setParameter('locale', '%'.$localeParams->contentLocale.'%');
        if ($this instanceof ShowRepository) {
            $qb->andWhere('m.episodes IS NOT EMPTY');
        }
        switch ($pageRequest->sort) {
            case 'title':
            case 'name':
                $qb->addOrderBy('m.title', $pageRequest->order);
                break;
            case 'rating':
                $qb->addOrderBy('m.rating.votes', $pageRequest->order);
                $qb->addOrderBy('m.rating.percentage', $pageRequest->order);
                break;
            case 'released':
            case 'updated':
                if ($this instanceof MovieRepository) {
                    $qb->addOrderBy('m.released', $pageRequest->order);
                } else {
                    $qb->addOrderBy('m.lastUpdated', $pageRequest->order);
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
                $qb->addOrderBy('m.rating.votes', 'DESC');
                $qb->addOrderBy('m.rating.percentage', 'DESC');
                $qb->addOrderBy('m.rating.watching', 'DESC');
                $qb->addOrderBy('m.rating.watchers', 'DESC');
                break;
        }
        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getRandom(): BaseMedia
    {
        $uuid = Uuid::uuid4();
        $media = $this->createQueryBuilder('m')
            ->where('m.id > :uuid')->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
        if (!$media) {
            $media = $this->createQueryBuilder('m')
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
        }

        return $media;
    }

    public function getGenreStatistics(): array
    {
        return $this->createQueryBuilder('m')
            ->select('m.existTranslations, m.genres, COUNT(m) as c')
            ->groupBy('m.existTranslations, m.genres')
            ->getQuery()->getResult();
    }
}
