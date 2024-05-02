<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Artist;
use App\Entity\Album;
use App\Entity\ArtistHasLabel;
use App\Entity\Label;
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

        //Création d'un label

        for ($i = 0; $i < 3; $i++) {
            $label = new Label();
            $label->setNom("Label_" . rand(0, 999));
            $label->setIdLabel(uniqid());
            $manager->persist($label);
        }
        $manager->flush();

        // Création d'un user "normal"
        $user = new User();
        $user->setIdUser("User_" . rand(0, 999));
        $user->setEmail("feugang_ange@yahoo.com");
        $user->setFirstname("Feugang");
        $user->setLastname("Ange");
        $user->setDateBirth(new \DateTime("10-05-1996"));
        $user->setSexe("Feminin");
        $user->setRoles(["ROLE_USER"]);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());
        $user->setPassword($this->userPasswordHasher->hashPassword($user, 'Admin2024@'));
        $manager->persist($user);


        $userArtist = new User();
        $userArtist->setIdUser("User_" . rand(0, 999));
        $userArtist->setEmail("artist@bookapi.com");
        $userArtist->setFirstname("Nyamsi");
        $userArtist->setLastname("Brice");
        $userArtist->setDateBirth(new \DateTime("10-05-1992"));
        $userArtist->setSexe("Masculin");
        $userArtist->setRoles(["ROLE_ARTIST", "ROLE_USER"]);
        $userArtist->setCreatedAt(new \DateTimeImmutable());
        $userArtist->setUpdatedAt(new \DateTimeImmutable());
        $userArtist->setPassword($this->userPasswordHasher->hashPassword($userArtist, "Password20#"));
        $manager->persist($userArtist);



        //Création d'un artist
        $artist = new Artist();
        $artist->setUserIdUser($userArtist);
        $artist->setFullname($userArtist->getFirstname() . ' ' . $userArtist->getLastname());
        $manager->persist($artist);
    }
}
