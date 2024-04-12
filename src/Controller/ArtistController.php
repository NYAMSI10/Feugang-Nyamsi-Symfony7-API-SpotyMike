<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\ArtistHasLabel;
use App\Entity\Label;
use App\Entity\Song;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class ArtistController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $serializer;
    private $validator;
    private $jwtManager;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, ValidatorInterface $validator, JWTTokenManagerInterface $jwtManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Artist::class);
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->jwtManager = $jwtManager;
    }


    #[Route('/artist', name: 'artist_list', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $page = 1;
        $limit = 2;

        $check_visibility = 0;
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        if (!in_array('ROLE_ARTIST', $user->getRoles(), true))
            $check_visibility = 1;


        $current_page = $request->get('currentPage');

        if (isset($current_page)) {
            if (!is_numeric($current_page) || $current_page <= 0) {
                $data = $this->serializer->serialize([
                    'error' => true,
                    'message' => "Le paramètre de pagination est invalide. Veuillez fournir un numéro de page valide"
                ], 'json');
                return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
            }
        }


        $artistes_data = $this->repository->findAllWithPagination($check_visibility, ($current_page) ? intval($current_page) : $page, $limit);
        if ($artistes_data['artists']) {
            $data = $this->serializer->serialize([
                'error' => false,
                'artists' => $artistes_data['artists'],
                'pagination' => $artistes_data['pagination'],
                'message' => "Informations des artistes récupérées avec succès."
            ], 'json');
            return new JsonResponse($data, Response::HTTP_OK, [], true);
        } else {
            $data = $this->serializer->serialize([
                'error' => true,
                'message' => "Aucun artiste trouvé pour la page demandée"
            ], 'json');
            return new JsonResponse($data, Response::HTTP_NOT_FOUND, [], true);
        }
    }

    #[Route('/artist/{fullname}', name: 'artist_show',  methods: ['GET'])]
    public function show(Request $request, string $fullname = 'none'): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        if ($fullname == 'none') {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Le nom d'artiste est obligatoire pour cette requete"],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }
        if (!preg_match('/[^A-Za-z0-9]/', $fullname) || !is_string($fullname)) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Le format du nom d'artiste fourni est invalide"],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }

        $check_visibility = true;
        if (in_array('ROLE_ARTIST', $user->getRoles(), true)) {
            $check_visibility = false;
        }

        $artiste_data = $this->repository->findByArtistAndAlbumAndSong($fullname, $check_visibility);
        if ($artiste_data) {
            $data = $this->serializer->serialize(['error' => false, 'artist' =>   $artiste_data], 'json');
            return new JsonResponse($data, Response::HTTP_OK, [], true);
        } else {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Aucun artiste trouvé correspondant au nom fourni"],
                'json'
            );
            return new JsonResponse($data, Response::HTTP_NOT_FOUND, [], true);
        }
    }

    #[Route('/artist', name: 'artist_new_or_edit',  methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $fullname = $request->get('fullname');
        $id_label = $request->get('label');
        $description = $request->get('description');

        if (!in_array('ROLE_ARTIST', $user->getRoles(), true)) {


            if ((!$fullname) || (!$id_label)) {
                $data = $this->serializer->serialize(
                    ['error' => true, 'message' => "L'id du label et le fullname sont obligatoires"],
                    'json'
                );

                return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
            }
            if (!filter_var($id_label, FILTER_VALIDATE_INT)) {
                $data = $this->serializer->serialize(
                    ['error' => true, 'message' => "Le format de l'id du label est invalide"],
                    'json'
                );

                return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
            }



            $today = new \DateTime();
            $age = $today->diff($user->getDateBirth())->y;

            if ($age < 16) {
                $data = $this->serializer->serialize(
                    ['error' => true, 'message' => "Vous devez avoir au moins 16 ans pour etre artiste"],
                    'json'
                );

                return new JsonResponse($data, Response::HTTP_NOT_ACCEPTABLE, [], true);
            }

            $searchArtist = $this->repository->findOneBy(['fullname' => $fullname]);
            if ($searchArtist) {
                $data = $this->serializer->serialize(
                    ['error' => true, 'message' => "Ce nom d'artiste est déjà pris. Veuillez en choisir un autre"],
                    'json'
                );

                return new JsonResponse($data, Response::HTTP_CONFLICT, [], true);
            }

            try {
                $user->setRoles(["ROLE_ARTIST", "ROLE_USER"]);
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $artist = new Artist();

                $artist->setUserIdUser($user);
                $artist->setFullname($fullname);
                // $artist->setLabel($label);
                $artist->setDescription(isset($description) ? $description : '');
                $errors = $this->validator->validate($artist);
                if (count($errors) > 0) {
                    $errorMessages = [];
                    foreach ($errors as $error) {
                        $errorMessages[] = $error->getMessage();
                    }
                    $data = $this->serializer->serialize(
                        ['error' => true, 'message' => "Une ou plusieurs donnees sont erronees", 'data' => $errorMessages],
                        'json'
                    );

                    return new JsonResponse($data, Response::HTTP_CONFLICT, [], true);
                }

                $this->entityManager->persist($artist);
                $this->entityManager->flush();

                $label = $this->entityManager->getRepository(Label::class)->find($id_label);
                if (!$label) {
                    $data = $this->serializer->serialize(
                        ['error' => true, 'message' => "L'id du label est invalide"],
                        'json'
                    );

                    return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
                }

                $artistHasLabel = new ArtistHasLabel();
                $artistHasLabel->setIdArtist($artist)
                    ->setIdLabel($label)
                    ->setEntrydate(new \DateTimeImmutable());

                $this->entityManager->persist($artistHasLabel);
                $this->entityManager->flush();


                $data = $this->serializer->serialize(
                    ['error' => false, 'message' => "Votre inscription a bien été pris en compte"],
                    'json'
                );

                return new JsonResponse($data, Response::HTTP_CREATED, [], true);
            } catch (\Exception $e) {
                return $this->json(['errors' => $e->getMessage(), 'message' => 'Une erreur est survenue'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            $id = $request->get('id');
            $artist = $user->getArtist();
            if (isset($id)) {
                $artist = $this->repository->find($id);
                if (!$artist) {
                    $data = $this->serializer->serialize(
                        ['error' => true, 'message' => "Artiste non trouvé. Veuillez vérifier les informations fournies."],
                        'json'
                    );
                    return new JsonResponse($data, Response::HTTP_NOT_FOUND, [], true);
                }
                if ($user->getArtist() != $artist) {
                    $data = $this->serializer->serialize(
                        ['error' => true, 'message' => "Vous n'étes pas autorisé à accèder aux informations de cet artiste"],
                        'json'
                    );
                    return new JsonResponse($data, Response::HTTP_FORBIDDEN, [], true);
                }
            }
            if ($fullname && $fullname != $artist->getFullname()) {
                $searchArtist = $this->repository->findOneBy(['fullname' => $fullname]);
                if ($searchArtist) {
                    $data = $this->serializer->serialize(
                        ['error' => true, 'message' => "Le nom d'artiste est déjà utilisé.Veuillez choisir un autre nom"],
                        'json'
                    );

                    return new JsonResponse($data, Response::HTTP_CONFLICT, [], true);
                }
            }

            $label = $this->entityManager->getRepository(Label::class)->find($id_label);


            $artist->setFullname(isset($fullname) ? $fullname : $artist->getFullname());
            $artist->setDescription(isset($description) ? $description : $artist->getDescription());

            $errors = $this->validator->validate($artist);
            if (count($errors) > 0 || !$label) {
                $data = $this->serializer->serialize(
                    ['error' => true, 'message' => "Les parametres fournis sont invalides.Veuillez vérifier les données soumises"],
                    'json'
                );

                return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
            }

            $this->entityManager->persist($artist);

            $lastlabel = $this->entityManager->getRepository(ArtistHasLabel::class)->findOneBy(['idArtist' => $artist], ['id' => 'DESC']);
            if ($lastlabel && ($lastlabel->getIssuedate() == null)) {
                $lastlabel->setIssuedate(new \DateTimeImmutable());
                $this->entityManager->persist($lastlabel);
            }
            $artistHasLabel = new ArtistHasLabel();
            $artistHasLabel->setIdArtist($artist)
                ->setIdLabel($label)
                ->setEntrydate(new \DateTimeImmutable());

            $this->entityManager->persist($artistHasLabel);
            $this->entityManager->flush();

            $data = $this->serializer->serialize(
                ['error' => false, 'message' => "Les informations de l'artiste ont été mises à jour avec succès"],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_OK, [], true);
        }
    }

    #[Route('/artist', name: 'artist_delete', methods: ['DELETE'])]
    public function delete(): JsonResponse
    {
        $artist = $this->repository->findOneBy(['User_idUser' => $this->getUser()->getId()]);

        if (!$artist) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Compte artiste non trouvé. Vérifiez les informations fournies et réessayez"],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_NOT_FOUND, [], true);
        }
        if (!$artist->isActive()) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Ce compte artiste est déjà désactivé "],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_GONE, [], true);
        }
        $artist->setActive(false);
        $this->entityManager->persist($artist);
        $this->entityManager->flush();
        $data = $this->serializer->serialize(
            ['error' => false, 'message' => "Le compte artiste a été  désactivé avec succès"],
            'json'
        );

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
