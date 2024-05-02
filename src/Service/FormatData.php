<?php

namespace App\Service;

use App\Entity\Album;
use App\Entity\ArtistHasLabel;
use App\Entity\Label;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class FormatData
{
    protected $baseUrl;
    public function __construct(
        protected EntityManagerInterface $em,
        private readonly ParameterBagInterface $parameterBag,
        /*, private readonly ContainerInterface $container*/
        RequestStack $requestStack
    ) {
        $this->baseUrl = $requestStack->getCurrentRequest()->getSchemeAndHttpHost();
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
                'avatar' => ($artist->getAvatar() ? $this->baseUrl . '/images/artists/' . $artist->getAvatar() : ''),
                'sexe' =>  $artist->getUserIdUser()->getSexe(),
                'dateBirth' => $artist->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                'Artist.createdAt' => $artist->getCreatedAt()->format('Y-m-d'),
                'albums' => [],
            ];

            $artistData['albums'] = $this->formatDataAlbums($artist->getAlbums(), $user);

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
            'avatar' => ($artist->getAvatar() ? $this->baseUrl . '/images/artists/' . $artist->getAvatar() : ''),
            "follower" => 0,
            'sexe' =>  $artist->getUserIdUser()->getSexe(),
            'dateBirth' => $artist->getUserIdUser()->getDateBirth()->format('d-m-Y'),
            'Artist.createdAt' => $artist->getCreatedAt()->format('Y-m-d'),
            'featuring' => [],
            'albums' => $this->formatDataAlbums($albums, $user)
        ];

        $tempFeaturing = [];
        $nbrFollower = 0;

        foreach ($artist->getUsers() as $follower) {
            $nbrFollower += 1;
        }



        foreach ($artist->getSongs() as $collaborator) {
            $featuringData = [
                'id' => $collaborator->getIdSong(),
                'title' => $collaborator->getTitle(),
                'cover' => ($collaborator->getCover() ? $this->baseUrl . '/songs/' . $collaborator->getCover() : ''),
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
                //'avatar' => $this->parameterBag->get('ArtistImgDir') . '/' . $artist->getAvatar(),
                'avatar' => ($artist->getAvatar() ? $this->baseUrl . '/images/artists/' . $artist->getAvatar() : ''),
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
                    //'cover' => $this->parameterBag->get('AlbumImgDir') . '/' . $album->getCover(),
                    'cover' => ($album->getCover() ? $this->baseUrl . '/images/albums/' . $album->getCover() : ''),
                    'year' => $album->getYear(),
                    'label' => $label->getNom(), // Remplacez cela par la logique appropriée pour récupérer le label
                    'createdAt' => $album->getCreatedAt()->format('Y-m-d'),
                    'songs' => [],
                ];

                foreach ($album->getSongs() as $song) {
                    $tempAlbum['songs'][] = [
                        'id' => $song->getIdSong(),
                        'title' => $song->getTitle(),
                        //'cover' => $this->parameterBag->get('SongDir') . '/' . $song->getCover(),
                        'cover' => ($song->getCover() ? $this->baseUrl . '/songs/' . $song->getCover() : ''),
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
                //'cover' => $this->parameterBag->get('AlbumImgDir') . '/' . $album->getCover(),
                'cover' => ($album->getCover() ? $this->baseUrl . '/images/albums/' . $album->getCover() : ''),
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
                        //'cover' => ($song->getCover() ? $this->parameterBag->get('SongDir') . '/' . $song->getCover() : null),
                        'cover' => ($song->getCover() ? $this->baseUrl . '/songs/' . $song->getCover() : ''),
                        'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
                    ];
                    $responseAlbum['songs'][] = $songData;
                } else {
                    if ($song->isVisibility()) {
                        $songData = [
                            'id' => $song->getIdSong(),
                            'title' => $song->getTitle(),
                            //'cover' => ($song->getCover() ? $this->parameterBag->get('SongDir') . '/' . $song->getCover() : null),
                            'cover' => ($song->getCover() ? $this->baseUrl . '/songs' . $song->getCover() : ''),
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
                //'cover' => ($album->getArtistUserIdUser()->getAvatar() ? $this->parameterBag->get('ArtistImgDir') . '/' . $album->getArtistUserIdUser()->getAvatar() : null),
                'cover' => ($album->getArtistUserIdUser()->getAvatar() ? $this->baseUrl . '/images/artists/' . $album->getArtistUserIdUser()->getAvatar() : ''),
                'follower' => 0,
                'sexe' =>  $album->getArtistUserIdUser()->getUserIdUser()->getSexe(),
                'dateBirth' => $album->getArtistUserIdUser()->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                'createdAt' => $album->getArtistUserIdUser()->getCreatedAt()->format('Y-m-d')
            ];
            $nbrFollower = 0;

            foreach ($album->getArtistUserIdUser()->getUsers() as $follower) {
                $nbrFollower += 1;
            }

            $artist['follower'] = $nbrFollower;


            $label_id = $this->em->getRepository(ArtistHasLabel::class)->findLabel($album->getArtistUserIdUser()->getId(), $album->getCreatedAt());

            $label = $this->em->getRepository(Label::class)->find($label_id['id']);

            $responseAlbum = [
                'id' => $album->getIdAlbum(),
                'nom' => $album->getNom(),
                'categ' => $album->getCateg(),
                //'cover' => ($album->getCover() ?  $this->parameterBag->get('AlbumImgDir') . '/' . $album->getCover() : null),
                'cover' => ($album->getCover() ? $this->baseUrl . '/images/albums/' . $album->getCover() : ''),
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
                    //'cover' => ($song->getCover() ? $this->parameterBag->get('SongDir') . '/' . $song->getCover() : null),
                    'cover' => ($song->getCover() ? $this->baseUrl . '/songs/' . $song->getCover() : ''),
                    'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
                    'featuring' => []
                ];

                // Ajoutez les artistes en collaboration pour chaque chanson
                foreach ($song->getArtistIdUser() as $collaborator) {
                    $songData['featuring'][] = [
                        'firstname' => $collaborator->getUserIdUser()->getFirstname(),
                        'lastname' => $collaborator->getUserIdUser()->getLastname(),
                        'fullname' => $collaborator->getFullname(),
                        'avatar' => ($collaborator->getAvatar() ? $this->baseUrl . '/images/artists/' . $collaborator->getAvatar() : ''),
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
            'cover' => ($album->getArtistUserIdUser()->getAvatar() ? $this->baseUrl . '/images/artists/' . $album->getArtistUserIdUser()->getAvatar() : ''),
            'follower' => 0,
            'sexe' =>  $album->getArtistUserIdUser()->getUserIdUser()->getSexe(),
            'dateBirth' => $album->getArtistUserIdUser()->getUserIdUser()->getDateBirth()->format('d-m-Y'),
            'createdAt' => $album->getArtistUserIdUser()->getCreatedAt()->format('Y-m-d')
        ];

        $nbrFollower = 0;

        foreach ($album->getArtistUserIdUser()->getUsers() as $follower) {
            $nbrFollower += 1;
        }

        $artist['follower'] = $nbrFollower;

        $label_id = $this->em->getRepository(ArtistHasLabel::class)->findLabel($album->getArtistUserIdUser()->getId(), $album->getCreatedAt());

        $label = $this->em->getRepository(Label::class)->find($label_id['id']);

        $responseAlbum = [
            'id' => $album->getIdAlbum(),
            'nom' => $album->getNom(),
            'categ' => $album->getCateg(),
            'cover' => ($album->getCover() ? $this->baseUrl . '/images/albums/' . $album->getCover() : ''),
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
                'cover' => ($song->getCover() ? $this->baseUrl . '/songs/' . $song->getCover() : ''),
                'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
                'featuring' => []
            ];

            // Ajoutez les artistes en collaboration pour chaque chanson
            foreach ($song->getArtistIdUser() as $collaborator) {
                $songData['featuring'][] = [
                    'firstname' => $collaborator->getUserIdUser()->getFirstname(),
                    'lastname' => $collaborator->getUserIdUser()->getLastname(),
                    'fullname' => $collaborator->getFullname(),
                    'avatar' => ($collaborator->getAvatar() ? $this->baseUrl . '/images/artists/' . $collaborator->getAvatar() : ''),
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
