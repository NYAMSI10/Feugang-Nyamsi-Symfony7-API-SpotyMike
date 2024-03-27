<?php

namespace App\Controller;

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

#[Route('/api/user')]
class UserController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $serializer;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager,SerializerInterface $serializer,ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
        $this->serializer = $serializer;
        $this->validator = $validator;

    }

    #[Route('/', name: 'user_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->repository->findAll();

        $data = $this->serializer->serialize($users, 'json');
        return new JsonResponse($data, Response::HTTP_OK, [], true);

    }

    #[Route('/new', name: 'user_new', methods: 'POST')]
    public function new(Request $request): JsonResponse
    {
        $data_received = $request->toArray();
        $search = $this->repository->findBy(['email' => $data_received['email']]);
        if($search) {
            return $this->json([
                'message' => 'This email already exists, Please change',
            ]);
        }


        $user = new User();
        $user->setName($data_received['name']);
        $user->setEmail($data_received['email']);
        $user->setTel($data_received['tel']);
        
        $hashedPassword = password_hash($data_received['encrypte'], PASSWORD_BCRYPT);
        $user->setEncrypte($hashedPassword);
        
        /*$errors = $this->validator->validate($user);
        
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize((string)$errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }*/
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $data = $this->serializer->serialize($user, 'json');
        
        return new JsonResponse($data, Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(Request $request,User $user): JsonResponse
    {
        //$user = $this->repository->find($request->get('id'));

        $data = $this->serializer->serialize($user, 'json');
        
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/edit/{id}', name: 'app_user_edit', methods: ['POST','PUT'])]
    public function edit(Request $request,User $user): JsonResponse
    {
        $data_received = $request->toArray();

        $user->setName($data_received['name']);
        $user->setEmail($data_received['email']);
        $user->setTel($data_received['tel']);

        if(isset($data_received['encrypte'])) {
            $hashedPassword = password_hash($data_received['encrypte'], PASSWORD_BCRYPT);
            $user->setEncrypte($hashedPassword);
        }
        

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $data = $this->serializer->serialize($user, 'json');
        
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(Request $request, User $user): JsonResponse
    {
        
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        $data = $this->serializer->serialize($user, 'json');

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
