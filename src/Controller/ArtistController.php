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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, ValidatorInterface $validator, JWTTokenManagerInterface $jwtManager, private readonly ParameterBagInterface $parameterBag)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Artist::class);
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->jwtManager = $jwtManager;
    }


    #[Route('/artist/{fullname}', name: 'artist_list', methods: ['GET'])]
    public function index(Request $request, string $fullname = 'none'): JsonResponse
    {
        $current_page = $request->get('currentPage');

        if ($current_page) {
            $page = 1;
            $limit = 5;

            if (isset($current_page)) {
                if ((!is_numeric($current_page)) || ($current_page <= 0) || !$current_page) {
                    $data = $this->serializer->serialize([
                        'error' => true,
                        'message' => "Le paramètre de pagination est invalide. Veuillez fournir un numéro de page valide"
                    ], 'json');
                    return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
                }
            }
            if (!$current_page) {
                $data = $this->serializer->serialize([
                    'error' => true,
                    'message' => "Le paramètre de pagination est invalide. Veuillez fournir un numéro de page valide"
                ], 'json');
                return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
            }

            $artists = $this->repository->getAllArtist($current_page, $limit);
            $nb_items = is_countable($artists) ? count($artists) : 0;

            if (($nb_items == 0) ||  ($current_page > ceil($nb_items / $limit))) {
                return $this->json([
                    'error' => true,
                    'message' => 'Aucun artist trouvé pour la page demandée.',
                ], Response::HTTP_NOT_FOUND);
            }

            $artists_data = $this->formatData($artists);

            return $this->json([
                'error' => false,
                'artists' => $artists_data,
                'message' => "Informations des artistes récupérées avec succès.",
                'pagination' => [
                    'current_page' => $current_page,
                    'totalPages' => ceil($nb_items / $limit),
                    'totalArtiste' => $nb_items,
                ]
            ], Response::HTTP_OK);
        } else {

            $artist = $this->repository->findOneBy(["fullname" => $fullname]);


            if ($fullname == 'none') {
                $data = $this->serializer->serialize(
                    ['error' => true, 'message' => "Le nom d'artiste est obligatoire pour cette requete"],
                    'json'
                );

                return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
            }
            if (!preg_match('/^[A-Z a-z 0-9]/', $fullname) || !is_string($fullname)) {
                $data = $this->serializer->serialize(
                    ['error' => true, 'message' => "Le format du nom d'artiste fourni est invalide"],
                    'json'
                );
                return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
            }

            if (!$artist) {
                $data = $this->serializer->serialize(
                    ['error' => true, 'message' => "Aucun artiste trouvé correspondant au nom fourni"],
                    'json'
                );
                return new JsonResponse($data, Response::HTTP_NOT_FOUND, [], true);
            }

            $artist_data = $this->formatDataOneArtist($artist);
            return $this->json([
                'error' => false,
                'artist' => $artist_data,
            ], Response::HTTP_OK);
        }
    }


    #[Route('/artist', name: 'artist_new_or_edit',  methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $fullname = $request->get('fullname');
        $id_label = $request->get('label');
        $description = $request->get('description');
        $avatar = $request->get('avatar');


        if (!in_array('ROLE_ARTIST', $user->getRoles(), true)) {

            if ($avatar) {
                // Cela sépare la chaîne en utilisant '/' et récupère le deuxième élément (l'extension)
                $image_decodee = base64_decode($avatar);
                $taille = strlen($image_decodee) * 8;
                $decoded_data = strpos($avatar, 'data:image/') === 0;

                if (!$decoded_data) {
                    $data = $this->serializer->serialize(
                        ['error' => true, 'message' => "Le serveur ne peut pas décoder le contenu en base64 en fichier binaire."],
                        'json'
                    );

                    return new JsonResponse($data, Response::HTTP_UNPROCESSABLE_ENTITY, [], true);
                }
                $explodeData = explode(",", $avatar);
                $fileType = explode('/', $explodeData[0])[1];
                $formatList = ["png", "jpeg"];
                if (
                    !in_array(explode(";", $fileType)[0], $formatList, true)
                ) {
                    $data = $this->serializer->serialize(
                        ['error' => true, 'message' => "Erreur sur le format du fichier qui n'est pas pris en charge. "],
                        'json'
                    );

                    return new JsonResponse($data, Response::HTTP_UNPROCESSABLE_ENTITY, [], true);
                }

                if ((1000000 > $taille) && ($taille > 7_000_000)) {
                    $data = $this->serializer->serialize(
                        ['error' => true, 'message' => "Le fichier envoyé est trop ou pas assez volumineux. vous devez respecter la taille entre 1mb et 7mb."],
                        'json'
                    );

                    return new JsonResponse($data, Response::HTTP_UNPROCESSABLE_ENTITY, [], true);
                }



                if (count($explodeData) == 2) {
                    $file = base64_decode($explodeData[1]);
                    $format = explode(";", $fileType)[0];
                    $name = uniqid('', true) . '.' . $format;
                    $dest_path = $this->getParameter('ArtistImgDir') . '/' . $name;
                    file_put_contents($dest_path, $file);
                }
            };

            if ((!$fullname) || (!$id_label)) {
                $data = $this->serializer->serialize(
                    ['error' => true, 'message' => "L'id du label et le fullname sont obligatoires"],
                    'json'
                );

                return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
            }
            if (!is_string($id_label)) {
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
            $label = $this->entityManager->getRepository(Label::class)->findOneBy(["idLabel" => $id_label]);
            if (!$label) {
                $data = $this->serializer->serialize(
                    ['error' => true, 'message' => "L'id du label est invalide"],
                    'json'
                );

                return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
            }

            try {
                $user->setRoles(["ROLE_ARTIST", "ROLE_USER"]);
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $artist = new Artist();

                $artist->setUserIdUser($user);
                $artist->setFullname($fullname);
                // $artist->setLabel($label);
                $artist->setAvatar($name);
                $artist->setDescription(isset($description) ? $description : '');


                $this->entityManager->persist($artist);
                $this->entityManager->flush();

                $artistHasLabel = new ArtistHasLabel();
                $artistHasLabel->setIdArtist($artist)
                    ->setIdLabel($this->entityManager->getRepository(Label::class)->find($label))
                    ->setEntrydate(new \DateTimeImmutable());

                $this->entityManager->persist($artistHasLabel);
                $this->entityManager->flush();


                $data = $this->serializer->serialize(
                    [
                        'success' => false,
                        'message' => "Votre compte d'artiste a été crée avec succès. Bienvenue dans notre communauté d'artistes.",
                    ],
                    'json'
                );

                return new JsonResponse($data, Response::HTTP_CREATED, [], true);
            } catch (\Exception $e) {
                return $this->json(['errors' => $e->getMessage(), 'message' => 'Une erreur est survenue'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            $parameters = $request->request->all();
            $allowedParameters = ['fullname', 'label', 'description', 'avatar'];
            foreach ($parameters as $key => $value) {
                if (!in_array($key, $allowedParameters)) {
                    return $this->json([
                        'error' => true,
                        'message' => 'Les paramètres fournis sont invalides. Veuillez vérifier les données soumises.',
                    ], Response::HTTP_BAD_REQUEST);
                }
            }
            $artist = $user->getArtist();

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
            $name = '';
            if ($parameters['avatar']) {
                $explodeData = explode(",", $parameters['avatar']);

                if (count($explodeData) == 2) {

                    $decodedCover = base64_decode($explodeData[1]);
                    $imageInfo = getimagesizefromstring($decodedCover);

                    if ($decodedCover === false || empty($decodedCover) || ($imageInfo === false)) {
                        return $this->json([
                            'error' => true,
                            'message' => 'Le serveur ne peut pas décoder le contenu base64 en fichier binaire',
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    $format = null;
                    $jpegHeader = 'data:image/jpeg;base64';
                    $pngHeader = 'data:image/png;base64';

                    // Check if the header matches known image format headers
                    if (strpos($explodeData[0], $jpegHeader) === 0) {
                        $format = 'jpeg';
                    } elseif (strpos($explodeData[0], $pngHeader) === 0) {
                        $format = 'png';
                    }

                    if ($format == null) {
                        return $this->json([
                            'error' => true,
                            'message' => "Erreur sur le format du fichier qui n'est pas pris en compte.",
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }
                    $imageSizeBytes = strlen($decodedCover);
                    $imageSizeMB = $imageSizeBytes / (1024 * 1024);
                    if (!($imageSizeMB >= 1 && $imageSizeMB <= 7)) {
                        return $this->json([
                            'error' => true,
                            'message' => "Le fichier envoyé est trop ou pas assez volumineux. Vous devez respecter la taille entre 1MB et 7MB.",
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    $name = uniqid('', true) . '.' . $format;
                    $dest_path = $this->parameterBag->get('ArtistImgDir') . '/' . $name;

                    file_put_contents($dest_path, $decodedCover);
                } else {
                    return $this->json([
                        'error' => true,
                        'message' => 'Le serveur ne peut pas décoder le contenu base64 en fichier binaire',
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }


            $artist->setFullname(isset($fullname) ? $fullname : $artist->getFullname());
            $artist->setDescription(isset($description) ? $description : $artist->getDescription());
            $artist->setAvatar($name);

            $this->entityManager->persist($artist);

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



    public function formatDataOneArtist($artist)
    {
        $response = [];
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        if (in_array('ROLE_ARTIST', $user->getRoles(), true)) {
            $albums = $this->entityManager->getRepository(Album::class)->getAllAlbumsIndefferent($artist->getId());
        } else {
            $albums = $this->entityManager->getRepository(Album::class)->getAllAlbumsVisibility($artist->getId());
        }

        $artistData = [
            'firstname' => $artist->getUserIdUser()->getFirstname(),
            'lastname' => $artist->getUserIdUser()->getLastname(),
            'fullname' => $artist->getFullname(),
            'avatar' => $artist->getAvatar(),
            "follower" => [],
            'sexe' =>  $artist->getUserIdUser()->getSexe(),
            'dateBirth' => $artist->getUserIdUser()->getDateBirth()->format('d-m-Y'),
            'Artist.createdAt' => $artist->getCreatedAt()->format('Y-m-d'),
            'featuring' => [],
            'albums' => $this->formatDataAlbums($albums),

        ];

        $tempFollower = [];
        $tempFeaturing = [];

        foreach ($artist->getUsers() as $follower) {
            $followerData = [
                'nom' => $follower->getFirstname() . ' ' . $follower->getLastname(),
            ];

            $tempFollower[] = $followerData;
        }



        foreach ($artist->getSongs() as $collaborator) {
            $featuringData = [
                'id' => $collaborator->getIdSong(),
                'title' => $collaborator->getTitle(),
                'cover' => $collaborator->getCover(),
                'artist' =>  $this->formatData($collaborator->getArtistIdUser()),
                'createdAt' => $collaborator->getCreatedAt()->format('Y-m-d'),
            ];

            $tempFeaturing[] = $featuringData;
        }
        $artistData['featuring'] = $tempFeaturing;
        $artistData['follower'] = $tempFollower;


        $response[] = $artistData;
        return $response;
    }


    public function formatData($artists)
    {
        $response = [];
        foreach ($artists as $artist) {
            $artistData = [
                'firstname' => $artist->getUserIdUser()->getFirstname(),
                'lastname' => $artist->getUserIdUser()->getLastname(),
                'fullname' => $artist->getFullname(),
                'avatar' => $artist->getAvatar(),
                'sexe' =>  $artist->getUserIdUser()->getSexe(),
                'dateBirth' => $artist->getUserIdUser()->getDateBirth()->format('d-m-Y'),
                'Artist.createdAt' => $artist->getCreatedAt()->format('Y-m-d'),
                'albums' => [],
            ];

            // Utiliser un tableau temporaire pour stocker les albums sans l'artiste pour éviter les références circulaires
            $tempAlums = [];
            foreach ($artist->getAlbums() as $album) {

                $label_id = $this->entityManager->getRepository(ArtistHasLabel::class)->findLabel($artist->getId(), $album->getCreatedAt());
                $label = $this->entityManager->getRepository(Label::class)->find($label_id['id']);

                $tempAlbum = [
                    'id' => $album->getIdAlbum(),
                    'nom' => $album->getNom(),
                    'categ' => $album->getCateg(),
                    'cover' => $album->getCover(),
                    'year' => $album->getYear(),
                    'label' => $label->getNom(), // Remplacez cela par la logique appropriée pour récupérer le label
                    'createdAt' => $album->getCreatedAt()->format('Y-m-d'),
                    'songs' => [],
                ];

                foreach ($album->getSongs() as $song) {
                    $tempAlbum['songs'][] = [
                        'id' => $song->getIdSong(),
                        'title' => $song->getTitle(),
                        'cover' => $song->getCover(),
                        'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
                    ];
                }

                $tempAlbums[] = $tempAlbum;
            }

            $artistData['albums'] = $tempAlbums;

            $response[] = $artistData;
        }

        return $response;
    }

    public function formatDataAlbums($albums)
    {
        $response = [];
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        foreach ($albums as $album) {


            $label_id = $this->entityManager->getRepository(ArtistHasLabel::class)->findLabel($album->getArtistUserIdUser()->getId(), $album->getCreatedAt());
            $label = $this->entityManager->getRepository(Label::class)->find($label_id['id']);

            $responseAlbum = [
                'id' => $album->getIdAlbum(),
                'nom' => $album->getNom(),
                'categ' => $album->getCateg(),
                'cover' => $album->getCover(),
                'year' => $album->getYear(),
                'label' => $label->getNom(),
                'createdAt' => $album->getCreatedAt()->format('Y-m-d'),
                'songs' => [],
            ];

            foreach ($album->getSongs() as $song) {
                if (in_array('ROLE_ARTIST', $user->getRoles(), true)) {
                    $songData = [
                        'id' => $song->getIdSong(),
                        'title' => $song->getTitle(),
                        'cover' => $song->getCover(),
                        'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
                    ];
                    $responseAlbum['songs'][] = $songData;
                } else {
                    if ($song->isVisibility()) {
                        $songData = [
                            'id' => $song->getIdSong(),
                            'title' => $song->getTitle(),
                            'cover' => $song->getCover(),
                            'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
                        ];
                        $responseAlbum['songs'][] = $songData;
                    }
                }
            }

            $response[] = $responseAlbum;
        }


        return $response;
    }
}
