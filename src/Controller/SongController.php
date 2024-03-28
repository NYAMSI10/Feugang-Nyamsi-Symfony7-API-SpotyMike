<?php

namespace App\Controller;

use App\Entity\Song;
use App\Repository\AlbumRepository;
use App\Repository\ArtistRepository;
use App\Repository\PlaylistHasSongRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('api')]
class SongController extends AbstractController
{
    private $repository;

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Song::class);

    }

    #[Route('/songs', name: 'app_all_song', methods: ['GET'])]
    public function getAllSongs(SerializerInterface $serializerInterface): JsonResponse
    {
        $songs = $this->repository->findAll();
        $jsonSongList = $serializerInterface->serialize($songs, 'json', ['groups' => 'getSongs']);

        return new JsonResponse($jsonSongList, Response::HTTP_OK, [], true);
    }

    #[Route('/song/{id}', name: 'app_detail_song', methods: ['GET'])]
    public function getDetailSong(Song $song, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonSongList = $serializerInterface->serialize($song, 'json', ['groups' => 'getSongs']);

        return new JsonResponse($jsonSongList, Response::HTTP_OK, [], true);
    }

    #[Route('/song/create', name: 'app_create_song', methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializerInterface, ValidatorInterface $validator, AlbumRepository $albumRepository, PlaylistHasSongRepository $playlistHasSongRepository, ArtistRepository $artistRepository): JsonResponse
    {
        $song = new Song();
        $song->setIdSong($request->get('idsong'))
            ->setTitle($request->get('title'))
            ->setUrl($request->get('url'))
            ->setCover($request->get('cover'))
            ->setVisibility($request->get('visibility'))
            ->addArtistIdUser($artistRepository->find($request->get('idartistuser')))
            ->setAlbum($albumRepository->find($request->get('idalbum')))
            ->setPlaylistHasSong($playlistHasSongRepository->find($request->get('idplaylisthassong')));


        $this->entityManager->persist($song);
        $this->entityManager->flush();
        $jsonSongList = $serializerInterface->serialize($song, 'json', ['groups' => 'getSongs']);

        return new JsonResponse($jsonSongList, Response::HTTP_CREATED, [], true);

    }

    #[Route('/song/{id}', name: 'app_update_song', methods: ['PUT'])]
    public function update(Request $request, Song $song, SerializerInterface $serializerInterface, ValidatorInterface $validator, AlbumRepository $albumRepository, PlaylistHasSongRepository $playlistHasSongRepository, ArtistRepository $artistRepository): JsonResponse
    {
        $song = $this->repository->find($song);
        $song->setTitle($request->get('title'))
            ->setUrl($request->get('url'))
            ->setCover($request->get('cover'))
            ->setVisibility($request->get('visibility'))
            ->addArtistIdUser($artistRepository->find($request->get('idartistuser')))
            ->setAlbum($albumRepository->find($request->get('idalbum')))
            ->setPlaylistHasSong($playlistHasSongRepository->find($request->get('idplaylisthassong')));

        $this->entityManager->persist($song);
        $this->entityManager->flush();
        $jsonSongList = $serializerInterface->serialize($song, 'json', ['groups' => 'getSongs']);

        return new JsonResponse($jsonSongList, Response::HTTP_BAD_REQUEST, [], true);

    }

    #[Route('/song/{id}', name: 'app_delete_song', methods: ['DELETE'])]
    public function delete(Song $song): JsonResponse
    {
        $this->entityManager->remove($song);
        $this->entityManager->flush();

        return new JsonResponse(["message" => "Delete Song Success"], Response::HTTP_NOT_FOUND);
    }


}
