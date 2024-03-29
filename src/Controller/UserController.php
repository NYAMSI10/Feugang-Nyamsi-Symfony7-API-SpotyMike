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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager,SerializerInterface $serializer,ValidatorInterface $validator,UserPasswordHasherInterface $userPasswordHasherInterface)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->passwordHasher = $userPasswordHasherInterface;

    }

    #[Route('/', name: 'user_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->repository->findAll();

        $data = $this->serializer->serialize($users, 'json', ['groups' => 'getUsers']);

        return $this->json([
            'message' => 'List of users',
            'data' =>   $data
            ],Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(Request $request): JsonResponse
    {
        $user = $this->repository->find($request->get("id"));
        if($user)
            {
                $data = $this->serializer->serialize($user, 'json', ['groups' => 'getUsers']);
                return $this->json([
                'message' => 'User retreive successefully',
                'data' =>  $data
                ],Response::HTTP_OK);
            }
        else
            return $this->json([
                'message' => 'User not found'
                ],Response::HTTP_NOT_FOUND);    
    }

    #[Route('/register', name: 'user_new', methods: 'POST')]
    public function new(Request $request): JsonResponse
    {
        $firstname = $request->get('firstname');
        $lastname = $request->get('lastname');
        $email = $request->get('email');
        $password = $request->get('password');
        $dateBirth = $request->get('dateBirth');


        $today = new \DateTime();
        $birthdate = new \DateTime($dateBirth);
        $age = $today->diff($birthdate)->y;

        if(!isset($firstname) || !isset($lastname) || !isset($email) || !isset($password) || !isset($dateBirth)) {
            return $this->json([
                'error' => true,
                'message' => 'Une ou plusieurs données obligatoires sont manquantes',
            ],Response::HTTP_BAD_REQUEST);
        }

        if($age < 12) {
            return $this->json([
                'error' => true,
                'message' => "L'age de l'utilisateur ne permet pas(12 ans)",
            ],Response::HTTP_NOT_ACCEPTABLE);
        }

        $search = $this->repository->findBy(['email' => $request->get('email')]);
        if($search) {
            return $this->json([
                'error' => true,
                'message' => 'Un compte utilisant cette adresse mail est déjà enregistré',
            ],Response::HTTP_CONFLICT);
        }
        try {
            $user = new User();
            $user->setIdUser(md5(uniqid($email, true)));
            $user->setFirstname($firstname);
            $user->setEmail($email);
            $user->setTel($request->get('tel'));
            $user->setSexe($request->get('sexe'));
            
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $password
            );
            $user->setPassword($hashedPassword);
        
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json([
                    'error'=> true,
                    'message' => "Une ou plusieurs données sont erronées",
                    'data' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }
        
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->json([
                'error' => false,
                'message' => "L'utilisateur a bien été créé avec succès",
                'user' =>  $this->serializer->serialize($user, 'json', ['groups' => 'getUsers'])
                ],Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => true,
                'message' => 'Un souci serveur, veuillez réessayer plus tard'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
          
    }

    #[Route('/edit/{id}', name: 'user_edit', methods: ['POST','PUT'])]
    public function edit(Request $request): JsonResponse
    {
        $firstname = $request->get('firstname');
        $lastname = $request->get('lastname');

        if(!isset($firstname) || !isset($lastname)) {
            return $this->json([
                'error' => true,
                'message' => 'Une ou plusieurs données obligatoires sont manquantes',
            ],Response::HTTP_BAD_REQUEST);
        }

        $user = $this->repository->find($request->get('id'));
        if(!$user) {
            return $this->json([
                'error' => true,
                'message' => "L'utilisateur n'existe pas",
            ],Response::HTTP_NOT_FOUND);
        }
        
        try {
            $user->setFirstname($firstname);
            $user->setLastname(empty($data_received['email'])?$user->getEmail():$data_received['email']);
            $user->setSexe($request->get('sexe'));
            $user->setUpdateAt(new \DateTimeImmutable());
        
            
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json([
                    'error'=> true,
                    'message' => "Une ou plusieurs données sont erronées",
                    'data' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }


            $this->entityManager->flush();
            return $this->json([
                'error' => false,
                'message' => "L'utilisateur a bien été modifié avec succès",
                'user' =>  $this->serializer->serialize($user, 'json', ['groups' => 'getUsers'])
                ],Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'error' => true,
                'message' => 'Un souci serveur, veuillez réessayer plus tard'], Response::HTTP_INTERNAL_SERVER_ERROR);
       }
        
        
    }

    #[Route('/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        $user = $this->repository->find($request->get('id'));
        if(!$user) {
            return $this->json([
                'error' => true,
                'message' => "L'utilisateur n'existe pas",
            ],Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'User deleted successefully',
            'data' =>  $user->jsonSerialize()
            ],Response::HTTP_OK);
    }
}
