<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Namshi\JOSE\JWT;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;

class LoginController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $jwtManager;
    private $serializer;
    private $cache;
    private $passwordHasher;
    private $jwtProvider;


    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasherInterface, JWTTokenManagerInterface $jwtManager, SerializerInterface $serializer, CacheItemPoolInterface $cache, JWSProviderInterface $jwtProvider)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
        $this->jwtManager = $jwtManager;
        $this->serializer = $serializer;
        $this->cache = $cache;
        $this->passwordHasher = $userPasswordHasherInterface;
        $this->jwtProvider = $jwtProvider;
    }

    #[Route('/login', name: 'app_login_post', methods: 'POST')]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $email = $request->get('email');
        $password = $request->get('password');
        $email_pattern = '/^[a-zA-Z0-9._%+\-—]+@[a-zA-Z0-9.\-—]+\.[a-zA-Z]{2,}$/';

        $encodedEmail = urlencode($email);

        if (!$email || !$password) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Email/password manquants."],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }

        if (!preg_match($email_pattern, $email)) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Le format de l'email est invalide."],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }

        $password_pattern = '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.* )(?=.*[^a-zA-Z0-9]).{8,20}$/';

        if (!preg_match($password_pattern, $password)) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et avoir 8 caractères minimum."],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }


        if ($this->cache->getItem('blocked_user_' . $encodedEmail)->isHit()) {
            return new JsonResponse(['error' => true, 'message' => "Trop de tentatives de connexion (5max) - Veuillez réessayer ultérieurement -2min d'attente"], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $attempts = $this->cache->getItem('login_attempts_' . $encodedEmail)->get();


        if ($attempts >= 5) {
            $cacheItem_block =  $this->cache->getItem('blocked_user_' . $encodedEmail);
            if (!$cacheItem_block->isHit()) {
                $cacheItem_block->set(true)->expiresAfter(120);
                $this->cache->save($cacheItem_block);
            }
            return new JsonResponse([
                'error' => true,
                'message' => "Trop de tentatives de connexion (5 max). Veuillez réessayer ultérieurement - 2 min d'attente.Trop de tentatives de connexion (5 max). Veuillez réessayer ultérieurement - 2 min d'attente.Trop de tentatives de connexion (5 max). Veuillez réessayer ultérieurement - 2 min d'attente.Trop de tentatives de connexion (5 max). Veuillez réessayer ultérieurement - 2 min d'attente."
            ], Response::HTTP_TOO_MANY_REQUESTS);
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

        if (!$user->isActive()) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Le compte n'est plus actif ou est suspendu."],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_FORBIDDEN, [], true);
        }

        // Generate JWT token
        $token = $this->jwtManager->create($user);

        $check_visibility = true;
        if (in_array('ROLE_ARTIST', $user->getRoles(), true)) {
            $check_visibility = false;
        }
        $artist = (object)[];
        if ($user->getArtist()) {
            $fullname = $user->getArtist()->getFullname();
            $artist = $this->entityManager->getRepository(Artist::class)->searchArtistAndAlbumAndSong($fullname, $check_visibility);
        }
        $user_data = [
            "firstname" => $user->getFirstname(),
            "email" => $user->getEmail(),
            "tel" => $user->getTel(),
            "artist" => $artist,
            "lastname" => $user->getLastname(),
            "dateBirth" => $user->getDateBirth()->format('d-m-Y'),
            "sexe" => $user->getSexe(),
            "createdAt" => $user->getCreatedAt()->format('Y-m-d')
        ];



        $data = $this->serializer->serialize(
            [
                'error' => false,
                'message' => "L'utilisateur a été authentifié avec succès",
                'user' => $user_data,
                'token' => $token
            ],
            'json'
        );

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/password-lost', name: 'app_password_lost', methods: 'POST')]
    public function forgetPassword(Request $request): JsonResponse
    {
        $email = $request->get('email');
        $encodedEmail = urlencode($email);
        $email_pattern = '/^[a-zA-Z0-9._%+\-—]+@[a-zA-Z0-9.\-—]+\.[a-zA-Z]{2,}$/';


        if (!preg_match($email_pattern, $email)) {
            return $this->json([
                'error' => true,
                'message' => "le format de l'email est invalide.Veuillez entrer un email valide",
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$email) {
            return $this->json([
                'error' => true,
                'message' => "Email manquant. Veuillez fournir votre email pour la récupération du mot de passe ",
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->repository->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json([
                'error' => true,
                'message' => "Aucun compte n'est associé à cet email.Veuillez vérifier et réessayer",
            ], Response::HTTP_NOT_FOUND);
        }
        /* if ($this->cache->getItem('blocked_user_' . $encodedEmail)->isHit()) {
            return new JsonResponse(['error' => true, 'message' => "Trop de tentative sur l'email " . $email . " (5max) - Veuillez patienter(2min)"], Response::HTTP_TOO_MANY_REQUESTS);
        }*/

        $attempts = $this->cache->getItem('login_attempts_' . $encodedEmail)->get();


        if ($attempts >= 3) {
            $cacheItem_block =  $this->cache->getItem('blocked_user_' . $encodedEmail);
            if (!$cacheItem_block->isHit()) {
                $cacheItem_block->set(true)->expiresAfter(300);
                $this->cache->save($cacheItem_block);
            }
            return new JsonResponse(['error' => true, 'message' => "Trop de demandes de réinitialisation de mot de passe ( 3 max ). Veuillez attendre avant de réessayer ( Dans 5 min)."], Response::HTTP_TOO_MANY_REQUESTS);
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
            // Generate JWT token
            $token = $this->jwtManager->create($user);

            $tokenCache = $this->cache->getItem('token')->set($token)->expiresAfter(120);
            $emailCache = $this->cache->getItem('email')->set($email)->expiresAfter(120);

            $this->cache->save($tokenCache);
            $this->cache->save($emailCache);

            return $this->json([
                'success' => true,
                'message' => "Un email de réinitialisation de mot de passe a été envoyé à votre adresse email. Veuillez suivre les instructions contenues dans l'email pour réinitialiser votre mot de passe.",
                'token' => $token
            ], Response::HTTP_OK);
        }
    }

    #[Route('/reset-password/{token?}', name: 'app_reset_password', methods: 'POST')]
    public function resetPassword(Request $request, String $token = null): JsonResponse
    {

        $password = $request->get('password');
        $password_pattern = '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.* )(?=.*[^a-zA-Z0-9]).{8,20}$/';
        $dataToken = $this->jwtProvider->load($token);
        if (!$token || !$dataToken->isVerified($token)) {
            return $this->json([
                'error' => true,
                'message' => "Token de réinitialisation manquant ou invalide. Veuillez utiliser le lien fourni dans l'email de réinitialisation de mot de passe."
            ], Response::HTTP_BAD_REQUEST);
        }
        if ($token) {
            if (($this->cache->getItem('token')->isHit()) && ($token == $this->cache->getItem('token')->get())) {
                $user = $this->repository->findOneBy(['email' => $this->cache->getItem('email')->get()]);

                if (!$password) {
                    return $this->json([
                        'error' => true,
                        'message' => "Veuillez fournir un nouveau mot de passe."
                    ], Response::HTTP_BAD_REQUEST);
                }
                if (!preg_match($password_pattern, $password)) {
                    return $this->json([
                        'error' => true,
                        'message' => "Le nouveau mot de passe ne respecte pas les critères requis. Il doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et être composé d'au moins 8 caractères."
                    ], Response::HTTP_BAD_REQUEST);
                }

                $hashedPassword = $this->passwordHasher->hashPassword(
                    $user,
                    $password
                );
                $user->setPassword($hashedPassword);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                return $this->json([
                    'success' => true,
                    'message' => "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe."
                ], Response::HTTP_OK);
            } else {
                return $this->json([
                    'error' => true,
                    'message' => "Votre token de réinitialisation de mot de passe a expiré. Veuillez refaire une demande de réinitialisation de mot de passe."
                ], Response::HTTP_GONE);
            }
        }
    }
}
