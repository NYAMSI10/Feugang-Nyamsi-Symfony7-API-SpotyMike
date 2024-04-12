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


    public function findAllWithPagination($checkvisibility, $page, $limit)
    {

        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(DISTINCT a.id) as totalArtists');
        if ($checkvisibility) {
            $qb->andWhere('a.active = :active')
                ->setParameter('active', true);
        }

        $totalArtists = $qb->getQuery()->getSingleScalarResult();
        $sql = "SELECT
                    u.firstname AS firstname,
                    u.lastname AS lastname,
                    u.sexe AS sexe,
                    DATE_FORMAT(u.date_birth, '%d/%m/%Y') AS dateBirth,
                    DATE_FORMAT(a.created_at, '%Y-%m-%d') AS artist_createdAt,
                    a.fullname AS artist_name,
                    al.id AS idAlbum,
                    al.nom AS nom,
                    al.categ AS categ,
                    al.cover AS cover,
                    DATE_FORMAT(al.created_at, '%Y-%m-%d') AS created_at_album,
                    l.nom AS label,
                    al.visibility AS album_visibility,
                    GROUP_CONCAT(s.id_song) AS song_ids,
                    GROUP_CONCAT(s.title) AS song_titles,
                    GROUP_CONCAT(s.cover) AS song_covers,
                    GROUP_CONCAT(
                        DATE_FORMAT(s.created_at, '%Y-%m-%d')
                    ) AS song_created_ats
                FROM 
                    artist a
                LEFT JOIN 
                    user u ON a.user_id_user_id = u.id
                LEFT JOIN 
                    album al ON a.id = al.artist_user_id_user_id";

        if ($checkvisibility) {
            $sql .= " AND al.visibility =  $checkvisibility";
        }
        $sql .= " LEFT JOIN song s ON al.id = s.album_id";

        if ($checkvisibility) {
            $sql .= " AND al.visibility =  $checkvisibility";
        }
        $sql .= "
                LEFT JOIN 
                    artist_has_label ahl ON a.id = ahl.id_artist_id
                LEFT JOIN 
                    label l ON l.id = ahl.id_label_id
                    AND al.created_at BETWEEN ahl.entrydate AND ahl.issuedate
                WHERE ";
        if ($checkvisibility) {
            $sql .= "a.active = $checkvisibility AND";
        }
        $sql .= "
                     (al.id IS NULL OR l.id IS NOT NULL)
                GROUP BY
                    al.id
                ORDER BY 
                    a.id, al.id";
        $results = $this->getEntityManager()->getConnection()->executeQuery($sql, [])->fetchAll();

        $artists = [];
        foreach ($results as $result) {
            $artistKey = $result['firstname'] . ' ' . $result['lastname'];

            if (!isset($artists[$artistKey])) {
                $artists[$artistKey] = [
                    'firstname' => $result['firstname'],
                    'lastname' => $result['lastname'],
                    'sexe' => $result['sexe'],
                    'dateBirth' => $result['dateBirth']/*->format('d-m-Y')*/,
                    'Artist.createdAt' => $result['artist_createdAt']/*->format('d-m-Y')*/,
                    'albums' => [],

                ];
            }

            if ($result['idAlbum'] !== null) {

                if (!isset($artists[$artistKey]['albums'][$result['idAlbum']])) {
                    $artists[$artistKey]['albums'][$result['idAlbum']] = [
                        'id' => $result['idAlbum'],
                        'nom' => $result['nom'],
                        'categ' => $result['categ'],
                        'cover' => $result['cover'],
                        'label' => $result['label'],
                        'createdAt' => $result['created_at_album'],
                        'songs' => []
                    ];
                }

                $songIds = explode(',', $result['song_ids']);
                $songTitles = explode(',', $result['song_titles']);
                $songCovers = explode(',', $result['song_covers']);
                $songcreates = explode(',', $result['song_created_ats']);

                $numSongs = count($songIds);
                for ($i = 0; $i < $numSongs; $i++) {
                    $artists[$artistKey]['albums'][$result['idAlbum']]['songs'][] = [
                        'id' => $songIds[$i],
                        'title' => $songTitles[$i],
                        'cover' => $songCovers[$i],
                        'createdAt' => $songcreates[$i]
                    ];
                }
            }
        }

        $limit = 1;
        $offset = ($page - 1) * $limit;
        $returnArtist = array_slice($artists, $offset, $limit);

        $totalPages = ceil($totalArtists / $limit);
        $currentPage = $page;

        return array(
            'artists' => array_values($returnArtist),
            'pagination' => array(
                'currentPage' => $currentPage,
                'totalPages' => (int)$totalPages,
                'totalArtists' => $totalArtists
            )
        );
    }

    public function findByArtistAndAlbumAndSong($artist_fullname, $checkvisibility)
    {
        $sql = "SELECT
                    u.firstname AS firstname,
                    u.lastname AS lastname,
                    u.sexe AS sexe,
                    DATE_FORMAT(u.date_birth, '%d/%m/%Y') AS dateBirth,
                    DATE_FORMAT(a.created_at, '%Y-%m-%d') AS artist_createdAt,
                    a.fullname AS artist_name,
                    al.id AS idAlbum,
                    al.nom AS nom,
                    al.categ AS categ,
                    al.cover AS cover,
                    DATE_FORMAT(al.created_at, '%Y-%m-%d') AS created_at_album,
                    l.nom AS label,
                    al.visibility AS album_visibility,
                    GROUP_CONCAT(s.id_song) AS song_ids,
                    GROUP_CONCAT(s.title) AS song_titles,
                    GROUP_CONCAT(s.cover) AS song_covers,
                    GROUP_CONCAT(
                        DATE_FORMAT(s.created_at, '%Y-%m-%d')
                    ) AS song_created_ats
                FROM 
                    artist a
                LEFT JOIN 
                    user u ON a.user_id_user_id = u.id
                LEFT JOIN 
                    album al ON a.id = al.artist_user_id_user_id";

        if ($checkvisibility) {
            $sql .= "AND al.visibility =  $checkvisibility";
        }
        $sql .= " LEFT JOIN song s ON al.id = s.album_id";

        if ($checkvisibility) {
            $sql .= "AND al.visibility =  $checkvisibility";
        }
        $sql .= "
                LEFT JOIN 
                    artist_has_label ahl ON a.id = ahl.id_artist_id
                LEFT JOIN 
                    label l ON l.id = ahl.id_label_id
                    AND al.created_at BETWEEN ahl.entrydate AND ahl.issuedate
                WHERE ";
        if ($checkvisibility) {
            $sql .= "a.active = $checkvisibility AND";
        }
        $sql .= "
                    a.fullname = '" . $artist_fullname . "'
                    AND (al.id IS NULL OR l.id IS NOT NULL)
                GROUP BY
                    al.id
                ORDER BY 
                    a.id, al.id";


        $results = $this->getEntityManager()->getConnection()->executeQuery($sql, [])->fetchAll();
        $artist = [];

        foreach ($results as $result) {
            $artistKey = $result['firstname'] . ' ' . $result['lastname'];

            if (!isset($artist[$artistKey])) {
                $artist[$artistKey] = [
                    'firstname' => $result['firstname'],
                    'lastname' => $result['lastname'],
                    'sexe' => $result['sexe'],
                    'dateBirth' => $result['dateBirth']/*->format('d-m-Y')*/,
                    'Artist.createdAt' => $result['artist_createdAt']/*->format('d-m-Y')*/,
                    'albums' => [],

                ];
            }

            if ($result['idAlbum'] !== null) {

                if (!isset($artist[$artistKey]['albums'][$result['idAlbum']])) {
                    $artist[$artistKey]['albums'][$result['idAlbum']] = [
                        'id' => $result['idAlbum'],
                        'nom' => $result['nom'],
                        'categ' => $result['categ'],
                        'cover' => $result['cover'],
                        'label' => $result['label'],
                        'createdAt' => $result['created_at_album'],
                        'songs' => []
                    ];
                }

                $songIds = explode(',', $result['song_ids']);
                $songTitles = explode(',', $result['song_titles']);
                $songCovers = explode(',', $result['song_covers']);
                $songcreates = explode(',', $result['song_created_ats']);

                $numSongs = count($songIds);
                for ($i = 0; $i < $numSongs; $i++) {
                    $artist[$artistKey]['albums'][$result['idAlbum']]['songs'][] = [
                        'id' => $songIds[$i],
                        'title' => $songTitles[$i],
                        'cover' => $songCovers[$i],
                        'createdAt' => $songcreates[$i]
                    ];
                }
            }
        }

        if ($artist)
            return array_values($artist)[0];
        else
            return null;
    }
}
