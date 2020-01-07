<?php

namespace App\Repository\Locale;

use App\Entity\Locale\EpisodeLocale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

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
}
