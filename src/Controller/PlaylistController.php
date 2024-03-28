<?php

namespace App\Controller;

use App\Entity\Playlist;
use App\Repository\PlaylistHasSongRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('api')]
class PlaylistController extends AbstractController
{
    private $repository;

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Playlist::class);

    }

    #[Route('/playlists', name: 'app_all_playlist', methods: ['GET'])]
    public function getAllSongs(SerializerInterface $serializerInterface): JsonResponse
    {
        $playlist = $this->repository->findAll();
        $jsonPlaylistList = $serializerInterface->serialize($playlist, 'json', ['groups' => 'getPlaylist']);

        return new JsonResponse($jsonPlaylistList, Response::HTTP_OK, [], true);
    }

    #[Route('/playlist/{id}', name: 'app_detail_playlist', methods: ['GET'])]
    public function getDetailSong(Playlist $playlist, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonPlaylistList = $serializerInterface->serialize($playlist, 'json', ['groups' => 'getPlaylist']);

        return new JsonResponse($jsonPlaylistList, Response::HTTP_OK, [], true);
    }

    #[Route('/playlist', name: 'app_create_playlist', methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializerInterface, PlaylistHasSongRepository $playlistHasSongRepository): JsonResponse
    {
        $playlist = new Playlist();
        $playlist->setIdPlaylist($request->get('idplaylist'))
            ->setTitle($request->get('title'))
            ->setPublic($request->get('public'))
            ->setPlaylistHasSong($playlistHasSongRepository->find($request->get('idplaylisthassong')));

        $this->entityManager->persist($playlist);
        $this->entityManager->flush();
        $jsonPlaylistList = $serializerInterface->serialize($playlist, 'json', ['groups' => 'getPlaylist']);

        return new JsonResponse($jsonPlaylistList, Response::HTTP_CREATED, [], true);
    }

    #[Route('/playlist/{id}', name: 'app_create_playlist', methods: ['PUT'])]
    public function update(Playlist $playlist, Request $request, SerializerInterface $serializerInterface, PlaylistHasSongRepository $playlistHasSongRepository): JsonResponse
    {
        $playlist = $this->repository->find($playlist);
        $playlist->setTitle($request->get('title'))
            ->setPublic($request->get('public'))
            ->setPlaylistHasSong($playlistHasSongRepository->find($request->get('idplaylisthassong')));

        $this->entityManager->persist($playlist);
        $this->entityManager->flush();
        $jsonPlaylistList = $serializerInterface->serialize($playlist, 'json', ['groups' => 'getPlaylist']);

        return new JsonResponse($jsonPlaylistList, Response::HTTP_BAD_REQUEST, [], true);
    }
    #[Route('/playlist/{id}', name: 'app_delete_playlist', methods: ['DELETE'])]
    public function delete(Playlist $playlist): JsonResponse
    {
        $this->entityManager->remove($playlist);
        $this->entityManager->flush();

        return new JsonResponse(["message" => "Delete Playlist Success"], Response::HTTP_NOT_FOUND);
    }
}
