<?php

namespace App\Repository;

use App\Entity\Album;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
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



    public function getAllAlbums($currentpage, $limit, $checkvisibility)
    {
        $qb = $this->createQueryBuilder('al');
        if ($checkvisibility) {
            $qb->where('al.visibility =:checkvisibility')
                ->setParameter('checkvisibility', $checkvisibility);
        }

        $qb->orderBy('al.id', 'ASC')
            ->distinct()
            ->setFirstResult(($currentpage - 1) * $limit)
            ->setMaxResults($limit);
        /*->getQuery()
        ->getResult();
        dd($qb);*/
        return new Paginator($qb);
    }

    public function getAllAlbumsVisibility($id)
    {
        return $this->createQueryBuilder('al')
            ->select('al')
            ->andWhere('al.artist_User_idUser = :id')
            ->andWhere('al.visibility = 1')

            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }
    public function getAllAlbumsIndefferent($id)
    {
        return $this->createQueryBuilder('al')
            ->select('al')
            ->andWhere('al.artist_User_idUser = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }


    public function searchAlbum($nom, $fullname, $label, $year, $featuring, $category, $currentpage, $limit, $checkvisibility)
    {
        $qb = $this->createQueryBuilder('al')
            ->select('al', 'a')
            ->join('al.artist_User_idUser', 'a');
        if ($checkvisibility) {
            $qb->where('al.visibility = :checkvisibility')
                ->setParameter('checkvisibility', $checkvisibility);
        }

        if ($nom) {
            $qb->andwhere('al.nom LIKE :nom')
                ->setParameter('nom', '%' . $nom . '%');
        }
        if ($year) {
            $qb->andwhere('al.year =:year')
                ->setParameter('year', $year);
        }
        if ($category) {
            $qb->andwhere('al.categ =:categ')
                ->setParameter('categ', $category);
        }
        if ($fullname) {
            $qb->andwhere('a.fullname LIKE :fullname')
                ->setParameter('fullname', '%' . $fullname . '%');
        }

        $qb->orderBy('al.id', 'ASC')
            ->distinct()
            ->setFirstResult(($currentpage - 1) * $limit)
            ->setMaxResults($limit);
        /*->getQuery()
        ->getResult();
        dd($qb);*/
        return new Paginator($qb);
    }
}
