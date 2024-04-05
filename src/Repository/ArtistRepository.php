<?php

namespace App\Repository;

use App\Entity\Artist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    //    /**
    //     * @return Artist[] Returns an array of Artist objects
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

    //    public function findOneBySomeField($value): ?Artist
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findAllWithPagination($checkvisibility,$page, $limit) 
    {

        $qb = $this->createQueryBuilder('a')
        ->select('COUNT(DISTINCT a.id) as totalArtists')
        ->leftJoin('a.User_idUser', 'u')
        ->leftJoin('a.songs', 's')
        ->leftJoin('a.albums', 'al')
        ->leftJoin('App\Entity\ArtistHasLabel', 'ahl', 'WITH', 'a.id = ahl.idArtist')
        ->leftJoin('App\Entity\Label', 'l', 'WITH', 'l.id = ahl.idLabel');
        

        if($checkvisibility) {
            $qb->where('s.visibility = :songVisibility')
            ->andWhere('al.visibility = :albumVisibility')
            ->andWhere('a.active = :active')
            ->setParameter('songVisibility', true)
            ->setParameter('albumVisibility', true)
            ->setParameter('active', true);
        }
        $qb->andWhere('al.createdAt BETWEEN ahl.entrydate AND ahl.issuedate');

        $totalArtists = $qb->getQuery()->getSingleScalarResult();

        $qb = $this->createQueryBuilder('a')
            ->select('u.firstname','u.lastname','u.sexe','u.dateBirth','a.createdAt AS artist_createdAt', 's.idSong','s.title','s.cover AS song_cover','s.createdAt AS song_createdAt', 'al.idAlbum','al.nom','al.categ','al.cover','al.createdAt AS album_created','l.nom AS label')
            ->leftJoin('a.User_idUser', 'u')
            ->leftJoin('a.songs', 's')
            ->leftJoin('a.albums', 'al')
            ->leftJoin('App\Entity\ArtistHasLabel', 'ahl', 'WITH', 'a.id = ahl.idArtist')
            ->leftJoin('App\Entity\Label', 'l', 'WITH', 'l.id = ahl.idLabel')
            //->where('al.createdAt BETWEEN ahl.entrydate AND ahl.issuedate')
            ->where('s.visibility = :songVisibility')
            ->andWhere('al.visibility = :albumVisibility')
            ->andWhere('a.active = :active')
            ->andWhere('al.createdAt BETWEEN ahl.entrydate AND ahl.issuedate')
            ->setParameter('songVisibility', true)
            ->setParameter('albumVisibility', true)
            ->setParameter('active', true)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
        
        $results = $qb->getQuery()->getResult();

        
        $artists = [];
        foreach ($results as $result) {
            $artistKey = $result['firstname'] . ' ' . $result['lastname'];

            if (!isset($artists[$artistKey])) {
                $artists[$artistKey] = [
                    'firstname' => $result['firstname'],
                    'lastname' => $result['lastname'],
                    'sexe' => $result['sexe'],
                    'dateBirth' => $result['dateBirth']->format('d-m-Y'),
                    'Artist.createdAt' =>$result['artist_createdAt']->format('d-m-Y'),
                    'albums' => [],
                    'songs' => [],
                ];
            }

            if ($result['idAlbum'] !== null) {
                $album = [
                    'id' => $result['idAlbum'],
                    'nom' => $result['nom'],
                    'categ' => $result['categ'],
                    'cover' => $result['cover'],
                    'label' => $result['label'],
                    'createdAt' => $result['album_created']->format('d-m-Y')
                ];
                if(!in_array($album,$artists[$artistKey]['albums']))
                    $artists[$artistKey]['albums'][] = $album;
            }

            if ($result['idSong'] !== null) {
                $song = [
                    'id' => $result['idSong'],
                    'title' => $result['title'],
                    'cover' => $result['title'],
                    'createdAt' => $result['song_createdAt']->format('d-m-Y')
                ];

                if(!in_array($song,$artists[$artistKey]['songs']))
                    $artists[$artistKey]['songs'][] = $song;
            }
        }
       
        $totalPages = ceil($totalArtists / $limit);
        $currentPage = $page;

        return array(
            'artists' => array_values($artists),
            'pagination' => array(
                'currentPage' => $currentPage,
                'totalPages' => (int)$totalPages,
                'totalArtists' => $totalArtists
            )
        );
    }
}
