<?php

namespace App\Repository\Locale;

use App\Entity\BaseMedia;
use App\Entity\Locale\BaseLocale;
use App\Entity\Locale\MovieLocale;
use App\Entity\Locale\ShowLocale;
use App\Entity\Movie;
use App\Entity\Show;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    public function flush(): void
    {
        $this->_em->flush();
    }

    public function findOrCreateByMovieAndLocale(Movie $media, string $locale): MovieLocale
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('m')
            ->from(MovieLocale::class, 'm');
        $qb->where('m.locale = :locale')->setParameter('locale', $locale);
        $qb->andWhere('m.movie = :id')->setParameter('id', $media->getId());
        $localeObj = $qb->getQuery()->getOneOrNullResult();

        if (!$localeObj) {
            $localeObj = new MovieLocale();
            $localeObj->setMovie($media);
            $localeObj->setLocale($locale);
            $this->_em->persist($localeObj);
        }

        return $localeObj;
    }

    public function findOrCreateByShowAndLocale(Show $media, string $locale): ShowLocale
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('m')
            ->from(ShowLocale::class, 'm');
        $qb->where('m.locale = :locale')->setParameter('locale', $locale);
        $qb->andWhere('m.show = :id')->setParameter('id', $media->getId());
        $localeObj = $qb->getQuery()->getOneOrNullResult();

        if (!$localeObj) {
            $localeObj = new ShowLocale();
            $localeObj->setShow($media);
            $localeObj->setLocale($locale);
            $this->_em->persist($localeObj);
        }

        return $localeObj;
    }

    /**
     * @param string $title
     * @param string $class
     * @return string[]
     */
    public function findMediaIdsByTitle(string $title, string $class): array
    {
        $qb = $this->_em->createQueryBuilder('l');
        $qb
            ->select('l')
            ->from($class, 'l')
            ->where('l.title LIKE :title')
            ->setParameter('title', '%'.str_replace('%', '%%', $title).'%')
            ->setMaxResults(100) // just first 100 - for possible mem limit
        ;

        return array_map(static function(BaseLocale $l) {
            return $l->getMedia()->getId();
        }, $qb->getQuery()->getResult());
    }
}
