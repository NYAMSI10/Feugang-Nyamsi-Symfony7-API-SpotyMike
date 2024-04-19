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
    private $serializer;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Album::class);
        $this->artistRepository = $entityManager->getRepository(Artist::class);
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    
    #[Route('/albums', name: 'album_list', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $current_page = $request->get('currentPage',1);
        $limit = $request->get('limit',5);

        if (!(is_numeric($current_page) && $current_page >= 0 && intval($current_page) == $current_page)) {
            return $this->json([
                'error' => true,
                'message' => 'Le paramètre de pagination invalide. Veuillez fournir un numéro de page valide.',
            ], Response::HTTP_BAD_REQUEST);
        }

       
        $albums = $this->repository->getAllAlbums($current_page,$limit);
        $nb_items = is_countable($albums) ? count($albums) : 0;

        if ($nb_items ==0) {
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

    #[Route('album/{id}', name: 'album_show', methods: ['GET'])]
    public function show(Request $request, int $id = 0): JsonResponse
    {
       
        $id = $request->get('id');

        if (!isset($id) || $id == 0) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Une ou plusieurs données obligatoires sont manquantes"],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }

        $album = $this->repository->find($id);
       
        if ($album) {
            $jsonAlbumList = $this->serializer->serialize(["error" => false, "album" => $album], 'json', ['groups' => 'getAlbums']);

            return new JsonResponse($jsonAlbumList, Response::HTTP_OK, [], true);
        } else {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Une ou plusieurs données obligatoires sont erronnées"],
                'json'
            );
            return new JsonResponse($data, Response::HTTP_CONFLICT, [], true);
        }
    }

    // #[Route('album/edit/{id}', name: 'album_edit', methods: ['POST', 'PUT'])]
    // public function edit(Request $request, Album $album): JsonResponse
    // {
    //     $data_received = $request->request->all();

    //     $album->setNom(isset($data_received['nom']) ? $data_received['nom'] : $album->getNom());
    //     $album->setCateg(isset($data_received['categ']) ? $data_received['categ'] : $album->getCateg());
    //     $album->setCover(isset($data_received['cover']) ? $data_received['cover'] : $album->getCover());
    //     $album->setYear(isset($data_received['year']) ? $data_received['year'] : date('Y'));


    //     $this->entityManager->persist($album);
    //     $this->entityManager->flush();

    //     return $this->json([
    //         'message' => 'Album modified successfully',
    //         'data' =>  $album->jsonSerialize()
    //     ], Response::HTTP_OK);
    // }

    // #[Route('album/{id}', name: 'album_delete', methods: ['DELETE'])]
    // public function delete(Request $request, Album $album): JsonResponse
    // {

    //     $this->entityManager->remove($album);
    //     $this->entityManager->flush();
    //     $data = $this->serializer->serialize($album, 'json');

    //     return $this->json([
    //         'message' => 'Album deleted successfully',
    //         'data' =>  $album->jsonSerialize()
    //     ], Response::HTTP_OK);
    // }


    public function formatData($albums) 
    {
        $response = [];
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

            $label_id = $this->entityManager->getRepository(ArtistHasLabel::class)->findLabel($album->getArtistUserIdUser()->getId(),$album->getCreatedAt());
            $label = $this->entityManager->getRepository(Label::class)->find($label_id['id']) ;
            $responseAlbum = [
                'id' => $album->getId(),
                'nom' => $album->getNom(),
                'categ' => $album->getCateg(),
                'cover' => $album->getCover(),
                'year' => $album->getYear(),
                'label' => $label->getNom(),
                'createdAt' => $album->getCreatedAt()->format('Y-m-d'),
                'artist' => $artist,
                'songs' => [],
            ];
 
            foreach ($album->getSongs() as $song) {
                $songData = [
                    'id' => $song->getIdSong(),
                    'title' => $song->getTitle(),
                    'cover' => $song->getCover(),
                    'createdAt' =>$song->getCreatedAt()->format('Y-m-d'),
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
                        'dateBirth' => $collaborator->getUserIdUser()->getDateBirth(),
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
