<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\GenerateId;
use Countable;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class UserController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $serializer;
    private $validator;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, ValidatorInterface $validator, UserPasswordHasherInterface $userPasswordHasherInterface)
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
            ['error' => false, 'message' => 'List of users', 'data' =>   $users],
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
            ]
        );

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/user/{id}', name: 'user_show', methods: ['GET'])]
    public function show(Request $request): JsonResponse
    {
        $user = $this->repository->find($request->get("id"));
        if ($user) {
            $data = $this->serializer->serialize(
                ['error' => false, 'message' => 'User retreive successefully', 'data' =>   $user],
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
                ]
            );

            return new JsonResponse($data, Response::HTTP_OK, [], true);
        } else
            return $this->json([
                'message' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
    }

    #[Route('/register', name: 'user_new', methods: 'POST')]
    public function new(Request $request, GenerateId $generateId): JsonResponse
    {
        $user = new User();
        $firstname = $request->get('firstname');
        $lastname = $request->get('lastname');
        $email = $request->get('email');
        $password = $request->get('password');
        $dateBirth = $request->get('dateBirth');

        $formats = 'd/m/Y';
        $date = \DateTime::createFromFormat($formats, $dateBirth);
        $today = new \DateTime();
        $birthdate = new \DateTime($dateBirth);
        $age = $today->diff($birthdate)->y;


        $password_pattern = '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.* )(?=.*[^a-zA-Z0-9]).{8,20}$/';
        $phone_pattern = '/^(?:\+33|0)[0-9]{9}$/';

        if (!$firstname || !$lastname || !$email || !$password || !$dateBirth) {
            return $this->json([
                'error' => true,
                'message' => 'Des champs obligatoires sont manquantes',
            ], Response::HTTP_BAD_REQUEST);
        }
        if (!preg_match($password_pattern, $password)) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Le mot de passe doit contenir au moins une majuscule, une minuscule,un chiffre, un caractère spécial et avoir 8 caractères minimum"],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }
        if (!preg_match($phone_pattern, $request->get('tel')) && $request->get('tel')) {
            return $this->json([
                'error' => true,
                'message' => "Le format du numéro de téléphone est invalide",
            ], Response::HTTP_BAD_REQUEST);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'error' => true,
                'message' => "le format de l'email est invalide",
            ], Response::HTTP_BAD_REQUEST);
        }
        if (($request->get('sexe') != 0 || $request->get('sexe') != 1) && $request->get('sexe')) {
            return $this->json([
                'error' => true,
                'message' => "La valeur du champ sexe est invalide, les valeurs autorisées sont 0 pour Femme, 1 pour Homme.",
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$date) {
            return $this->json([
                'error' => true,
                'message' => "le format de la date de naissance est invalide. Le format atttendu est JJ/MM/AAAA ",
            ], Response::HTTP_BAD_REQUEST);
        }
        if ($age < 12) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "L'utilisateur doit avoir au moins 12 ans"],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_NOT_ACCEPTABLE, [], true);
        }

        $search = $this->repository->findOneBy(['email' => $request->get('email')]);
        if ($search) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Cet email est déjà utilisé par un autre compte"],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_CONFLICT, [], true);
        }
        try {
            $user->setIdUser($generateId->randId());
            $user->setFirstname($firstname);
            $user->setLastname($lastname);
            $user->setEmail($email);
            $user->setTel($request->get('tel'));
            $user->setSexe($request->get('sexe'));
            $user->setDateBirth($date);
            $user->setRoles(["ROLE_USER"]);

            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $password
            );
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $data = $this->serializer->serialize(
                ['error' => false, 'message' => "L'utilisateur a bien été créé avec succès", 'user' =>   $user],
                'json',
                [
                    'groups' => 'getUsers'
                ]
            );

            return new JsonResponse($data, Response::HTTP_CREATED, [], true);
        } catch (\Exception $e) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Un souci serveur, veuillez réessayer plus tard", "erreur" => $e->getMessage()],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_INTERNAL_SERVER_ERROR, [], true);
        }
    }

    #[Route('/user', name: 'user_edit', methods: ['POST', 'PUT'])]
    public function edit(Request $request): JsonResponse
    {

        $detailUser = $this->repository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $user = $this->repository->find($detailUser->getId());

        $firstname = $request->get('firstname');
        $lastname = $request->get('lastname');
        $phone_pattern = '/^(?:\+33|0)[0-9]{9}$/';
        $word_pattern = '/^[A-Za-z]+$/';

        if ((!preg_match($word_pattern, $firstname) || (!preg_match($word_pattern, $lastname))) && ($firstname  ||  $lastname)) {
            return $this->json([
                'error' => true,
                'message' => "Les données fournies sont invalides ou incomplètes  ",
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($request->get('tel') && $request->get('tel') != $detailUser->getTel()) {

            if ($this->repository->findOneBy(['tel' => $request->get('tel')])) {
                return $this->json([
                    'error' => true,
                    'message' => "Conflit de donnée. le numéro de téléphone est déjà utilisé par un autre utilisateur",
                ], Response::HTTP_CONFLICT);
            }

            if (!preg_match($phone_pattern, $request->get('tel')) && $request->get('tel')) {
                return $this->json([
                    'error' => true,
                    'message' => "Le format du numéro de téléphone est invalide",
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        if ((strlen($firstname) < 4 || strlen($lastname) < 4) && ($firstname || $lastname)) {
            return $this->json([
                'error' => true,
                'message' => "Erreur de validation des données ",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (($request->get('sexe') != 0 || $request->get('sexe') != 1) && $request->get('sexe')) {
            return $this->json([
                'error' => true,
                'message' => "La valeur du champ sexe est invalide, les valeurs autorisées sont 0 pour Femme, 1 pour Homme.",
            ], Response::HTTP_BAD_REQUEST);
        }
        try {
            $user->setFirstname($firstname);
            $user->setLastname($lastname);
            $user->setTel($request->get('tel'));
            $user->setSexe($request->get('sexe'));
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            return $this->json([
                'error' => false,
                'message' => 'Votre inscription a bien été prise en compte',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Un souci serveur, veuillez réessayer plus tard", "erreur" => $e->getMessage()],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_INTERNAL_SERVER_ERROR, [], true);
        }
    }

    #[Route('/account-desactivation', name: 'user_delete', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        $user = $this->repository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        if(!$user->isActive()) {
            return $this->json([
                'error' => true,
                'message' => "Le compte est déjà désactivé",
            ], Response::HTTP_CONFLICT);
        }
        $user->setActive(false);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'error' => false,
            'message' => 'Votre compte a été désactivé avec succès.Nous sommes désolés de vous voir partir',
        ], Response::HTTP_OK);
    }
}
