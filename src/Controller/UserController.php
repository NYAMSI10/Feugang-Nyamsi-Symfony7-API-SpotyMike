<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\Validator\ValidatorInterface;


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

    #[Route('/user', name: 'user_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->repository->findAll();

        $data = $this->serializer->serialize(
            ['error' => false ,'message' => 'List of users','data' =>   $users], 
            'json', 
            [
                'groups' => 'getUsers',
                AbstractNormalizer::CALLBACKS => [
                    'dateBirth' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                        return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                    },
                    'createAt' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                        return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                    },
                    'updateAt' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                        return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                    }
                ]
        ]); 

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/user/{id}', name: 'user_show', methods: ['GET'])]
    public function show(Request $request): JsonResponse
    {
        $user = $this->repository->find($request->get("id"));
        if($user)
            {
                $data = $this->serializer->serialize(
                    ['error' => false, 'message' => 'User retreive successefully','data' =>   $user], 
                    'json', 
                    [
                        'groups' => 'getUsers',
                        AbstractNormalizer::CALLBACKS => [
                            'dateBirth' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                                return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                            },
                            'createAt' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                                return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                            },
                            'updateAt' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                                return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                            }
                        ]
                ]); 

                return new JsonResponse($data, Response::HTTP_OK, [], true);
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
            $data = $this->serializer->serialize(
                ['error' => true,'message' => "Une ou plusieurs données obligatoires sont manquantes"], 
                'json'); 
            
            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }
        

        if($age < 12) {
            $data = $this->serializer->serialize(
                ['error' => true,'message' => "L'age de l'utilisateur ne permet pas(12 ans)"], 
                'json'); 
            
            return new JsonResponse($data, Response::HTTP_NOT_ACCEPTABLE, [], true);
        }

        $search = $this->repository->findBy(['email' => $request->get('email')]);
        if($search) {
            $data = $this->serializer->serialize(
                ['error' => true,'message' => "Un compte utilisant cette adresse mail est déjà enregistré"], 
                'json'); 
            
            return new JsonResponse($data, Response::HTTP_CONFLICT, [], true);
        }
        try {
            $user = new User();
            $user->setIdUser(md5(uniqid($email, true)));
            $user->setFirstname($firstname);
            $user->setLastname($lastname);
            $user->setEmail($email);
            $user->setTel($request->get('tel'));
            $user->setSexe($request->get('sexe'));
            $user->setDateBirth(new \DateTime($dateBirth));
            
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $password
            );
            $user->setPassword($hashedPassword);
        
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessage = $error->getMessage();
                    if (!in_array($errorMessage, $errorMessages)) {
                        $errorMessages[] = $errorMessage;
                    }
                }
                
                
                $data = $this->serializer->serialize(
                    ['error' => true,'message' => "Une ou plusieurs donnees sont erronees",'data' => $errorMessages], 
                    'json'); 
                
                return new JsonResponse($data, Response::HTTP_CONFLICT, [], true);
            }
        
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $data = $this->serializer->serialize(
                ['error' => false, 'message' => "L'utilisateur a bien été créé avec succès",'user' =>   $user], 
                'json', 
                [
                    'groups' => 'getUsers',
                    AbstractNormalizer::CALLBACKS => [
                        'dateBirth' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                            return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                        },
                        'createAt' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                            return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                        },
                        'updateAt' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                            return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                        }
                    ]
            ]); 
            
            return new JsonResponse($data, Response::HTTP_CREATED, [], true);

        } catch (\Exception $e) {
                $data = $this->serializer->serialize(
                    ['error' => true,'message' => "Un souci serveur, veuillez réessayer plus tard","erreur" => $e->getMessage()], 
                    'json'); 
                
                return new JsonResponse($data, Response::HTTP_INTERNAL_SERVER_ERROR, [], true);
        }
          
    }

    #[Route('/user/{id}', name: 'user_edit', methods: ['POST','PUT'])]
    public function edit(Request $request): JsonResponse
    {
        $firstname = $request->get('firstname');
        $lastname = $request->get('lastname');

        if(!isset($firstname) || !isset($lastname)) {
            $data = $this->serializer->serialize(
                ['error' => true,'message' => "Une ou plusieurs données obligatoires sont manquantes"], 
                'json'); 
            
            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }

        $user = $this->repository->find($request->get('id'));
        if(!$user) {
            $data = $this->serializer->serialize(
                ['error' => true,'message' => "L'utilisateur n'existe pas"], 
                'json'); 
            
            return new JsonResponse($data, Response::HTTP_NOT_FOUND, [], true);
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
                
                $data = $this->serializer->serialize(
                    ['error' => true,'message' => "Une ou plusieurs données sont erronées",'data' => $errorMessages], 
                    'json'); 
                
                return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
            }


            $this->entityManager->flush();
            

            $data = $this->serializer->serialize(
                [
                    'error' => true,
                    'message' => "L'utilisateur a bien été modifié avec succès",
                    'user' => $user
                ], 
                'json',[
                    'groups' => 'getUsers',
                    AbstractNormalizer::CALLBACKS => [
                        'dateBirth' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                            return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                        },
                        'createAt' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                            return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                        },
                        'updateAt' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                            return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                        }
                    ]
            ]); 
            
            return new JsonResponse($data, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            $data = $this->serializer->serialize(
                ['error' => true,'message' => "Un souci serveur, veuillez réessayer plus tard"], 
                'json'); 
            
            return new JsonResponse($data, Response::HTTP_INTERNAL_SERVER_ERROR, [], true);
       }
        
        
    }

    #[Route('/user/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
            $user = $this->repository->find($request->get('id'));
            if(!$user) {

                $data = $this->serializer->serialize(
                    ['error' => true,'message' => "L'utilisateur n'existe pas"], 
                    'json'); 
                
                return new JsonResponse($data, Response::HTTP_NOT_FOUND, [], true);
            }

            $this->entityManager->remove($user);
            $this->entityManager->flush();

            $data = $this->serializer->serialize(
                ['error' => false, 'message' => 'User deleted successefully','data' =>   $user], 
                'json', 
                [
                    'groups' => 'getUsers',
                    AbstractNormalizer::CALLBACKS => [
                        'dateBirth' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                            return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                        },
                        'createAt' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                            return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                        },
                        'updateAt' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                            return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                        }
                    ]
            ]); 
            
            return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
