<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Album;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use App\Repository\UserRepository;
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

#[Route('/api/album')]
class AlbumController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $serializer;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager,SerializerInterface $serializer,ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Album::class);
        $this->serializer = $serializer;
        $this->validator = $validator;

    }

    #[Route('/', name: 'album_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $albums = $this->repository->findAll();

        $data = $this->serializer->serialize($albums, 'json', [
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['id'],
        ]);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
        return $this->json([
            'message' => 'List of albums',
            'data' =>   $data
            ],Response::HTTP_OK);

    }

    #[Route('/new', name: 'album_new', methods: 'POST')]
    public function new(Request $request): JsonResponse
    {
        $data_received = $request->request->all();

        if(!isset($data_received['artist_User_idUser'])) {
            return $this->json([
                'message' => 'Please choose an artist',
            ]);
        }


        $album = new Album();
        $user = $this->entityManager->getRepository(User::class)->find($data_received['artist_User_idUser']);
        $artist = $this->entityManager->getRepository(Artist::class)->findBy(['User_idUser' => $user]);

        if($artist) {
            $album->setArtistUserIdUser($artist[0]);
            $album->setIdAlbum(md5(uniqid($data_received['nom'].'-'.$data_received['categ'], true)));
            $album->setNom($data_received['nom']);
            $album->setCateg($data_received['categ']);
            $album->setCover($data_received['cover']);
            $album->setYear($data_received['year']);

            $errors = $this->validator->validate($album);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }
            
            $this->entityManager->persist($album);
            $this->entityManager->flush();
        } else {
            return $this->json([
                'message' => 'Please choose a valid artist',
            ]);
        }
        
        return $this->json([
            'message' => 'Album created successfully',
            'data' =>  $album->jsonSerialize()
            ],Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'album_show', methods: ['GET'])]
    public function show(Request $request,Album $album): JsonResponse
    {
        return $this->json([
            'message' => 'Album retreive successfully',
            'data' =>  $album->jsonSerialize()
            ],Response::HTTP_OK);
    }

    #[Route('/edit/{id}', name: 'album_edit', methods: ['POST','PUT'])]
    public function edit(Request $request,Album $album): JsonResponse
    {
        $data_received = $request->request->all();

        $album->setNom(isset($data_received['nom'])?$data_received['nom']:$album->getNom());
        $album->setCateg(isset($data_received['categ'])?$data_received['categ']:$album->getCateg());
        $album->setCover(isset($data_received['cover'])?$data_received['cover']:$album->getCover());
        $album->setYear(isset($data_received['year'])?$data_received['year']:date('Y'));
        

        $this->entityManager->persist($album);
        $this->entityManager->flush();
        
        return $this->json([
            'message' => 'Album modified successfully',
            'data' =>  $album->jsonSerialize()
            ],Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'album_delete', methods: ['DELETE'])]
    public function delete(Request $request, Album $album): JsonResponse
    {
        
        $this->entityManager->remove($album);
        $this->entityManager->flush();
        $data = $this->serializer->serialize($album, 'json');

        return $this->json([
            'message' => 'Album deleted successfully',
            'data' =>  $album->jsonSerialize()
            ],Response::HTTP_OK);
    }
}
