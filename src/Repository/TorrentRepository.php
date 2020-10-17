<?php

namespace App\Repository;

use App\Entity\BaseMedia;
use App\Entity\Episode;
use App\Entity\Torrent\BaseTorrent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * @method BaseTorrent|null find($id, $lockMode = null, $lockVersion = null)
 * @method BaseTorrent|null findOneBy(array $criteria, array $orderBy = null)
 * @method BaseTorrent[]    findAll()
 * @method BaseTorrent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TorrentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BaseTorrent::class);
    }

    public function flush(): void
    {
        $this->_em->flush();
    }

    public function delete(BaseTorrent $torrent): void
    {
        $this->_em->remove($torrent);
        $this->_em->flush();
    }

    /**
     * @param \DateTime $before
     * @param int       $limit
     * @return BaseTorrent[]
     */
    public function getOld(\DateTime $before, int $limit): array
    {
        $qb = $this->createQueryBuilder('t');
        $qb->where('t.lastCheckAt < :before')->setParameter('before', $before);
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \DateTime $before
     * @param int       $limit
     * @return BaseTorrent[]
     */
    public function getNotSyncAndInactive(\DateTime $before, int $limit): array
    {
        $qb = $this->createQueryBuilder('t');
        $qb->andWhere('t.syncAt < :before')->setParameter('before', $before);
        $qb->andWhere('t.active = 0');
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function findByProviderAndExternalId(string $provider, string $externalId): ?BaseTorrent
    {
        return $this->findOneBy([
            'provider' => $provider,
            'providerExternalId' => $externalId
        ]);
    }

    public function getStatByProvider(): array
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t.provider', 'count(t.id) as c')
            ->groupBy('t.provider')
            ;
        $result = [];
        foreach ($qb->getQuery()->getArrayResult() as $item) {
            $result[$item['provider']] = $item['c'];
        }

        return $result;
    }

    /**
     * @param BaseMedia $media
     * @param string    $language
     * @param bool      $onlyActive
     * @return BaseTorrent[]
     */
    public function getMediaTorrents(BaseMedia $media, string $language, bool $onlyActive = true): array
    {
        $qb = $this->createQueryBuilder('t');
        $qb->where('t.mediaId = :media')->setParameter('media', $media->getId());
        $qb->andWhere('t.language = :lang')->setParameter('lang', $language);
        if ($onlyActive) {
            $qb->andWhere('t.active = true');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Episode $episode
     * @param string  $language
     * @param bool    $onlyActive
     * @return BaseTorrent[]
     */
    public function getEpisodeTorrents(Episode $episode, string $language, bool $onlyActive = true): array
    {
        $qb = $this->createQueryBuilder('t');
        $qb->where('t.mediaId = :media')->setParameter('media', $episode->getId());
        $qb->andWhere('t.language = :lang')->setParameter('lang', $language);
        if ($onlyActive) {
            $qb->andWhere('t.active = true');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int $limit
     * @return string[]
     */
    public function getUnlinkedShowTorrents(int $limit): array
    {
        $q = $this->_em->createNativeQuery('
            SELECT DISTINCT t.id
            FROM torrent t
            JOIN file f ON t.id = f.torrent_id
            LEFT JOIN episodes_files ef ON f.id = ef.file_id
            WHERE t.show_id IS NOT NULL AND ef.episode_id IS NULL
              AND (name LIKE \'%.avi\'
                    OR name LIKE \'%.mp4\'
                    OR name LIKE \'%.mkv\'
                )
            LIMIT '.$limit, (new ResultSetMapping())->addScalarResult('id', 'id'));
        return array_map(function ($a) {return $a['id'];}, $q->getResult());
    }
}
