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

    #[Route('/login', name: 'app_login_post', methods: 'POST')]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $email = $request->get('email');
        $password = $request->get('password');

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
                ['error' => true, 'message' => "Le format de l'email est invalide"],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }

        $password_pattern = '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.* )(?=.*[^a-zA-Z0-9]).{8,20}$/';

        if (!preg_match($password_pattern, $password)) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Le mot de passe doit contenir au moins une majuscule, une minuscule,un chiffre, un caractère spécial et avoir 8 caractères minimum"],
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

    #[Route('/password-lost', name: 'app_password_lost', methods: 'POST')]
    public function forgetPassword(Request $request): JsonResponse
    {
        $email = $request->get('email');
        $encodedEmail = urlencode($email);

        $user = $this->repository->findOneBy(['email' => $email]);


        if (!$email) {
            return $this->json([
                'error' => true,
                'message' => "Email manquant. Veuillez fournir votre email pour la récupération du mot de passe ",
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$user) {
            return $this->json([
                'error' => true,
                'message' => "Aucun compte n'est associé à cet email.Veuillez vérifier et réessayer",
            ], Response::HTTP_NOT_FOUND);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'error' => true,
                'message' => "le format de l'email est invalide.Veuillez entrer un email valide",
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($this->cache->getItem('blocked_user_' . $encodedEmail)->isHit()) {
            return new JsonResponse(['error' => true, 'message' => "Trop de tentative sur l'email " . $email . " (5max) - Veuillez patienter(2min)"], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $attempts = $this->cache->getItem('login_attempts_' . $encodedEmail)->get();


        if ($attempts >= 3) {
            $cacheItem_block =  $this->cache->getItem('blocked_user_' . $encodedEmail);
            if (!$cacheItem_block->isHit()) {
                $cacheItem_block->set(true)->expiresAfter(300);
                $this->cache->save($cacheItem_block);
            }
            return new JsonResponse(['message' => "Trop de demandes de réinitialisation de mot de passe (3max). Veuillez attendre avant de réessayer (Dans 5min)"], Response::HTTP_TOO_MANY_REQUESTS);
        }
        if ($user) {
            $cacheItem_attempt = $this->cache->getItem('login_attempts_' . $encodedEmail);
            if (!$cacheItem_attempt->isHit()) {
                $cacheItem_attempt->set(1)->expiresAfter(300);
                $this->cache->save($cacheItem_attempt);
            } else {
                $cacheItem_attempt->set($attempts + 1)->expiresAfter(300);
                $this->cache->save($cacheItem_attempt);
            }
            return $this->json([
                'error' => true,
                'message' => "Un email de réinitialisation de mot de passe a été envoyé à votre adresse email. Veuillez suivre les instructions contenues dans l'email pour réinitialiser votre mot de passe ",
            ], Response::HTTP_OK);
        }
    }
}
