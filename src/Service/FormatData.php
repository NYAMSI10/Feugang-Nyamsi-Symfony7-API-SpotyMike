<?php

namespace App\Service;

use App\Entity\Album;
use App\Entity\ArtistHasLabel;
use App\Entity\Label;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class FormatData
{
    public function __construct(
        protected EntityManagerInterface $em
        /*, private readonly ContainerInterface $container*/
    ) {
    }

    public function formatDataArtist($artists)
    {
        //dd($artists);
        $response = [];
        if(is_array($artists)) {
            foreach ($artists as $artist) {
            
                $artistData = [
                    'firstname' => $artist->getUserIdUser()->getFirstname(),
                    'lastname' => $artist->getUserIdUser()->getLastname(),
                    'fullname' => $artist->getFullname(),
                    'avatar' => $artist->getAvatar(),
                    'sexe' =>  $artist->getUserIdUser()->getSexe(),
                    'dateBirth' => $artist->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                    'Artist.createdAt' => $artist->getCreatedAt()->format('Y-m-d'),
                    'albums' => [],
                ];
    
                // Utiliser un tableau temporaire pour stocker les albums sans l'artiste pour éviter les références circulaires
                $tempAlums = [];
                foreach ($artist->getAlbums() as $album) {
    
                    $label_id = $this->em->getRepository(ArtistHasLabel::class)->findLabel($artist->getId(), $album->getCreatedAt());
                    $label = $this->em->getRepository(Label::class)->find($label_id['id']);
    
                    $tempAlbum = [
                        'id' => $album->getIdAlbum(),
                        'nom' => $album->getNom(),
                        'categ' => $album->getCateg(),
                        'cover' => $album->getCover(),
                        'year' => $album->getYear(),
                        'label' => $label->getNom(), // Remplacez cela par la logique appropriée pour récupérer le label
                        'createdAt' => $album->getCreatedAt()->format('Y-m-d'),
                        'songs' => [],
                    ];
    
                    foreach ($album->getSongs() as $song) {
                        $tempAlbum['songs'][] = [
                            'id' => $song->getIdSong(),
                            'title' => $song->getTitle(),
                            'cover' => $song->getCover(),
                            'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
                        ];
                    }
    
                    $tempAlbums[] = $tempAlbum;
                }
    
                $artistData['albums'] = $tempAlbums;
    
                $response[] = $artistData;
            }
        }

        return $response;
    }

    public function formatDataOneArtist($artist,$user)
    {
        $response = [];
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $user->getUserIdentifier()]);

        if (in_array('ROLE_ARTIST', $user->getRoles(), true)) {
            $albums = $this->em->getRepository(Album::class)->getAllAlbumsIndefferent($artist->getId());
        } else {
            $albums = $this->em->getRepository(Album::class)->getAllAlbumsVisibility($artist->getId());
        }

        $artistData = [
            'firstname' => $artist->getUserIdUser()->getFirstname(),
            'lastname' => $artist->getUserIdUser()->getLastname(),
            'fullname' => $artist->getFullname(),
            'avatar' => $artist->getAvatar(),
            "follower" => [],
            'sexe' =>  $artist->getUserIdUser()->getSexe(),
            'dateBirth' => $artist->getUserIdUser()->getDateBirth()->format('d-m-Y'),
            'Artist.createdAt' => $artist->getCreatedAt()->format('Y-m-d'),
            'featuring' => [],
            'albums' => $this->formatDataAlbums($albums,$user)
        ];

        $tempFollower = [];
        $tempFeaturing = [];

        foreach ($artist->getUsers() as $follower) {
            $followerData = [
                'nom' => $follower->getFirstname() . ' ' . $follower->getLastname(),
            ];

            $tempFollower[] = $followerData;
        }



        foreach ($artist->getSongs() as $collaborator) {
            $featuringData = [
                'id' => $collaborator->getIdSong(),
                'title' => $collaborator->getTitle(),
                'cover' => $collaborator->getCover(),
                'artist' =>  $this->formatData($collaborator->getArtistIdUser()),
                'createdAt' => $collaborator->getCreatedAt()->format('Y-m-d'),
            ];

            $tempFeaturing[] = $featuringData;
        }
        $artistData['featuring'] = $tempFeaturing;
        $artistData['follower'] = $tempFollower;


       // $response[] = $artistData;
        return $artistData;
    }

    public function formatData($artists)
    {
        $response = [];
        foreach ($artists as $artist) {
            $artistData = [
                'firstname' => $artist->getUserIdUser()->getFirstname(),
                'lastname' => $artist->getUserIdUser()->getLastname(),
                'fullname' => $artist->getFullname(),
                'avatar' => $artist->getAvatar(),
                'sexe' =>  $artist->getUserIdUser()->getSexe(),
                'dateBirth' => $artist->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                'Artist.createdAt' => $artist->getCreatedAt()->format('Y-m-d'),
                'albums' => [],
            ];

            // Utiliser un tableau temporaire pour stocker les albums sans l'artiste pour éviter les références circulaires
            $tempAlums = [];
            foreach ($artist->getAlbums() as $album) {

                $label_id = $this->em->getRepository(ArtistHasLabel::class)->findLabel($artist->getId(), $album->getCreatedAt());
                $label = $this->em->getRepository(Label::class)->find($label_id['id']);

                $tempAlbum = [
                    'id' => $album->getIdAlbum(),
                    'nom' => $album->getNom(),
                    'categ' => $album->getCateg(),
                    'cover' => $album->getCover(),
                    'year' => $album->getYear(),
                    'label' => $label->getNom(), // Remplacez cela par la logique appropriée pour récupérer le label
                    'createdAt' => $album->getCreatedAt()->format('Y-m-d'),
                    'songs' => [],
                ];

                foreach ($album->getSongs() as $song) {
                    $tempAlbum['songs'][] = [
                        'id' => $song->getIdSong(),
                        'title' => $song->getTitle(),
                        'cover' => $song->getCover(),
                        'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
                    ];
                }

                $tempAlbums[] = $tempAlbum;
            }

            $artistData['albums'] = $tempAlbums;

            $response[] = $artistData;
        }

        return $response;
    }

    public function formatDataAlbums($albums,$user)
    {
        $response = [];
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $user->getUserIdentifier()]);
        foreach ($albums as $album) {


            $label_id = $this->em->getRepository(ArtistHasLabel::class)->findLabel($album->getArtistUserIdUser()->getId(), $album->getCreatedAt());
            $label = $this->em->getRepository(Label::class)->find($label_id['id']);

            $responseAlbum = [
                'id' => $album->getIdAlbum(),
                'nom' => $album->getNom(),
                'categ' => $album->getCateg(),
                'cover' => $album->getCover(),
                'year' => $album->getYear(),
                'label' => $label->getNom(),
                'createdAt' => $album->getCreatedAt()->format('Y-m-d'),
                'songs' => [],
            ];

            foreach ($album->getSongs() as $song) {
                if (in_array('ROLE_ARTIST', $user->getRoles(), true)) {
                    $songData = [
                        'id' => $song->getIdSong(),
                        'title' => $song->getTitle(),
                        'cover' => $song->getCover(),
                        'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
                    ];
                    $responseAlbum['songs'][] = $songData;
                } else {
                    if ($song->isVisibility()) {
                        $songData = [
                            'id' => $song->getIdSong(),
                            'title' => $song->getTitle(),
                            'cover' => $song->getCover(),
                            'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
                        ];
                        $responseAlbum['songs'][] = $songData;
                    }
                }
            }

            $response[] = $responseAlbum;
        }


        return $response;
    }
}
