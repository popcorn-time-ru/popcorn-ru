<?php

namespace App\Repository;

use App\Entity\MediaStat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MediaStat|null find($id, $lockMode = null, $lockVersion = null)
 * @method MediaStat|null findOneBy(array $criteria, array $orderBy = null)
 * @method MediaStat[]    findAll()
 * @method MediaStat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaStatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaStat::class);
    }

    public function flush(): void
    {
        $this->_em->flush();
    }

    /**
     * @param string $type
     * @param string $language
     * @return MediaStat[]
     */
    public function getByTypeAndLang(string $type, string $language): array
    {
        return $this->findBy([
            'type' => $type,
            'language' => $language,
        ]);
    }

    public function getOrCreate(string $type, string $genre, string $language): MediaStat
    {
        $item = $this->findOneBy([
            'type' => $type,
            'genre' => $genre,
            'language' => $language,
        ]);

        if (!$item) {
            $item = new MediaStat();
            $item->setType($type);
            $item->setGenre($genre);
            $item->setLanguage($language);
            $this->_em->persist($item);
        }

        return $item;
    }
}
