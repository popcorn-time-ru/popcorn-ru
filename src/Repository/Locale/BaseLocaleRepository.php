<?php

namespace App\Repository\Locale;

use App\Entity\Anime;
use App\Entity\BaseMedia;
use App\Entity\Locale\AnimeLocale;
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

    public function findByMediaAndLocale(BaseMedia $media, string $locale): ?BaseLocale
    {
        $info = $media instanceof Movie
            ? [MovieLocale::class, 'movie']
            : [ShowLocale::class, 'show'];

        $qb = $this->_em->createQueryBuilder()
            ->select('m')
            ->from($info[0], 'm');
        $qb->andWhere('m.locale = :locale')->setParameter('locale', $locale);
        $qb->andWhere('m.'.$info[1].' = :id')->setParameter('id', $media->getId());
        return $qb->getQuery()->enableResultCache()->getOneOrNullResult();
    }

    public function findOrCreateByMediaAndLocale(BaseMedia $media, string $locale): BaseLocale
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('m')
            ->from(BaseLocale::class, 'm');
        $qb->where('m.locale = :locale')->setParameter('locale', $locale);
        $qb->andWhere('m.media = :id')->setParameter('id', $media->getId());
        $localeObj = $qb->getQuery()->getOneOrNullResult();

        if (!$localeObj) {
            $localeObj = $this->getLocaleByMedia(get_class($media));
            $localeObj->setMedia($media);
            $localeObj->setLocale($locale);
            $this->_em->persist($localeObj);
        }

        return $localeObj;
    }

    protected function getLocaleByMedia($mediaClass)
    {
        switch ($mediaClass) {
            case Movie::class:
                return new MovieLocale();
            case Show::class:
                return new ShowLocale();
            case Anime::class:
                return new AnimeLocale();
        }
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
