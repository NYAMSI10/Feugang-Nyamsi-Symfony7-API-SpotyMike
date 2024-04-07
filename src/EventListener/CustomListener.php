<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;

class CustomListener
{
    public function onJWTNotFound(JWTNotFoundEvent $event)
    {
        $response = new JsonResponse([
            'error' => true,
            'message' => "Authentification requise. Vous devez etre connecté pour effectuer cette action",
        ], JsonResponse::HTTP_UNAUTHORIZED);

        $event->setResponse($response);
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        $response = new JsonResponse([
            'error' => true,
            'message' => "Authentification requise. Vous devez etre connecté pour effectuer cette action",
        ], JsonResponse::HTTP_UNAUTHORIZED);

        $event->setResponse($response);
    }

    public function onJWTExpired(JWTExpiredEvent $event)
    {

        //$response = $event->getResponse();

        //$response->setMessage('Your token is expired, please renew it.');
        $response = new JsonResponse([
            'error' => true,
            'message' => "Authentification requise. Vous devez etre connecté pour effectuer cette action",
        ], JsonResponse::HTTP_UNAUTHORIZED);

        $event->setResponse($response);
    }
}
