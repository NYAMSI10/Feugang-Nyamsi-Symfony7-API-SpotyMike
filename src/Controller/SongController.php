<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\User;
use App\Repository\AlbumRepository;
use App\Repository\ArtistRepository;
use App\Repository\PlaylistHasSongRepository;
use App\Service\GenerateId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File;

class SongController extends AbstractController
{
    private $repository;
    private $serializer;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer,private readonly ParameterBagInterface $parameterBag)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Song::class);
        $this->serializer = $serializer;
    }

    /*#[Route('songs', name: 'app_all_song', methods: ['GET'])]
    public function getAllSongs(): JsonResponse
    {
        $songs = $this->repository->findAll();
        $jsonSongList = $this->serializer->serialize($songs, 'json', ['groups' => 'getSongs']);

        return new JsonResponse($jsonSongList, Response::HTTP_OK, [], true);
    }*/

    #[Route('song/{id}', name: 'app_detail_song', methods: ['GET'])]
    public function getDetailSong(Request $request, int $id = 0): JsonResponse
    {
        $id = $request->get('id');

        if (!isset($id) || $id == 0) {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Une ou plusieurs données obligatoires sont manquantes"],
                'json'
            );

            return new JsonResponse($data, Response::HTTP_BAD_REQUEST, [], true);
        }
        $song = $this->repository->find($request->get("id"));
        if ($song) {
            $jsonSongList = $this->serializer->serialize(["error" => false, "song" => $song], 'json', ['groups' => 'getSong']);

            return new JsonResponse($jsonSongList, Response::HTTP_OK, [], true);
        } else {
            $data = $this->serializer->serialize(
                ['error' => true, 'message' => "Une ou plusieurs données obligatoires sont erronnées"],
                'json'
            );
            return new JsonResponse($data, Response::HTTP_CONFLICT, [], true);
        }
    }

    
    #[Route('stream/{id}', name: 'app_detail_song', methods: ['GET'])]
    public function streamSong(Request $request, string $id): Response
    {

        $song = $this->repository->findOneBy(['idSong' =>$id]);
        $directoryPath = $this->parameterBag->get('SongDir');

        $filePath = $directoryPath = $directoryPath . '/' . $song->getUrl();
        $file = new File($filePath);
        $fileSize = $file->getSize();
        $range = $request->headers->get('Range');

        $response = new Response();

        if ($range) {
            list($unit, $range) = explode('=', $range, 2);
            if ($unit == 'bytes') {
                list($range) = explode(',', $range, 2);
                list($start, $end) = explode('-', $range);
                $start = intval($start);
                $end = $end ? intval($end) : $fileSize - 1;
                $length = $end - $start + 1;

                $response->headers->set('Content-Range', "bytes $start-$end/$fileSize");
                $response->headers->set('Content-Length', $length);
                $response->headers->set('Accept-Ranges', 'bytes');
                $response->headers->set('Content-Type', 'audio/mpeg');
                $response->setStatusCode(206);
                
                $handle = fopen($filePath, 'rb');
                fseek($handle, $start);
                $content = fread($handle, $length);
                fclose($handle);
                $response->setContent($content);
            }
        } else {
            $response->headers->set('Content-Length', $fileSize);
            $response->headers->set('Content-Type', 'audio/mpeg');
            $response->setContent(file_get_contents($filePath));
        }

        return $response;
    }


    #[Route('/album/{id}/song', name: 'app_create_song', methods: ['POST'])]
    public function create(Request $request,String $id, AlbumRepository $albumRepository, ArtistRepository $artistRepository, GenerateId $generateId): JsonResponse
    {
        
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $album = $albumRepository->findOneBy(["idAlbum"=>$id]);
        if (!$album)
            return $this->json([
                'error' => true,
                'message' => "Aucun album trouvé correspondant au nom fourni.",
            ], Response::HTTP_NOT_FOUND);

        $artist = $artistRepository->findOneBy(['User_idUser' => $user->getId()]);
        if (!in_array('ROLE_ARTIST', $user->getRoles(), true) || (in_array('ROLE_ARTIST', $user->getRoles(), true) && ($album->getArtistUserIdUser() != $artist)))
            return $this->json([
                'error' => true,
                'message' => "Vous n'avez pas l'autorisation pour accèder à cet album.",
            ], Response::HTTP_FORBIDDEN);

        
        $song_data = $request->getContent();

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_buffer($finfo, $song_data);
        finfo_close($finfo);
        //dd($mime);
        if (empty($song_data) || !in_array($mime, ['audio/mp3', 'audio/wav','audio/mpeg'])) {
        
            return $this->json([
                'error' => true,
                'message' => "Erreur sur le format du fichier qui n'est pas pris en compte.",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        
        $fileSize = strlen($song_data);
        $minSize = 1 * 1024 * 1024;
        $maxSize = 7 * 1024 * 1024;
        if ($fileSize < $minSize || $fileSize > $maxSize) {
            return $this->json([
                'error' => true,
                'message' => "Le fichier envoyé est trop ou pas assez volumineux. Vous devez respecter la taille entre 1MB et 7MB.",
            ], Response::HTTP_UNPROCESSABLE_ENTITY); 
        }

        
        $name = uniqid('', true) . '.' . ($mime === 'audio/mp3' ? 'mp3' : 'wav');
        $directoryPath = $this->parameterBag->get('SongDir');
        if (!is_dir($directoryPath)) {
            // If not, create it recursively
            mkdir($directoryPath, 0777, true);
        }
        $dest_path = $directoryPath . '/' . $name;
    
        file_put_contents($dest_path,$song_data);

        $song = new Song();
        $song->setIdSong($generateId->randId())
            ->setTitle('')
            ->setUrl($name)
            ->setCover('')
            ->addArtistIdUser($artist)
            ->setAlbum($album);
        $this->entityManager->persist($song);
        $this->entityManager->flush();

        return $this->json([
            'error' => false,
            'message' => "Album mis à jour avec succès.",
            'idSong' => $song->getIdSong()
        ], Response::HTTP_CREATED); 
        
    }

    #[Route('/song/{id}', name: 'app_update_song', methods: ['PUT'])]
    public function update(Request $request, Song $song, AlbumRepository $albumRepository, PlaylistHasSongRepository $playlistHasSongRepository, ArtistRepository $artistRepository): JsonResponse
    {
        $song = $this->repository->find($song);
        $song->setTitle($request->get('title'))
            //->setUrl($request->get('url'))
            ->setCover($request->get('cover'));
            //->setVisibility($request->get('visibility'))
            //->addArtistIdUser($artistRepository->find($request->get('idartistuser')))
            //->setAlbum($albumRepository->find($request->get('idalbum')))
            //->setPlaylistHasSong($playlistHasSongRepository->find($request->get('idplaylisthassong')));

        $this->entityManager->persist($song);
        $this->entityManager->flush();
        $jsonSongList = $this->serializer->serialize($song, 'json', ['groups' => 'getSongs']);

        return new JsonResponse(["songs:" => $jsonSongList], Response::HTTP_BAD_REQUEST, [], true);
    }

    #[Route('song/{id}', name: 'app_delete_song', methods: ['DELETE'])]
    public function delete(Song $song): JsonResponse
    {
        $this->entityManager->remove($song);
        $this->entityManager->flush();

        return new JsonResponse(["message" => "Delete Song Success"], Response::HTTP_NOT_FOUND);
    }
}
