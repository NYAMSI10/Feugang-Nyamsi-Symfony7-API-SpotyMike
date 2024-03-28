<?php

namespace App\Controller;

use App\Entity\PlaylistHasSong;
use App\Repository\PlaylistHasSongRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('api')]
class PlaylistHasSongController extends AbstractController
{
    private $repository;

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(PlaylistHasSong::class);

    }

    #[Route('/playlisthassong', name: 'app_all_playlisthassong', methods: ['GET'])]
    public function getAllSongs(SerializerInterface $serializerInterface): JsonResponse
    {
        $playlist = $this->repository->findAll();
        $jsonPlaylistList = $serializerInterface->serialize($playlist, 'json', ['groups' => 'getPlaylist']);
        return new JsonResponse($jsonPlaylistList, Response::HTTP_OK, [], true);
    }

    #[Route('/playlisthassong/{id}', name: 'app_detail_playlisthassong', methods: ['GET'])]
    public function getDetailSong(PlaylistHasSong $playlist, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonPlaylistList = $serializerInterface->serialize($playlist, 'json', ['groups' => 'getPlaylist']);
        return new JsonResponse($jsonPlaylistList, Response::HTTP_OK, [], true);
    }

    #[Route('/playlisthassong', name: 'app_create_playlisthassong', methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializerInterface, PlaylistHasSongRepository $playlistHasSongRepository): JsonResponse
    {
        $playlisthassong = new PlaylistHasSong();
        $playlisthassong->setDownload($request->get('download'))
            ->setPosition($request->get('position'));

        $this->entityManager->persist($playlisthassong);
        $this->entityManager->flush();
        $jsonPlaylistList = $serializerInterface->serialize($playlisthassong, 'json', ['groups' => 'getPlaylist']);

        return new JsonResponse($jsonPlaylistList, Response::HTTP_CREATED, [], true);
    }

    #[Route('/playlisthassong/{id}', name: 'app_update_playlisthassong', methods: ['PUT'])]
    public function update(PlaylistHasSong $playlist,Request $request, SerializerInterface $serializerInterface, PlaylistHasSongRepository $playlistHasSongRepository): JsonResponse
    {
        $playlisthassong = $this->repository->find($playlist);
        $playlisthassong->setDownload($request->get('download'))
            ->setPosition($request->get('position'));

        $this->entityManager->persist($playlisthassong);
        $this->entityManager->flush();
        $jsonPlaylistList = $serializerInterface->serialize($playlisthassong, 'json', ['groups' => 'getPlaylist']);

        return new JsonResponse($jsonPlaylistList, Response::HTTP_BAD_REQUEST, [], true);
    }

    #[Route('/playlisthassong/{id}', name: 'app_delete_playlisthassong', methods: ['DELETE'])]
    public function delete(PlaylistHasSong $playlist): JsonResponse
    {
        $this->entityManager->remove($playlist);
        $this->entityManager->flush();

        return new JsonResponse(["message" => "Delete PlaylistHasSong Success"], Response::HTTP_NOT_FOUND);
    }
}
