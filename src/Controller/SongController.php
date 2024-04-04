<?php

namespace App\Controller;

use App\Entity\Song;
use App\Repository\AlbumRepository;
use App\Repository\ArtistRepository;
use App\Repository\PlaylistHasSongRepository;
use App\Service\GenerateId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SongController extends AbstractController
{
    private $repository;
    private $serializer;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Song::class);
        $this->serializer = $serializer;
    }

    /*#[Route('songs', name: 'app_all_song', methods: ['GET'])]
    public function getAllSongs(): JsonResponse
    {
        $songs = $this->repository->findAll();
        $jsonSongList = $this->serializer->serialize($songs, 'json', ['groups' => 'getSongs']);

        return new JsonResponse($jsonSongList, Response::HTTP_OK, [], true);
    }*/

    #[Route('song/{id}', name: 'app_detail_song', methods: ['GET'])]
    public function getDetailSong(Request $request, int $id = 0): JsonResponse
    {
        $id = $request->get('id');

        if (!isset($id) || $id == 0) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Une ou plusieurs données obligatoires sont manquantes"],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }
        $song = $this->repository->find($request->get("id"));
        if ($song) {
            $jsonSongList = $this->serializer->serialize(["error" => false, "song" => $song], 'json', ['groups' => 'getSong']);

            return new JsonResponse($jsonSongList, Response::HTTP_OK, [], true);
        } else {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Une ou plusieurs données obligatoires sont erronnées"],
                'json'
            );
            return new JsonResponse($data, Response::HTTP_CONFLICT, [], true);
        }
    }

    #[Route('song', name: 'app_create_song', methods: ['POST'])]
    public function create(Request $request, AlbumRepository $albumRepository, PlaylistHasSongRepository $playlistHasSongRepository, ArtistRepository $artistRepository, GenerateId $generateId): JsonResponse
    {
        $song = new Song();
        $song->setIdSong($generateId->randId())
            ->setTitle($request->get('title'))
            ->setUrl($request->get('url'))
            ->setCover($request->get('cover'))
            ->setVisibility($request->get('visibility'))
            ->addArtistIdUser($artistRepository->find($request->get('idartistuser')))
            ->setAlbum($albumRepository->find($request->get('idalbum')))
            ->setPlaylistHasSong($playlistHasSongRepository->find($request->get('idplaylisthassong')));
        $this->entityManager->persist($song);
        $this->entityManager->flush();
        $jsonSongList = $this->serializer->serialize($song, 'json', ['groups' => 'getSongs']);

        return new JsonResponse($jsonSongList, Response::HTTP_CREATED, [], true);
    }

    #[Route('/song/{id}', name: 'app_update_song', methods: ['PUT'])]
    public function update(Request $request, Song $song, AlbumRepository $albumRepository, PlaylistHasSongRepository $playlistHasSongRepository, ArtistRepository $artistRepository): JsonResponse
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
        $jsonSongList = $this->serializer->serialize($song, 'json', ['groups' => 'getSongs']);

        return new JsonResponse(["songs:" => $jsonSongList], Response::HTTP_BAD_REQUEST, [], true);
    }

    #[Route('song/{id}', name: 'app_delete_song', methods: ['DELETE'])]
    public function delete(Song $song): JsonResponse
    {
        $this->entityManager->remove($song);
        $this->entityManager->flush();

        return new JsonResponse(["message" => "Delete Song Success"], Response::HTTP_NOT_FOUND);
    }
}
