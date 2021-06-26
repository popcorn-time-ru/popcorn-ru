<?php

namespace App\Repository;

use App\Entity\Movie;
use App\Repository\Locale\BaseLocaleRepository;
use App\Service\Search\SearchInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Movie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Movie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Movie[]    findAll()
 * @method Movie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieRepository extends MediaRepository
{
    public function __construct(SearchInterface $search, ManagerRegistry $registry)
    {
        parent::__construct($search, $registry, Movie::class);
    }
}
