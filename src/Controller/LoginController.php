<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class LoginController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $jwtManager;
    private $serializer;
    private $cache;

    public function __construct(EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager, SerializerInterface $serializer, CacheItemPoolInterface $cache)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
        $this->jwtManager = $jwtManager;
        $this->serializer = $serializer;
        $this->cache = $cache;
    }

    #[Route('/login', name: 'app_login_get', methods: 'GET')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/LoginController.php',
        ]);
    }

    #[Route('/login', name: 'app_login_post', methods: 'POST')]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $email = $request->get('Email');
        $password = $request->get('Password');

        $encodedEmail = urlencode($email);

        if (!isset($email) || !isset($password)) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Email/password manquants"],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Email/password incorret"],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }

        if ($this->cache->getItem('blocked_user_' . $encodedEmail)->isHit()) {
            return new JsonResponse(['error' => true, 'message' => "Trop de tentative sur l'email " . $email . " (5max) - Veuillez patienter(2min)"], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $attempts = $this->cache->getItem('login_attempts_' . $encodedEmail)->get();


        if ($attempts >= 5) {
            $cacheItem_block =  $this->cache->getItem('blocked_user_' . $encodedEmail);
            if (!$cacheItem_block->isHit()) {
                $cacheItem_block->set(true)->expiresAfter(120);
                $this->cache->save($cacheItem_block);
            }
            return new JsonResponse(['message' => "Trop de tentative sur l'email " . $email . " (5max) - Veuillez patienter(2min)"], Response::HTTP_TOO_MANY_REQUESTS);
        }


        $user = $this->repository->findOneBy(['email' => $email]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            $cacheItem_attempt = $this->cache->getItem('login_attempts_' . $encodedEmail);
            if (!$cacheItem_attempt->isHit()) {
                $cacheItem_attempt->set(1)->expiresAfter(120);
                $this->cache->save($cacheItem_attempt);
            } else {
                $cacheItem_attempt->set($attempts + 1)->expiresAfter(120);
                $this->cache->save($cacheItem_attempt);
            }

            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Email/password incorret"],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }

        $this->cache->deleteItem('login_attempts_' . $encodedEmail);

        // Generate JWT token
        $token = $this->jwtManager->create($user);


        $data = $this->serializer->serialize(
            [
                'error' => false,
                'message' => "L'utilisateur a été authentifié avec succès",
                'user' => $user,
                'token' => $token
            ],
            'json',
            [
                'groups' => 'getLogin'
            ]
        );

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    //login, mot de passe et mot de passe oublié
}
