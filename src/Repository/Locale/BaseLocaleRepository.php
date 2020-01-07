<?php

namespace App\Repository\Locale;

use App\Entity\Locale\BaseLocale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method BaseLocale|null find($id, $lockMode = null, $lockVersion = null)
 * @method BaseLocale|null findOneBy(array $criteria, array $orderBy = null)
 * @method BaseLocale[]    findAll()
 * @method BaseLocale[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BaseLocaleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BaseLocale::class);
    }
}
