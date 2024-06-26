<?php

namespace App\Repository;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\ArtistHasLabel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArtistHasLabel>
 *
 * @method ArtistHasLabel|null find($id, $lockMode = null, $lockVersion = null)
 * @method ArtistHasLabel|null findOneBy(array $criteria, array $orderBy = null)
 * @method ArtistHasLabel[]    findAll()
 * @method ArtistHasLabel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArtistHasLabelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArtistHasLabel::class);
    }

    //    /**
    //     * @return ArtistHasLabel[] Returns an array of ArtistHasLabel objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ArtistHasLabel
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findLabel($artist_id,$album_created){
        $qb = $this->createQueryBuilder('ahl')
        ->select('label.id')
        ->join('ahl.idLabel','label')
        ->join('ahl.idArtist','artist')
        ->where('artist.id = :id')
        ->andWhere(':album_created BETWEEN ahl.entrydate AND COALESCE(ahl.issuedate, CURRENT_TIMESTAMP())')
        ->setParameter('id', $artist_id)
        ->setParameter('album_created', $album_created);

       
        return $qb->getQuery()->getOneOrNullResult();

    }
}
