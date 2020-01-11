<?php

namespace App\Repository;

use App\Entity\Movie;
use App\Repository\Locale\BaseLocaleRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Movie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Movie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Movie[]    findAll()
 * @method Movie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieRepository extends MediaRepository
{
    public function __construct(BaseLocaleRepository $localeRepository, ManagerRegistry $registry)
    {
        parent::__construct($localeRepository, $registry, Movie::class);
    }

    public function findOrCreateMovieByImdb(string $imdbId): Movie
    {
        $movie = $this->findByImdb($imdbId);
        if (!$movie) {
            $movie = new Movie();
            $movie->setImdb($imdbId);
            $this->_em->persist($movie);
        }

        return $movie;
    }

}
