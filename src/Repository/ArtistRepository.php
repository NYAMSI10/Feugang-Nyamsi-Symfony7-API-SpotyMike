<?php

namespace App\Repository;

use App\Entity\Artist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Artist>
 *
 * @method Artist|null find($id, $lockMode = null, $lockVersion = null)
 * @method Artist|null findOneBy(array $criteria, array $orderBy = null)
 * @method Artist[]    findAll()
 * @method Artist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArtistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Artist::class);
    }

    public function getAllArtist($currentpage, $limit)
    {
        $qb = $this->createQueryBuilder('a');

        $qb->andWhere('a.active  = 1')
            ->orderBy('a.id', 'ASC')
            ->distinct()
            ->setFirstResult(($currentpage - 1) * $limit)
            ->setMaxResults($limit);
        /*->getQuery()
        ->getResult();
        dd($qb);*/
        return new Paginator($qb);
    }
}
