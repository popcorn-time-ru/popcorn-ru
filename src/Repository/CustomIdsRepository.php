<?php

namespace App\Repository;

use App\Entity\CustomIds;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CustomIds|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomIds|null findOneBy(array $criteria, array $orderBy = null)
 * @method CustomIds[]    findAll()
 * @method CustomIds[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomIdsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomIds::class);
    }

    public function findOrCreateByImdb(string $imdb)
    {
        $exist = $this->find($imdb);
        if (!$exist) {
            $exist = new CustomIds();
            $exist->setImdb($imdb);
            $this->persist($exist);
        }
        return $exist;
    }

    public function findOneByCustomId(string $name, $id): ?CustomIds
    {
        return $this->findOneBy([$name => $id]);
    }
}
