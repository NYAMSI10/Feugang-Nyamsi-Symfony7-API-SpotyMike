<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Album;
use Doctrine\ORM\EntityManager;
use App\Repository\UserRepository;
use App\Service\GenerateId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AlbumController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $artistRepository;
    private $serializer;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Album::class);
        $this->artistRepository = $entityManager->getRepository(Artist::class);
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    #[Route('/album', name: 'album_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $albums = $this->repository->findAll();

        $data = $this->serializer->serialize($albums, 'json', [
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['id'],
        ]);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/album', name: 'album_new', methods: 'POST')]
    public function new(Request $request, GenerateId $generateId): JsonResponse
    {
        $existAlbum = $this->repository->findOneBy(["nom" => $request->get('nom')]);

        if ($existAlbum) {
            return $this->json([
                'error' => true,
                'message' => 'Ce nom existe déjà',
            ], Response::HTTP_CONFLICT);
        }

        $album = new Album();
        $album->setIdAlbum($generateId->randId())
            ->setNom($request->get('nom'))
            ->setCateg($request->get('categ'))
            ->setCover($request->get('cover'))
            ->setArtistUserIdUser($this->artistRepository->find($request->get('idartist')));

        $errors = $this->validator->validate($album);
        if (count($errors) > 0) {

            return $this->json([
                'error' => true,
                'message' => 'Une ou plusieurs données obligatoires sont manquantes',
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($album);
        $this->entityManager->flush();
        return $this->json([
            'error' => false,
            'message' => 'Album ajouté avec succès',
        ], Response::HTTP_OK);
    }

    #[Route('album/{id}', name: 'album_show', methods: ['GET'])]
    public function show(Request $request): JsonResponse
    {

        $album = $this->repository->find($request->get("id"));
        if ($album) {
            $jsonAlbumList = $this->serializer->serialize(["error" => false, "album" => $album], 'json', ['groups' => 'getAlbums']);

            return new JsonResponse($jsonAlbumList, Response::HTTP_OK, [], true);
        } else {
            return $this->json([
                'error' => true,
                'message' => 'Album Not Found',
            ], Response::HTTP_NOT_FOUND);
        }
    }

    // #[Route('album/edit/{id}', name: 'album_edit', methods: ['POST', 'PUT'])]
    // public function edit(Request $request, Album $album): JsonResponse
    // {
    //     $data_received = $request->request->all();

    //     $album->setNom(isset($data_received['nom']) ? $data_received['nom'] : $album->getNom());
    //     $album->setCateg(isset($data_received['categ']) ? $data_received['categ'] : $album->getCateg());
    //     $album->setCover(isset($data_received['cover']) ? $data_received['cover'] : $album->getCover());
    //     $album->setYear(isset($data_received['year']) ? $data_received['year'] : date('Y'));


    //     $this->entityManager->persist($album);
    //     $this->entityManager->flush();

    //     return $this->json([
    //         'message' => 'Album modified successfully',
    //         'data' =>  $album->jsonSerialize()
    //     ], Response::HTTP_OK);
    // }

    // #[Route('album/{id}', name: 'album_delete', methods: ['DELETE'])]
    // public function delete(Request $request, Album $album): JsonResponse
    // {

    //     $this->entityManager->remove($album);
    //     $this->entityManager->flush();
    //     $data = $this->serializer->serialize($album, 'json');

    //     return $this->json([
    //         'message' => 'Album deleted successfully',
    //         'data' =>  $album->jsonSerialize()
    //     ], Response::HTTP_OK);
    // }
}
