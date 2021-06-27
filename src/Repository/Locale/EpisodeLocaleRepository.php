<?php

namespace App\Repository\Locale;

use App\Entity\Episode\Episode;
use App\Entity\Locale\EpisodeLocale;
use App\Entity\Show;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EpisodeLocale|null find($id, $lockMode = null, $lockVersion = null)
 * @method EpisodeLocale|null findOneBy(array $criteria, array $orderBy = null)
 * @method EpisodeLocale[]    findAll()
 * @method EpisodeLocale[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EpisodeLocaleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EpisodeLocale::class);
    }

    public function flush(): void
    {
        $this->_em->flush();
    }

    public function persist(EpisodeLocale $item): void
    {
        $this->_em->persist($item);
    }

    public function findByShowAndLocale(Show $show, string $locale): array
    {
        $qb = $this->createQueryBuilder('el');
        $qb->join('el.episode', 'e');
        $qb->andWhere('e.show = :show')->setParameter('show', $show);
        $qb->andWhere('el.locale = :locale')->setParameter('locale', $locale);
        /** @var EpisodeLocale[] $result */
        return $qb->getQuery()->enableResultCache()->getResult();
    }


    public function findByEpisodeAndLocale(Episode $episode, string $locale): ?EpisodeLocale
    {
        $qb = $this->createQueryBuilder('el');
        $qb->where('el.locale = :locale')->setParameter('locale', $locale);
        $qb->andWhere('el.episode = :id')->setParameter('id', $episode->getId());

        return $qb->getQuery()->getOneOrNullResult();
    }
}
