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

        $data = $this->serializer->serialize($users, 'json');

        return $this->json([
            'message' => 'List of users',
            'data' =>   $data
            ],Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(Request $request,User $user): JsonResponse
    {
        return $this->json([
            'message' => 'User retreive successefully',
            'data' =>  $user->jsonSerialize()
            ],Response::HTTP_OK);
    }

    #[Route('/new', name: 'user_new', methods: 'POST')]
    public function new(Request $request): JsonResponse
    {
        
        $data_received = $request->request->all();

        $search = $this->repository->findBy(['email' => $data_received['email']]);
        if($search) {
            return $this->json([
                'message' => 'This email already exists, Please change',
            ]);
        }

        $user = new User();
        $user->setIdUser(md5(uniqid($data_received['email'], true)));
        $user->setName($data_received['name']);
        $user->setEmail($data_received['email']);
        $user->setTel($data_received['tel']);
        
        $hashedPassword = password_hash($data_received['encrypte'], PASSWORD_BCRYPT);
        $user->setEncrypte($hashedPassword);
       
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $data = $this->serializer->serialize($user, 'json');
        
        return new JsonResponse($data, Response::HTTP_CREATED, [], true);
    }

    #[Route('/edit/{id}', name: 'user_edit', methods: ['POST','PUT'])]
    public function edit(Request $request,User $user): JsonResponse
    {
        $data_received = $request->request->all();

        $user->setName(empty($data_received['name'])?$user->getName():$data_received['name']);
        $user->setEmail(empty($data_received['email'])?$user->getEmail():$data_received['email']);
        $user->setTel($data_received['tel']);

        if(isset($data_received['encrypte'])) {
            $hashedPassword = password_hash($data_received['encrypte'], PASSWORD_BCRYPT);
            $user->setEncrypte($hashedPassword);
        }
     

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        
        return $this->json([
            'message' => 'User modified successefully',
            'data' =>  $user->jsonSerialize()
            ],Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(Request $request, User $user): JsonResponse
    {
        
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'User deleted successefully',
            'data' =>  $user->jsonSerialize()
            ],Response::HTTP_OK);
    }
}
