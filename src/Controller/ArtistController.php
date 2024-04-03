<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Song;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Serializer;

class ArtistController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $serializer;
    private $validator;
    private $userService;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Artist::class);
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /*
    #[Route('/artist', name: 'artist_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $artistes = $this->repository->findAll();

        $data = $this->serializer->serialize($artistes, 'json', [
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['id'],
        ]);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
        return $this->json([
            'message' => 'List of artists',
            'data' =>   $data
            ],Response::HTTP_OK);

    }*/

    #[Route('/artist/{fullname}', name: 'artist_new',  methods: ['POST', 'GET'])]
    public function new(Request $request, string $fullname = 'none'): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        if ($request->getMethod() == 'POST') {
            $fullname = $request->request->get('fullname');
            $label = $request->get('label');
            $description = $request->get('description');

            if (!isset($fullname) || !isset($label)) {
                $data = $this->serializer->serialize(
                    ['error' => true, 'message' => "Une ou plusieurs données obligatoires sont manquantes"],
                    'json'
                );

                return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
            }

            if (in_array('ROLE_ARTIST', $user->getRoles(), true)) {
                $data = $this->serializer->serialize(
                    ['error' => true, 'message' => "Un compte utilisateur est déjà un compte artiste"],
                    'json'
                );
                return new JsonResponse($data, Response::HTTP_CONFLICT, [], true);
            }

            $today = new \DateTime();
            $age = $today->diff($user->getDateBirth())->y;

            if ($age < 16) {
                $data = $this->serializer->serialize(
                    ['error' => true, 'message' => "L'age de l'utilisateur ne permet pas(16 ans)"],
                    'json'
                );

                return new JsonResponse($data, Response::HTTP_NOT_ACCEPTABLE, [], true);
            }

            $searchArtist = $this->repository->findOneBy(['fullname' => $fullname]);
            if ($searchArtist) {
                $data = $this->serializer->serialize(
                    ['error' => true, 'message' => "Un utilisateur ayant ce nom d'artiste déjà enregistré"],
                    'json'
                );

                return new JsonResponse($data, Response::HTTP_CONFLICT, [], true);
            }

            try {
                $user->setRoles(["ROLE_ARTIST","ROLE_USER"]);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                
                $artist = new Artist();

                $artist->setUserIdUser($user);
                $artist->setFullname($fullname);
                $artist->setLabel($label);
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

                $data = $this->serializer->serialize(
                    ['error' => false, 'message' => "Votre inscription a bien été pris en compte"],
                    'json'
                );

                return new JsonResponse($data, Response::HTTP_CREATED, [], true);
            } catch (\Exception $e) {
                return $this->json(['errors' => $e->getMessage(), 'message' => 'An error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            if ($fullname == 'none') {
                $data = $this->serializer->serialize(
                    ['error' => true, 'message' => "Nom de l'artiste manquant"],
                    'json'
                );

                return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
            }


            $artist = $this->repository->findOneBy(['fullname' => $fullname]);
            if (!$artist) {
                $data = $this->serializer->serialize(
                    ['error' => true, 'message' => "Une ou plusieurs données erronées"],
                    'json'
                );

                return new JsonResponse($data, Response::HTTP_CONFLICT, [], true);
            }
            $songs = [];
            $albums = [];
            if (in_array('ROLE_ARTIST', $user->getRoles(), true)) {
                $songs = $this->entityManager->getRepository(Song::class)->findByVisibilityAndArtist($artist);
                $albums = $this->entityManager->getRepository(Album::class)->findBy(['artist_User_idUser' =>$artist]);
            }else {
                $songs = $this->entityManager->getRepository(Song::class)->findByVisibilityAndArtist($artist,true);
                $albums = $this->entityManager->getRepository(Album::class)->findBy(['artist_User_idUser' =>$artist,'visibility' => true]);
            }
           
            $data = $this->serializer->serialize(
                ['error' => false, 'artist' =>   $artist,'songs' => $songs,'albums'=>$albums],
                'json',
                [
                    'groups' => ['getArtist','getSongs','getAlbumArtist']
                ]
            );

            $dataArray = json_decode($data, true);
            $dataArray['artist'] = array_merge($dataArray['artist']['User_idUser'], $dataArray['artist']);
            unset($dataArray['artist']['User_idUser']);

            $modifiedData = json_encode($dataArray);

            return new JsonResponse($modifiedData, Response::HTTP_OK, [], true);
        }
    }

    /*#[Route('/artist/{!fullname}', name: 'artist_show', methods: ['GET'])]
    public function show(Request $request): JsonResponse
    {
        $fullname = $request->get("fullname");
        if(!isset($fullname)) {
            $data = $this->serializer->serialize(
                ['error' => true,'message' => "Nom de l'artiste manquant"], 
                'json'); 
            
            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }
        

        $artist = $this->repository->findOneBy(['fullname' => $fullname]);
        if(!$artist) {
            $data = $this->serializer->serialize(
                ['error' => true,'message' => "Une ou plusieurs données erronées"], 
                'json'); 
            
            return new JsonResponse($data, Response::HTTP_CONFLICT, [], true);
        }

        $data = $this->serializer->serialize($artist, 'json', ['groups' => 'getArtist']);
        $data = $this->serializer->serialize(
            ['error' => false, 'message' => 'User retreive successefully','data' =>   $data], 
            'json', 
            [
                'groups' => 'getUsers',
                AbstractNormalizer::CALLBACKS => [
                    'dateBirth' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                        return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                    },
                    'Artist.createdAt' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                        return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                    },
                    'createdAt' => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                        return $innerObject instanceof \DateTimeInterface ? $innerObject->format('d-m-Y') : '';
                    }
                ]
        ]); 

        return $this->json([
            'message' => 'Artist retreive successfully',
            'data' =>  $artist->jsonSerialize()
            ],Response::HTTP_OK);
    }

   
    #[Route('/artist/{id}', name: 'artist_edit', methods: ['POST','PUT'])]
    public function edit(Request $request,Artist $artist): JsonResponse
    {
        $data_received = $request->toArray();

        $artist->setFullname($data_received['fullname']?$data_received['fullname']:$artist->getFullname());
        $artist->setLabel($data_received['label']?$data_received['fullname']:$artist->getLabel());
        $artist->setDescription($data_received['description']);
        
        try {
            $this->entityManager->persist($artist);
            $this->entityManager->flush();
        }  catch (\Exception $e) {
            return $this->json(['errors' => $e->getMessage(),'message' => 'An error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        return $this->json([
            'message' => 'Artist modified successfully',
            'data' =>  $artist->jsonSerialize()
            ],Response::HTTP_OK);
    }

    #[Route('/artist/{id}', name: 'artist_delete', methods: ['DELETE'])]
    public function delete(Request $request, Artist $artist): JsonResponse
    {
        
        $this->entityManager->remove($artist);
        $this->entityManager->flush();
        $data = $this->serializer->serialize($artist, 'json');

        return $this->json([
            'message' => 'Artist deleted successfully',
            'data' =>  $artist->jsonSerialize()
            ],Response::HTTP_OK);
    }
    */
}
