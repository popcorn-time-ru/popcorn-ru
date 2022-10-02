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
    public SearchInterface $search;

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
     * @return BaseMedia[]
     */
    public function getPage(PageRequest $pageRequest, LocaleRequest $localeParams): array
    {
        return $this->search->search(
            $this->createQueryBuilder('m'),
            $this->_class,
            $pageRequest,
            $localeParams,
            $pageRequest->offset,
            $pageRequest->limit
        );
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

    public function getGenreLangStatistics(): array
    {
        return $this->createQueryBuilder('m')
            ->select('m.existTranslations, m.genres, COUNT(m) as c')
            ->groupBy('m.existTranslations, m.genres')
            ->getQuery()->getResult();
    }

    public function getGenreStatistics(): array
    {
        return $this->createQueryBuilder('m')
            ->select('m.genres, COUNT(m) as c')
            ->groupBy('m.genres')
            ->getQuery()->getResult();
    }

    public function findWatching(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.rating.watching > 0')
            ->getQuery()->getResult();
    }
}
