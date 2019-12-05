<?php

namespace App\Repository;

use App\Entity\BaseTorrent;
use App\Entity\MovieTorrent;
use App\Entity\ShowTorrent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method BaseTorrent|null find($id, $lockMode = null, $lockVersion = null)
 * @method BaseTorrent|null findOneBy(array $criteria, array $orderBy = null)
 * @method BaseTorrent[]    findAll()
 * @method BaseTorrent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TorrentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BaseTorrent::class);
    }

    public function flush(): void
    {
        $this->_em->flush();
    }

    public function findOrCreateByProviderAndExternalId(string $provider, string $externalId): BaseTorrent
    {
        $torrent = $this->findOneBy([
            'provider' => $provider,
            'providerExternalId' => $externalId
        ]);
        if (!$torrent) {
            $torrent = new MovieTorrent();
            $torrent
                ->setProvider($provider)
                ->setProviderExternalId($externalId)
            ;
            $this->_em->persist($torrent);
        }

        return $torrent;
    }

    // /**
    //  * @return Torrent[] Returns an array of Torrent objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Torrent
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
