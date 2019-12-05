<?php

namespace App\Repository;

use App\Entity\BaseMedia;
use App\Entity\Movie;
use App\Entity\Show;
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
    public function flush(): void
    {
        $this->_em->flush();
    }

    public function findByImdb(string $imdbId): ?BaseMedia
    {
        return $this->findOneBy(['imdb' => $imdbId]);
    }

    public function findOrCreateShowByImdb(string $imdbId): Show
    {
        $movie = $this->findByImdb($imdbId);
        if (!$movie) {
            $movie = new Show();
            $movie->setImdb($imdbId);
            $this->_em->persist($movie);
        }

        return $movie;
    }

    /**
     * @param string $genre
     * @param string $keywords
     * @param string $sort
     * @param string $order
     * @param int    $offset
     * @param int    $limit
     * @return Movie[]
     */
    public function getPage(string $genre, string $keywords, string $sort, string $order, int $offset, int $limit): array
    {
        $qb = $this->createQueryBuilder('m');
        if ($genre && $genre !== 'all') {
            $qb->andWhere('m.genres LIKE :genre')->setParameter('genre', '%'.$genre.'%');
        }
        if ($keywords) {
            $qb->andWhere('m.synopsis LIKE :keywords OR m.title LIKE :keywords')
                ->setParameter('keywords', '%'.$keywords.'%');
        }
        switch ($sort) {
            case 'name':
                $qb->addOrderBy('m.title', $order);
                break;
            case 'released':
            case 'updated':
                $qb->addOrderBy('m.released', $order);
                break;
            case 'trending':
                $qb->addOrderBy('m.rating.watching', $order);
                break;
            case 'year':
                $qb->addOrderBy('m.year', $order);
                break;
            default:
                $qb->addOrderBy('m.rating.watching', 'DESC');
                break;
        }
        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    // /**
    //  * @return Movie[] Returns an array of Movie objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Movie
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
