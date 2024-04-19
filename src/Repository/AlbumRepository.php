<?php

namespace App\Repository;

use App\Entity\Album;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Album>
 *
 * @method Album|null find($id, $lockMode = null, $lockVersion = null)
 * @method Album|null findOneBy(array $criteria, array $orderBy = null)
 * @method Album[]    findAll()
 * @method Album[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlbumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Album::class);
    }

    public function searchAlbums($year, $albumName, $artistName)
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.artist_User_idUser', 'artist')
            ->leftJoin('artist.User_idUser', 'artist_user');

        if ($year !== null) {
            $qb->andWhere('a.year = :year')
                ->setParameter('year', $year);
        }

        if ($albumName !== null) {
            $qb->andWhere('a.nom LIKE :albumName')
                ->setParameter('albumName', '%' . $albumName . '%');
        }

        if ($artistName !== null) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('artist_user.fullname', ':artistName'),
                $qb->expr()->like('artist_user.firstname', ':artistName'),
                $qb->expr()->like('artist_user.lastname', ':artistName')
            ))
                ->setParameter('artistName', '%' . $artistName . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
