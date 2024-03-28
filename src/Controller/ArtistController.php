<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use App\Repository\UserRepository;
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

#[Route('/api/artist')]
class ArtistController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $serializer;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager,SerializerInterface $serializer,ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Artist::class);
        $this->serializer = $serializer;
        $this->validator = $validator;

    }

    #[Route('/', name: 'artist_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $artistes = $this->repository->findAll();

        $data = $this->serializer->serialize($artistes, 'json', [
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['id'],
        ]);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
        return $this->json([
            'message' => 'List of artists',
            'data' =>   $data
            ],Response::HTTP_OK);

    }

    #[Route('/new', name: 'artist_new', methods: 'POST')]
    public function new(Request $request): JsonResponse
    {
        $data_received = $request->request->all();

        if(!isset($data_received['User_idUser'])) {
            return $this->json([
                'message' => 'Please choose a user',
            ]);
        }


        $artist = new Artist();

        $user = $this->entityManager->getRepository(User::class)->find($data_received['User_idUser']);
        $artist->setUserIdUser($user);
        $artist->setFullname($data_received['fullname']);
        $artist->setLabel($data_received['label']);
        $artist->setDescription($data_received['description']);

        $errors = $this->validator->validate($artist);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->persist($artist);
        $this->entityManager->flush();
        
        return $this->json([
            'message' => 'Artist created successfully',
            'data' =>  $artist->jsonSerialize()
            ],Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'artist_show', methods: ['GET'])]
    public function show(Request $request,Artist $artist): JsonResponse
    {
        return $this->json([
            'message' => 'Artist retreive successfully',
            'data' =>  $artist->jsonSerialize()
            ],Response::HTTP_OK);
    }

    #[Route('/edit/{id}', name: 'artist_edit', methods: ['POST','PUT'])]
    public function edit(Request $request,Artist $artist): JsonResponse
    {
        $data_received = $request->toArray();

        $artist->setFullname($data_received['fullname']?$data_received['fullname']:$artist->getFullname());
        $artist->setLabel($data_received['label']?$data_received['fullname']:$artist->getLabel());
        $artist->setDescription($data_received['description']);
        

        $this->entityManager->persist($artist);
        $this->entityManager->flush();
        
        return $this->json([
            'message' => 'Artist modified successfully',
            'data' =>  $artist->jsonSerialize()
            ],Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'artist_delete', methods: ['DELETE'])]
    public function delete(Request $request, Artist $artist): JsonResponse
    {
        
        $this->entityManager->remove($artist);
        $this->entityManager->flush();
        $data = $this->serializer->serialize($artist, 'json');

        return $this->json([
            'message' => 'Artist deleted successfully',
            'data' =>  $artist->jsonSerialize()
            ],Response::HTTP_OK);
    }
}
