<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Artist;
use App\Entity\Album;
use App\Entity\Playlist;
use App\Entity\PlaylistHasSong;
use App\Entity\Song;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;
    
    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création d'un user "normal"
        $user = new User();
        $user->setIdUser("User_".rand(0,999));
        $user->setEmail("user@bookapi.com");
        $user->setFirstname("Feugang");
        $user->setLastname("Ange");
        $user->setDateBirth(new \DateTime("10-05-1996"));
        $user->setSexe("Feminin");
        $user->setRoles(["ROLE_USER"]);
        $user->setCreateAt(new \DateTimeImmutable());
        $user->setUpdateAt(new \DateTimeImmutable()); 
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "$2y$".rand(0,999999999999999999)));
        $manager->persist($user);
        
        
        $userArtist = new User();
        $userArtist->setIdUser("User_".rand(0,999));
        $userArtist->setEmail("artist@bookapi.com");
        $userArtist->setFirstname("Nyamsi");
        $userArtist->setLastname("Brice");
        $userArtist->setDateBirth(new \DateTime("10-05-1992"));
        $userArtist->setSexe("Masculin");
        $userArtist->setRoles(["ROLE_ARTIST"]);
        $userArtist->setCreateAt(new \DateTimeImmutable());
        $userArtist->setUpdateAt(new \DateTimeImmutable()); 
        $userArtist->setPassword($this->userPasswordHasher->hashPassword($userArtist, "password"));
        $manager->persist($userArtist);

        //Création d'un artist
        $artist = new Artist();
        $artist->setUserIdUser($userArtist);
        $artist->setFullname( $userArtist->getFirstname().' '.$userArtist->getLastname());
        $artist->setLabel("Maisons de disques");
        $manager->persist($artist);


        // Création des albums.
        for ($i = 0; $i < 3; $i++) {
            // Création de l'auteur lui-même.
            $album = new Album();
            $album->setIdAlbum("Album_".rand(0,999));
            $album->setNom("Héritage". $i);
            $album->setCateg("Zouk " . $i);
            $album->setCover("test" .$i);
            $album->setYear(date('Y'));
            $album->setArtistUserIdUser($artist);
            $manager->persist($album);
        }

        $manager->flush();
    }
}
