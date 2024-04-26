<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Album;
use App\Entity\ArtistHasLabel;
use App\Entity\Label;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use App\Repository\UserRepository;
use App\Service\GenerateId;
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

class AlbumController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $artistRepository;
    private $labelRepository;
    private $labelHasArtistRepository;
    private $serializer;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Album::class);
        $this->artistRepository = $entityManager->getRepository(Artist::class);
        $this->labelRepository = $entityManager->getRepository(Label::class);
        $this->labelHasArtistRepository = $entityManager->getRepository(ArtistHasLabel::class);
        $this->serializer = $serializer;
        $this->validator = $validator;
    }


    #[Route('/albums', name: 'album_list', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $current_page = $request->get('currentPage', 1);
        $limit = $request->get('limit', 5);
        $check_visibility = 0;
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        if (!(is_numeric($current_page) && $current_page >= 0 && intval($current_page) == $current_page)) {
            return $this->json([
                'error' => true,
                'message' => 'Le paramètre de pagination invalide. Veuillez fournir un numéro de page valide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array('ROLE_ARTIST', $user->getRoles(), true))
            $check_visibility = 1;

        $albums = $this->repository->getAllAlbums($current_page, $limit, $check_visibility);
        $nb_items = is_countable($albums) ? count($albums) : 0;

        if ($nb_items == 0) {
            return $this->json([
                'error' => true,
                'message' => 'Aucun album trouvé pour la page demandée.',
            ], Response::HTTP_NOT_FOUND);
        }

        $albums_data = $this->formatData($albums);
        return $this->json([
            'error' => false,
            'albums' => $albums_data,
            'pagination' => [
                'current_page' => $current_page,
                'totalAlbums' => $nb_items,
                'totalPages' => ceil($nb_items / $limit)
            ]
        ], Response::HTTP_OK);
    }


    #[Route('/album', name: 'album_new', methods: 'POST')]
    public function new(Request $request, GenerateId $generateId): JsonResponse
    {
        $existAlbum = $this->repository->findOneBy(["nom" => $request->get('nom')]);

        $artist = $this->artistRepository->findOneBy(["User_idUser" => $this->getUser()]);
        if ($existAlbum) {
            return $this->json([
                'error' => true,
                'message' => 'Ce nom existe déjà',
            ], Response::HTTP_CONFLICT);
        }

        $album = new Album();
        $album->setIdAlbum($generateId->randId())
            ->setNom($request->get('nom'))
            ->setCateg($request->get('categ'))
            ->setYear($request->get('year'))
            ->setArtistUserIdUser($artist);

        $errors = $this->validator->validate($album);
        if (count($errors) > 0) {

            return $this->json([
                'error' => true,
                'message' => 'Une ou plusieurs données obligatoires sont manquantes',
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($album);
        $this->entityManager->flush();
        return $this->json([
            'error' => false,
            'message' => 'Album ajouté avec succès',
        ], Response::HTTP_OK);
    }

    // #[Route('album/{id}', name: 'album_show', methods: ['GET'])]
    // public function show(Request $request, int $id = 0): JsonResponse
    // {
    //     $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);


    //     $id = $request->get('id');

    //     if (!isset($id) || $id == 0) {
    //         $data = $this->serializer->serialize(
    //             ['error' => true, 'message' => "L'id de l'album est obligatoire pour cette requête"],
    //             'json'
    //         );

    //         return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
    //     }

    //     $album_data = null;
    //     if (!in_array('ROLE_ARTIST', $user->getRoles(), true))
    //         $album_data = $this->repository->findOneBy(['idAlbum' => $id, 'visibility' => true, 'active' => true]);
    //     else
    //         $album_data = $this->repository->findOneBy(['idAlbum' => $id, 'active' => true]);


    //     if (!$album_data) {
    //         $data = $this->serializer->serialize(
    //             ['error' => true, 'message' => "L'album non trouvé.Vérifiez les informations fournies et réessayez."],
    //             'json'
    //         );
    //         return new JsonResponse($data, Response::HTTP_NOT_FOUND, [], true);
    //     }

    //     $artist = [
    //         'firstname' => $album_data->getArtistUserIdUser()->getUserIdUser()->getFirstname(),
    //         'lastname' => $album_data->getArtistUserIdUser()->getUserIdUser()->getLastname(),
    //         'fullname' => $album_data->getArtistUserIdUser()->getFullname(),
    //         //'avatar' => $collaborator->getFullname(),
    //         'followers' => count($album_data->getArtistUserIdUser()->getUserIdUser()->getFollowers()),
    //         'sexe' =>  $album_data->getArtistUserIdUser()->getUserIdUser()->getSexe(),
    //         'dateBirth' => $album_data->getArtistUserIdUser()->getUserIdUser()->getDateBirth()->format('d-m-Y'),
    //         'Artist.createdAt' => $album_data->getArtistUserIdUser()->getCreatedAt()->format('Y-m-d')
    //     ];

    //     $label_id = $this->entityManager->getRepository(ArtistHasLabel::class)->findLabel($album_data->getArtistUserIdUser()->getId(), $album_data->getCreatedAt());
    //     if ($label_id['id'])
    //         $label = $this->entityManager->getRepository(Label::class)->find($label_id['id']);
    //     else
    //         $label = "";

    //     $responseAlbum = [
    //         'id' => $album_data->getId(),
    //         'nom' => $album_data->getNom(),
    //         'categ' => $album_data->getCateg(),
    //         'cover' => $album_data->getCover(),
    //         'year' => $album_data->getYear(),
    //         'label' => $label->getNom(),
    //         'createdAt' => $album_data->getCreatedAt()->format('Y-m-d'),
    //         'artist' => $artist,
    //         'songs' => [],
    //     ];

    //     foreach ($album_data->getSongs() as $song) {
    //         $songData = [
    //             'id' => $song->getIdSong(),
    //             'title' => $song->getTitle(),
    //             'cover' => $song->getCover(),
    //             'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
    //             'featuring' => []
    //         ];

    //         // Ajoutez les artistes en collaboration pour chaque chanson
    //         foreach ($song->getArtistIdUser() as $collaborator) {
    //             $songData['featuring'][] = [
    //                 'firstname' => $collaborator->getUserIdUser()->getFirstname(),
    //                 'lastname' => $collaborator->getUserIdUser()->getLastname(),
    //                 'fullname' => $collaborator->getFullname(),
    //                 //'avatar' => $collaborator->getFullname(),
    //                 'sexe' =>  $collaborator->getUserIdUser()->getSexe(),
    //                 'dateBirth' => $collaborator->getUserIdUser()->getDateBirth()->format('d-m-Y'),
    //                 'Artist.createdAt' => $collaborator->getCreatedAt()->format('Y-m-d')
    //             ];
    //         }

    //         $responseAlbum['songs'][] = $songData;
    //     }



    //     $jsonAlbumList = $this->serializer->serialize(["error" => false, "album" => $responseAlbum], 'json');

    //     return new JsonResponse($jsonAlbumList, Response::HTTP_OK, [], true);
    // }

    #[Route('album', name: 'album_search', methods: ['GET'])]
    public function albumSearch(Request $request, int $id = 0): JsonResponse
    {

        $current_page = $request->get('currentPage', 1);
        $limit = $request->get('limit', 5);
        $nom = $request->get('nom');
        $label = $request->get('label');
        $year = $request->get('year');
        $featuring = $request->get('featuring');
        $category = $request->get('category');
        if ($request->get('fullname')) {
            $fullname = $this->artistRepository->findOneBy(['fullname' => $request->get('fullname')])->getId();
        } else {
            $fullname = null;
        }
        $categoryList = ["rap", "r'n'b", "gospel", "soul", "country", "hip hop", "jazz", "rap", "le Mike"];
        $check_visibility = 0;
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        if (!(is_numeric($current_page) && $current_page >= 0 && intval($current_page) == $current_page)) {
            return $this->json([
                'error' => true,
                'message' => 'Le paramètre de pagination invalide. Veuillez fournir un numéro de page valide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array('ROLE_ARTIST', $user->getRoles(), true)) {
            $check_visibility = 1;
        }

        if (!in_array($category, $categoryList)) {
            return $this->json([
                'error' => true,
                'message' => 'Les categorie ciblée sont invalide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $albums = $this->repository->searchAlbum($nom, $fullname, $label, $year, $featuring, $category, $current_page, $limit, $check_visibility);
        $nb_items = is_countable($albums) ? count($albums) : 0;

        if ($nb_items == 0) {
            return $this->json([
                'error' => true,
                'message' => 'Aucun album trouvé pour la page demandée.',
            ], Response::HTTP_NOT_FOUND);
        }

        $albums_data = $this->formatData($albums);
        return $this->json([
            'error' => false,
            'albums' => $albums_data,
            'pagination' => [
                'current_page' => $current_page,
                'totalAlbums' => $nb_items,
                'totalPages' => ceil($nb_items / $limit)
            ]
        ], Response::HTTP_OK);
    }



    public function formatData($albums)
    {
        $response = [];
        //dd($albums);
        foreach ($albums as $album) {
            $artist = [
                'firstname' => $album->getArtistUserIdUser()->getUserIdUser()->getFirstname(),
                'lastname' => $album->getArtistUserIdUser()->getUserIdUser()->getLastname(),
                'fullname' => $album->getArtistUserIdUser()->getFullname(),
                //'avatar' => $collaborator->getFullname(),
                'followers' => count($album->getArtistUserIdUser()->getUserIdUser()->getFollowers()),
                'sexe' =>  $album->getArtistUserIdUser()->getUserIdUser()->getSexe(),
                'dateBirth' => $album->getArtistUserIdUser()->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                'Artist.createdAt' => $album->getArtistUserIdUser()->getCreatedAt()->format('Y-m-d')

            ];

            $label_id = $this->entityManager->getRepository(ArtistHasLabel::class)->findLabel($album->getArtistUserIdUser()->getId(), $album->getCreatedAt());
            //$label = $this->entityManager->getRepository(Label::class)->find($label_id['id']);

            $responseAlbum = [
                'id' => $album->getIdAlbum(),
                'nom' => $album->getNom(),
                'categ' => $album->getCateg(),
                'cover' => $album->getCover(),
                'year' => $album->getYear(),
                'label' => "ok",
                'createdAt' => $album->getCreatedAt()->format('Y-m-d'),
                'artist' => $artist,
                'songs' => [],
            ];

            foreach ($album->getSongs() as $song) {
                $songData = [
                    'id' => $song->getIdSong(),
                    'title' => $song->getTitle(),
                    'cover' => $song->getCover(),
                    'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
                    'featuring' => []
                ];

                // Ajoutez les artistes en collaboration pour chaque chanson
                foreach ($song->getArtistIdUser() as $collaborator) {
                    $songData['featuring'][] = [
                        'firstname' => $collaborator->getUserIdUser()->getFirstname(),
                        'lastname' => $collaborator->getUserIdUser()->getLastname(),
                        'fullname' => $collaborator->getFullname(),
                        //'avatar' => $collaborator->getFullname(),
                        'sexe' =>  $collaborator->getUserIdUser()->getSexe(),
                        'dateBirth' => $collaborator->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                        'Artist.createdAt' => $collaborator->getCreatedAt()->format('Y-m-d')
                    ];
                }

                $responseAlbum['songs'][] = $songData;
            }

            $response[] = $responseAlbum;
        }


        return $response;
    }
}
