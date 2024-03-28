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

    }

    #[Route('/new', name: 'artist_new', methods: 'POST')]
    public function new(Request $request): JsonResponse
    {
        $data_received = $request->toArray();

        $artist = new Artist();

        $user = $this->entityManager->getRepository(User::class)->find($data_received['User_idUser']);
        $artist->setUserIdUser($user);
        $artist->setFullname($data_received['fullname']);
        $artist->setLabel($data_received['label']);
        $artist->setDescription($data_received['description']);
        
        $this->entityManager->persist($artist);
        $this->entityManager->flush();
        
        $data = $this->serializer->serialize($artist, 'json');
        
        return new JsonResponse($data, Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', name: 'artist_show', methods: ['GET'])]
    public function show(Request $request,Artist $artist): JsonResponse
    {
        //$user = $this->repository->find($request->get('id'));

        $data = $this->serializer->serialize($artist, 'json', [
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['id'],
        ]);
        
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/edit/{id}', name: 'artist_edit', methods: ['POST','PUT'])]
    public function edit(Request $request,Artist $artist): JsonResponse
    {
        $data_received = $request->toArray();

        $artist->setFullname($data_received['fullname']);
        $artist->setLabel($data_received['label']);
        $artist->setDescription($data_received['description']);
        

        $this->entityManager->persist($artist);
        $this->entityManager->flush();
        
        $data = $this->serializer->serialize($artist, 'json');
        
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'artist_delete', methods: ['DELETE'])]
    public function delete(Request $request, Artist $artist): JsonResponse
    {
        
        $this->entityManager->remove($artist);
        $this->entityManager->flush();
        $data = $this->serializer->serialize($artist, 'json');

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
