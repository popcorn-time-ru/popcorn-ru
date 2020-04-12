<?php

namespace App\Repository;

use App\Entity\BaseMedia;
use App\Entity\Locale\MovieLocale;
use App\Entity\Locale\ShowLocale;
use App\Entity\Movie;
use App\Repository\Locale\BaseLocaleRepository;
use App\Request\PageRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

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

    /**
     * MediaRepository constructor.
     *
     * @param BaseLocaleRepository $localeRepository
     * @param ManagerRegistry      $registry
     * @param                      $entityClass
     */
    public function __construct(BaseLocaleRepository $localeRepository, ManagerRegistry $registry, $entityClass)
    {
        parent::__construct($registry, $entityClass);
        $this->localeRepository = $localeRepository;
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
     * @param PageRequest $pageRequest
     * @param int         $offset
     * @param int         $limit
     * @return BaseMedia[]
     */
    public function getPage(PageRequest $pageRequest, int $offset, int $limit): array
    {
        $qb = $this->createQueryBuilder('m');
        if ($pageRequest->genre) {
            $qb->andWhere('m.genres LIKE :genre')->setParameter('genre', '%'.$pageRequest->genre.'%');
        }
        if ($pageRequest->keywords) {
            $class = $this instanceof ShowRepository ? ShowLocale::class : MovieLocale::class;
            $mediaIds = $this->localeRepository->findMediaIdsByTitle($pageRequest->keywords, $class);
            $qb
                ->andWhere('m.title LIKE :title OR m.imdb = :imdb OR m.id IN (:ids)')
                ->setParameters([
                    'ids' => $mediaIds,
                    'imdb' => $pageRequest->keywords,
                    'title' => '%'.$pageRequest->keywords.'%',
                ]);
        }
        $qb->andWhere('m.existTranslations LIKE :locale')
            ->setParameter('locale', '%'.$pageRequest->locale.'%');
        if ($this instanceof ShowRepository) {
            $qb->andWhere('m.episodes iS NOT EMPTY');
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
            case 'last added':
                if ($this instanceof MovieRepository) {
                    $qb->addOrderBy('m.released', $pageRequest->order);
                } else {
                    $qb->addOrderBy('m.lastUpdated', $pageRequest->order);
                }
                break;
            case 'trending':
                $qb->addOrderBy('m.rating.watching', $pageRequest->order);
                break;
            case 'year':
                $qb->addOrderBy('m.year', $pageRequest->order);
                break;
            default:
                $qb->addOrderBy('m.rating.votes', 'DESC');
                $qb->addOrderBy('m.rating.percentage', 'DESC');
                $qb->addOrderBy('m.rating.watching', 'DESC');
                break;
        }
        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
