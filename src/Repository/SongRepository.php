<?php

namespace App\Repository;

use App\Entity\Song;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Song>
 *
 * @method Song|null find($id, $lockMode = null, $lockVersion = null)
 * @method Song|null findOneBy(array $criteria, array $orderBy = null)
 * @method Song[]    findAll()
 * @method Song[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SongRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Song::class);
    }

    //    /**
    //     * @return Song[] Returns an array of Song objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Song
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findByVisibilityAndArtist($artist, $check_visibility = false)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->join('s.Artist_idUser', 'a')
            ->where("a = :artist")
            ->setParameter('artist', $artist);

        if ($check_visibility) {
            $qb->andWhere('s.visibility = true');
        }
        return $qb->getQuery()->getResult();
    }
    public function findByVisibilityAndAlbums($id)
    {
        return $this->createQueryBuilder('s')
            ->select('s')
            ->andWhere('s.id = :id')
            ->andWhere('s.visibility = 1')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }
}
