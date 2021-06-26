<?php

namespace App\Repository;

use App\Entity\Anime;
use App\Service\Search\SearchInterface;
use Doctrine\Persistence\ManagerRegistry;

class AnimeRepository extends MediaRepository
{
    public function __construct(SearchInterface $search, ManagerRegistry $registry)
    {
        parent::__construct($search, $registry, Anime::class);
    }
}
