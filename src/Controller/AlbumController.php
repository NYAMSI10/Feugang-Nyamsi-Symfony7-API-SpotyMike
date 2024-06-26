<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Album;
use App\Entity\ArtistHasLabel;
use App\Entity\Label;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use App\Repository\UserRepository;
use App\Service\FormatData;
use App\Service\GenerateId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, ValidatorInterface $validator, private readonly ParameterBagInterface $parameterBag, private readonly FormatData $formatData)
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
        $current_page = $request->get('currentPage');
        $limit = $request->get('limit', 5);
        $check_visibility = 0;
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        if (!(is_numeric($current_page) && $current_page >= 0 && $current_page)) {
            return $this->json([
                'error' => true,
                'message' => 'Le paramètre de pagination est invalide. Veuillez fournir un numéro de page valide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array('ROLE_ARTIST', $user->getRoles(), true))
            $check_visibility = 1;

        $albums = $this->repository->getAllAlbums($current_page, $limit, $check_visibility);
        $nb_items = is_countable($albums) ? count($albums) : 0;

        if (($nb_items == 0) ||  ($current_page > ceil($nb_items / $limit))) {
            return $this->json([
                'error' => true,
                'message' => 'Aucun album trouvé pour la page demandée.',
            ], Response::HTTP_NOT_FOUND);
        }

        $albums_data = $this->formatData->formatDataAlbumsWithFeaturings($albums, $this->getUser());
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



    #[Route('album/{id}', name: 'album_show', methods: ['GET'])]
    public function getOne(Request $request, string $id = 'none'): JsonResponse
    {
        if ($id == 'search') {
            $parameters = $_GET;
            $allowedParameters = ['currentPage', 'limit', 'nom', 'labe', 'year', 'featuring', 'category'];
            $categoryList = ["rap", "r'n'b", "gospel", "soul", "country", "hip hop", "jazz", "rap", "le Mike"];

            $nom = $fullname = $label = $year = $featuring = $category = $year = null;
            $current_page = 1;
            $limit = 5;

            $check_visibility = 0;
            foreach ($parameters as $key => $value) {
                if (!in_array($key, $allowedParameters)) {
                    return $this->json([
                        'error' => true,
                        'message' => 'Les paramètres fournis sont invalides. Veuillez vérifier les données soumises.',
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            if (isset($parameters['year'])) {
                if (!is_numeric($parameters['year']) || strlen($parameters['year']) > 4 || strlen($parameters['year']) < 4) {
                    return $this->json([
                        'error' => true,
                        'message' => "L'année n'est pas valide.",
                    ], Response::HTTP_BAD_REQUEST);
                }
                $year = $parameters['year'];
            }

            if (isset($parameters['current_page'])) {
                if (!(is_numeric($parameters['current_page'] && $parameters['current_page'] >= 0 && $parameters['current_page']))) {
                    return $this->json([
                        'error' => true,
                        'message' => 'Le paramètre de pagination invalide. Veuillez fournir un numéro de page valide.',
                    ], Response::HTTP_BAD_REQUEST);
                }
                $current_page = $parameters['current_page'];
            }

            if (isset($parameters['category'])) {
                $jsonString = str_replace("'", '"', $parameters['category']);

                $decodedData = json_decode($jsonString);
                foreach ($decodedData as $element) {
                    if (!in_array($element, $categoryList)) {
                        return $this->json([
                            'error' => true,
                            'message' => 'Les categorie ciblée sont invalide.',
                        ], Response::HTTP_BAD_REQUEST);
                    }
                }
                $category = $decodedData;
            }

            if (isset($parameters['featuring'])) {
                $jsonString = str_replace("'", '"', $parameters['featuring']);

                $decodedFeaturing = json_decode($jsonString);
                foreach ($decodedFeaturing as $element) {
                    $artist = $this->entityManager->getRepository(Artist::class)->findOneBy(['fullname' => $element]);
                    if (!$artist)
                        return $this->json([
                            'error' => true,
                            'message' => 'Les featuring ciblée sont invalide.',
                        ], Response::HTTP_BAD_REQUEST);
                }
                $featuring = $decodedFeaturing;
            }

            $albums = $this->repository->searchAlbum($nom, $fullname, $label, $year, $featuring, $category, $current_page, $limit, $check_visibility);
            $nb_items = is_countable($albums) ? count($albums) : 0;
            if ($nb_items == 0) {
                return $this->json([
                    'error' => true,
                    'message' => 'Aucun album trouvé pour la page demandée.',
                ], Response::HTTP_NOT_FOUND);
            }
            $albums_data = $this->formatData->formatDataAlbumsWithFeaturings($albums, $this->getUser());
            return $this->json([
                'error' => false,
                'albums' => $albums_data,
                'pagination' => [
                    'current_page' => $current_page,
                    'totalAlbums' => $nb_items,
                    'totalPages' => ceil($nb_items / $limit)
                ]
            ], Response::HTTP_OK);
        } else {
            if ($id == 'none') {
                return $this->json([
                    'error' => true,
                    'album' => "L'id de l'album est obligatoire pour cette requête.",
                ], Response::HTTP_BAD_REQUEST);
            }
            $album = $this->repository->findOneBy(['idAlbum' => $id]);
            if (!$album) {
                return $this->json([
                    'error' => true,
                    'message' => "L'album non trouvé. Vérifiez les informations fournies et réessayez.",
                ], Response::HTTP_NOT_FOUND);
            }
            $album_data = $this->formatData->formatDataAlbumWithFeaturings($album, $this->getUser());
            return $this->json([
                'error' => false,
                'album' => $album_data,
            ], Response::HTTP_OK);
        }
    }

    #[Route('album/image/{id}', name: 'album_show', methods: ['GET'])]
    public function getAlbumImage(Request $request, string $id = 'none'): Response
    {
        $album = $this->repository->findOneBy(['idAlbum' => $id]);
        if (!$album) {
            return $this->json([
                'error' => true,
                'message' => "L'album non trouvé. Vérifiez les informations fournies et réessayez.",
            ], Response::HTTP_NOT_FOUND);
        }
        
       /* return $this->json([
            'error' => false,
            'album' => $album->getCover(),
        ], Response::HTTP_OK);*/
        $directoryPath = $this->parameterBag->get('AlbumImgDir');
        return new Response($directoryPath . '/' . $album->getCover());
       
    }


    #[Route('/album', name: 'album_new', methods: 'POST')]
    public function new(Request $request, GenerateId $generateId): JsonResponse
    {
        $categories = ["rap", "r'n'b", "gospel", "soul", "country", "hip hop", "jazz", "le Mike"];
        $parameters = $_POST;

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        if (!in_array('ROLE_ARTIST', $user->getRoles(), true))
            return $this->json([
                'error' => true,
                'message' => "Vous n'avez pas l'autorisation pour accèder à cet album.",
            ], Response::HTTP_FORBIDDEN);

        if (!isset($parameters['visibility']) || !isset($parameters['cover']) || !isset($parameters['title']) || !isset($parameters['categorie'])) {
            return $this->json([
                'error' => true,
                'message' => 'Les paramètres fournis sont invalides. Veuillez vérifier les données soumises.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if there are any parameters other than "label" and "categorie"
        $allowedParameters = ['visibility', 'cover', 'title', 'categorie'];
        foreach ($parameters as $key => $value) {
            if (!in_array($key, $allowedParameters)) {
                return $this->json([
                    'error' => true,
                    'message' => 'Les paramètres fournis sont invalides. Veuillez vérifier les données soumises.',
                ], Response::HTTP_BAD_REQUEST);
            }
        }



        if ($parameters['visibility'] != 0 && $parameters['visibility'] != 1) {
            return $this->json([
                'error' => true,
                'message' => 'La valeur du champ visibility est invalide. Les valeurs autorisées sont 0 pour invisible, 1 pour visible.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $jsonString = str_replace("'", '"', $parameters['categorie']);
        $decodedData = json_decode($jsonString);


        $titlepattern = '/^[\p{L}\p{N}\s\p{P}]{1,90}$/u';

        if (!($decodedData !== null && json_last_error() === JSON_ERROR_NONE) || !preg_match($titlepattern, $parameters['title'])) {
            return $this->json([
                'error' => true,
                'message' => 'Erreur de validation des données.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        foreach ($decodedData as $element) {
            if (!in_array($element, $categories)) {
                return $this->json([
                    'error' => true,
                    'message' => 'Les categorie ciblée sont invalide.',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $artist = $user->getArtist();
        $existAlbum = $this->repository->findOneBy(["nom" => $parameters['title'], "artist_User_idUser" => $artist]);
        if ($existAlbum) {
            return $this->json([
                'error' => true,
                'message' => 'Ce titre est déjà pris. Veuillez en choisir un autre.',
            ], Response::HTTP_CONFLICT);
        }

        $explodeData = explode(",", $parameters['cover']);
        $name = '';
        if (count($explodeData) == 2) {
            $decodedCover = base64_decode($explodeData[1]);
            $imageInfo =  strpos($parameters['cover'], 'data:image/') === 0;

            if ($decodedCover === false || empty($decodedCover) || !($imageInfo)) {
                return $this->json([
                    'error' => true,
                    'message' => 'Le serveur ne peut pas décoder le contenu base64 en fichier binaire.',
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
            $directoryPath = $this->parameterBag->get('AlbumImgDir');
            if (!is_dir($directoryPath)) {
                // If not, create it recursively
                mkdir($directoryPath, 0777, true);
            }
            $dest_path = $directoryPath . '/' . $name;


            file_put_contents($dest_path, $decodedCover);
        } else {
            return $this->json([
                'error' => true,
                'message' => 'Le serveur ne peut pas décoder le contenu base64 en fichier binaire.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        $album = new Album();
        $album->setIdAlbum($generateId->randId())
            ->setNom($parameters['title'])
            ->setCateg($decodedData)
            ->setVisibility($parameters['visibility'])
            ->setYear(date("Y"))
            ->setArtistUserIdUser($artist)
            ->setCover($name);

        $this->entityManager->persist($album);
        $this->entityManager->flush();
        return $this->json([
            'error' => false,
            'message' => 'Album créé avec succès.',
            'id' => $album->getIdAlbum()
        ], Response::HTTP_CREATED);
    }

    #[Route('album/{id}', name: 'album_edit', methods: 'PUT')]
    public function edit(Request $request, String $id): JsonResponse
    {
        $parameters = $request->request->all();
        $categories = ["rap", "r'n'b", "gospel", "soul", "country", "hip hop", "jazz", "le Mike"];

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        if (!in_array('ROLE_ARTIST', $user->getRoles(), true))
            return $this->json([
                'error' => true,
                'message' => "Vous n'avez pas l'autorisation pour accèder à cet album.",
            ], Response::HTTP_FORBIDDEN);


        $allowedParameters = ['visibility', 'cover', 'title', 'categorie'];
        foreach ($parameters as $key => $value) {
            if (!in_array($key, $allowedParameters)) {
                return $this->json([
                    'error' => true,
                    'message' => 'Les paramètres fournis sont invalides. Veuillez vérifier les données soumises.',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        if (isset($parameters['visibility']) && $parameters['visibility'] != 0 && $parameters['visibility'] != 1) {
            return $this->json([
                'error' => true,
                'message' => 'La valeur du champ visibility est invalide. Les valeurs autorisées sont 0 pour invisible, 1 pour visible.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (isset($parameters['categorie'])) {
            $decodedData = json_decode($parameters['categorie']);
            $titlepattern = '/^[\p{L}\p{N}\s\p{P}]{1,90}$/u';

            if (!($decodedData !== null && json_last_error() === JSON_ERROR_NONE) || !preg_match($titlepattern, $parameters['title'])) {
                return $this->json([
                    'error' => true,
                    'message' => 'Erreur de validation des données.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $missingCategories = array_diff($decodedData, $categories);

            if (!empty($missingCategories)) {
                return $this->json([
                    'error' => true,
                    'message' => 'Les categorie ciblée sont invalide.',
                ], Response::HTTP_BAD_REQUEST);
            }
        }


        $album = $this->repository->findOneBy(["idAlbum" => $id]);
        if (!$album) {
            return $this->json([
                'error' => true,
                'message' => 'Aucun album trouvé correspondant au nom fourni.',
            ], Response::HTTP_NOT_FOUND);
        }

        $artist = $user->getArtist();
        if (isset($parameters['title'])) {
            $existAlbum = $this->repository->findOneBy(["nom" => $parameters['title'], "artist_User_idUser" => $artist]);
            if ($existAlbum && ($album != $existAlbum)) {
                return $this->json([
                    'error' => true,
                    'message' => 'Ce titre est déjà pris. Veuillez en choisir un autre.',
                ], Response::HTTP_CONFLICT);
            }
        }


        $name = null;
        if (isset($parameters['cover'])) {
            $explodeData = explode(",", $parameters['cover']);
            if (count($explodeData) == 2) {

                $decodedCover = base64_decode($explodeData[1]);
                $imageInfo = getimagesizefromstring($decodedCover);

                if ($decodedCover === false || empty($decodedCover) || ($imageInfo === false)) {
                    return $this->json([
                        'error' => true,
                        'message' => 'Le serveur ne peut pas décoder le contenu base64 en fichier binaire.',
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
                $directoryPath = $this->parameterBag->get('AlbumImgDir');
                if (!is_dir($directoryPath)) {
                    // If not, create it recursively
                    mkdir($directoryPath, 0777, true);
                }
                $dest_path = $directoryPath . '/' . $name;

                file_put_contents($dest_path, $decodedCover);
            } else {
                return $this->json([
                    'error' => true,
                    'message' => 'Le serveur ne peut pas décoder le contenu base64 en fichier binaire.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }
        if ($parameters['categorie']) {
            $category = json_decode($parameters['categorie'], true);
        }

        $album->setNom(isset($parameters['title']) ? $parameters['title'] : $album->getNom());
        $album->setCateg(isset($parameters['categorie']) ? $category : $album->getCateg());
        $album->setCover(isset($name) ? $name : $album->getCover());
        $album->setVisibility(isset($parameters['visibility']) ? $parameters['visibility'] : $album->isVisibility());
        $this->entityManager->persist($album);
        $this->entityManager->flush();

        return $this->json([
            'error' =>  false,
            'message' => 'Album mise à jour avec succès.'
        ], Response::HTTP_OK);
    }
}
