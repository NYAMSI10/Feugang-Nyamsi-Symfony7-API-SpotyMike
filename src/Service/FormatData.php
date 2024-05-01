<?php

namespace App\Service;

use App\Entity\Album;
use App\Entity\ArtistHasLabel;
use App\Entity\Label;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FormatData
{
    public function __construct(
        protected EntityManagerInterface $em,
        private readonly ParameterBagInterface $parameterBag
        /*, private readonly ContainerInterface $container*/
    ) {
    }

    public function formatDataArtist($artists, $user)
    {
        //dd($artists);
        $response = [];
        foreach ($artists as $artist) {

            $artistData = [
                'firstname' => $artist->getUserIdUser()->getFirstname(),
                'lastname' => $artist->getUserIdUser()->getLastname(),
                'fullname' => $artist->getFullname(),
                'avatar' => ($artist->getAvatar() ? $this->parameterBag->get('ArtistImgDir') . '/' . $artist->getAvatar() : null),
                'sexe' =>  $artist->getUserIdUser()->getSexe(),
                'dateBirth' => $artist->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                'Artist.createdAt' => $artist->getCreatedAt()->format('Y-m-d'),
                'albums' => [],
            ];

            $artistData['albums'] = $this->formatDataAlbums($artist->getAlbums(), $user);
            // Utiliser un tableau temporaire pour stocker les albums sans l'artiste pour éviter les références circulaires
            /* $tempAlums = [];

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

            $artistData['albums'] = $tempAlbums;*/

            $response[] = $artistData;
        }

        return $response;
    }

    public function formatDataOneArtist($artist, $user)
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
            'avatar' => $this->parameterBag->get('ArtistImgDir') . '/' . $artist->getAvatar(),
            "follower" => 0,
            'sexe' =>  $artist->getUserIdUser()->getSexe(),
            'dateBirth' => $artist->getUserIdUser()->getDateBirth()->format('d-m-Y'),
            'Artist.createdAt' => $artist->getCreatedAt()->format('Y-m-d'),
            'featuring' => [],
            'albums' => $this->formatDataAlbums($albums, $user)
        ];

        $nbrFollower = 0;
        $tempFeaturing = [];

        foreach ($artist->getUsers() as $follower) {
            $nbrFollower+= 1;
        }



        foreach ($artist->getSongs() as $collaborator) {
            $featuringData = [
                'id' => $collaborator->getIdSong(),
                'title' => $collaborator->getTitle(),
                'cover' => ($collaborator->getCover() ? $this->parameterBag->get('SongDir') . '/' . $collaborator->getCover() : null),
                'artist' =>  $this->formatData($collaborator->getArtistIdUser()),
                'createdAt' => $collaborator->getCreatedAt()->format('Y-m-d'),
            ];

            $tempFeaturing[] = $featuringData;
        }
        $artistData['featuring'] = $tempFeaturing;
        $artistData['follower'] = $nbrFollower;


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
                'avatar' => $this->parameterBag->get('ArtistImgDir') . '/' . $artist->getAvatar(),
                'sexe' =>  $artist->getUserIdUser()->getSexe(),
                'dateBirth' => $artist->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                'Artist.createdAt' => $artist->getCreatedAt()->format('Y-m-d'),
                'albums' => [],
            ];

            // Utiliser un tableau temporaire pour stocker les albums sans l'artiste pour éviter les références circulaires
            $tempAlbums = [];
            foreach ($artist->getAlbums() as $album) {

                $label_id = $this->em->getRepository(ArtistHasLabel::class)->findLabel($artist->getId(), $album->getCreatedAt());
                $label = $this->em->getRepository(Label::class)->find($label_id['id']);

                $tempAlbum = [
                    'id' => $album->getIdAlbum(),
                    'nom' => $album->getNom(),
                    'categ' => $album->getCateg(),
                    'cover' => $this->parameterBag->get('AlbumImgDir') . '/' . $album->getCover(),
                    'year' => $album->getYear(),
                    'label' => $label->getNom(), // Remplacez cela par la logique appropriée pour récupérer le label
                    'createdAt' => $album->getCreatedAt()->format('Y-m-d'),
                    'songs' => [],
                ];

                foreach ($album->getSongs() as $song) {
                    $tempAlbum['songs'][] = [
                        'id' => $song->getIdSong(),
                        'title' => $song->getTitle(),
                        'cover' => $this->parameterBag->get('SongDir') . '/' . $song->getCover(),
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

    public function formatDataAlbums($albums, $user)
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
                'cover' => $this->parameterBag->get('AlbumImgDir') . '/' . $album->getCover(),
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
                        'cover' => ($song->getCover() ? $this->parameterBag->get('SongDir') . '/' . $song->getCover() : null),
                        'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
                    ];
                    $responseAlbum['songs'][] = $songData;
                } else {
                    if ($song->isVisibility()) {
                        $songData = [
                            'id' => $song->getIdSong(),
                            'title' => $song->getTitle(),
                            'cover' => ($song->getCover() ? $this->parameterBag->get('SongDir') . '/' . $song->getCover() : null),
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

    public function formatDataAlbumsWithFeaturings($albums, $user)
    {

        $response = [];
        foreach ($albums as $album) {
            $artist = [
                'firstname' => $album->getArtistUserIdUser()->getUserIdUser()->getFirstname(),
                'lastname' => $album->getArtistUserIdUser()->getUserIdUser()->getLastname(),
                'fullname' => $album->getArtistUserIdUser()->getFullname(),
                'cover' => ($album->getArtistUserIdUser()->getAvatar() ? $this->parameterBag->get('ArtistImgDir') . '/' . $album->getArtistUserIdUser()->getAvatar() : null),
                'follower' => count($album->getArtistUserIdUser()->getUserIdUser()->getFollowers()),
                'sexe' =>  $album->getArtistUserIdUser()->getUserIdUser()->getSexe(),
                'dateBirth' => $album->getArtistUserIdUser()->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                'createdAt' => $album->getArtistUserIdUser()->getCreatedAt()->format('Y-m-d')
            ];

            $label_id = $this->em->getRepository(ArtistHasLabel::class)->findLabel($album->getArtistUserIdUser()->getId(), $album->getCreatedAt());

            $label = $this->em->getRepository(Label::class)->find($label_id['id']);

            $responseAlbum = [
                'id' => $album->getIdAlbum(),
                'nom' => $album->getNom(),
                'categ' => $album->getCateg(),
                'cover' => ($album->getCover() ?  $this->parameterBag->get('AlbumImgDir') . '/' . $album->getCover() : null),
                'year' => $album->getYear(),
                'label' => $label->getNom(),
                'createdAt' => $album->getCreatedAt()->format('Y-m-d'),
                'songs' => [],
                'artist' => $artist,

            ];

            foreach ($album->getSongs() as $song) {
                $songData = [
                    'id' => $song->getIdSong(),
                    'title' => $song->getTitle(),
                    'cover' => ($song->getCover() ? $this->parameterBag->get('SongDir') . '/' . $song->getCover() : null),
                    'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
                    'featuring' => []
                ];

                // Ajoutez les artistes en collaboration pour chaque chanson
                foreach ($song->getArtistIdUser() as $collaborator) {
                    $songData['featuring'][] = [
                        'firstname' => $collaborator->getUserIdUser()->getFirstname(),
                        'lastname' => $collaborator->getUserIdUser()->getLastname(),
                        'fullname' => $collaborator->getFullname(),
                        'avatar' => ($collaborator->getAvatar() ? $this->parameterBag->get('ArtistImgDir') . '/' . $collaborator->getAvatar() : null),
                        'sexe' =>  $collaborator->getUserIdUser()->getSexe(),
                        'dateBirth' => $collaborator->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                        'Artist.createdAt' => $collaborator->getCreatedAt()->format('Y-m-d')
                    ];
                }

                $responseAlbum['songs'][] = $songData;
            }

            $response[] = $responseAlbum;
        }


        return $response;
    }


    public function formatDataAlbumWithFeaturings($album, $user)
    {

        $response = [];

        $artist = [
            'firstname' => $album->getArtistUserIdUser()->getUserIdUser()->getFirstname(),
            'lastname' => $album->getArtistUserIdUser()->getUserIdUser()->getLastname(),
            'fullname' => $album->getArtistUserIdUser()->getFullname(),
            'cover' => ($album->getArtistUserIdUser()->getAvatar() ? $this->parameterBag->get('ArtistImgDir') . '/' . $album->getArtistUserIdUser()->getAvatar() : null),
            'follower' => count($album->getArtistUserIdUser()->getUsers()),
            'sexe' =>  $album->getArtistUserIdUser()->getUserIdUser()->getSexe(),
            'dateBirth' => $album->getArtistUserIdUser()->getUserIdUser()->getDateBirth()->format('d-m-Y'),
            'createdAt' => $album->getArtistUserIdUser()->getCreatedAt()->format('Y-m-d')
        ];

        $label_id = $this->em->getRepository(ArtistHasLabel::class)->findLabel($album->getArtistUserIdUser()->getId(), $album->getCreatedAt());

        $label = $this->em->getRepository(Label::class)->find($label_id['id']);

        $responseAlbum = [
            'id' => $album->getIdAlbum(),
            'nom' => $album->getNom(),
            'categ' => $album->getCateg(),
            'cover' => ($album->getCover() ?  $this->parameterBag->get('AlbumImgDir') . '/' . $album->getCover() : null),
            'year' => $album->getYear(),
            'label' => $label->getNom(),
            'createdAt' => $album->getCreatedAt()->format('Y-m-d'),
            'songs' => [],
            'artist' => $artist,

        ];

        foreach ($album->getSongs() as $song) {
            $songData = [
                'id' => $song->getIdSong(),
                'title' => $song->getTitle(),
                'cover' => ($song->getCover() ? $this->parameterBag->get('SongDir') . '/' . $song->getCover() : null),
                'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
                'featuring' => []
            ];

            // Ajoutez les artistes en collaboration pour chaque chanson
            foreach ($song->getArtistIdUser() as $collaborator) {
                $songData['featuring'][] = [
                    'firstname' => $collaborator->getUserIdUser()->getFirstname(),
                    'lastname' => $collaborator->getUserIdUser()->getLastname(),
                    'fullname' => $collaborator->getFullname(),
                    'avatar' => ($collaborator->getAvatar() ? $this->parameterBag->get('ArtistImgDir') . '/' . $collaborator->getAvatar() : null),
                    'sexe' =>  $collaborator->getUserIdUser()->getSexe(),
                    'dateBirth' => $collaborator->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                    'Artist.createdAt' => $collaborator->getCreatedAt()->format('Y-m-d')
                ];
            }

            $responseAlbum['songs'][] = $songData;
        }

        $response[] = $responseAlbum;


        return $response;
    }
}
