<?php

namespace App\Entity;

use App\Repository\AlbumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AlbumRepository::class)]
class Album implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(["getSongs"])]
    #[ORM\Column(length: 90)]
    private ?string $idAlbum = null;

    #[ORM\Column(length: 90)]
    #[Assert\NotBlank(message: 'The name must not be empty')]
    #[Assert\NotNull(message: 'The name must not be null')]
    private ?string $nom = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'The category must not be empty')]
    #[Assert\NotNull(message: 'The category must not be null')]
    #[Groups(["getSongs"])]
    private ?string $categ = null;


    #[ORM\Column(length: 125)]
    #[Groups(["getSongs"])]
    private ?string $cover = null;

    #[ORM\Column]
    #[Groups(["getSongs"])]
    #[Assert\Type(
        type: 'integer',
        message: 'The value {{ value }} is not a valid {{ type }}.',
    )]
    private ?int $year = 2024;

    #[ORM\ManyToOne(inversedBy: 'albums')]
    private ?Artist $artist_User_idUser = null;

    #[ORM\OneToMany(targetEntity: Song::class, mappedBy: 'album')]
    private Collection $song_idSong;

    public function __construct()
    {
        $this->song_idSong = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdAlbum(): ?string
    {
        return $this->idAlbum;
    }

    public function setIdAlbum(string $idAlbum): static
    {
        $this->idAlbum = $idAlbum;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getCateg(): ?string
    {
        return $this->categ;
    }

    public function setCateg(string $categ): static
    {
        $this->categ = $categ;

        return $this;
    }

    public function getCover(): ?string
    {
        return $this->cover;
    }

    public function setCover(string $cover): static
    {
        $this->cover = $cover;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getArtistUserIdUser(): ?Artist
    {
        return $this->artist_User_idUser;
    }

    public function setArtistUserIdUser(?Artist $artist_User_idUser): static
    {
        $this->artist_User_idUser = $artist_User_idUser;

        return $this;
    }

    /**
     * @return Collection<int, Song>
     */
    public function getSongIdSong(): Collection
    {
        return $this->song_idSong;
    }

    public function addSongIdSong(Song $songIdSong): static
    {
        if (!$this->song_idSong->contains($songIdSong)) {
            $this->song_idSong->add($songIdSong);
            $songIdSong->setAlbum($this);
        }

        return $this;
    }

    public function removeSongIdSong(Song $songIdSong): static
    {
        if ($this->song_idSong->removeElement($songIdSong)) {
            // set the owning side to null (unless already changed)
            if ($songIdSong->getAlbum() === $this) {
                $songIdSong->setAlbum(null);
            }
        }

        return $this;
    }

    public function jsonSerialize() {
        return [
            "id" => $this->getId(),
            "idAlbum" => $this->getIdAlbum(),
            "artist" => $this->getArtistUserIdUser(),
            "nom" => $this->getNom(),
            "categ" => $this->getCateg(),
            "cover" => $this->getCover(),
            "year" => $this->getYear(),
            "songs" => $this->getSongIdSong()->toArray(),
            
        ];
    }
}