<?php

namespace App\Entity;

use App\Repository\AlbumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AlbumRepository::class)]
class Album
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 90)]
    #[Groups(["getAlbums","getSongs","getArtist"])]
    private ?string $idAlbum = null;

    #[ORM\Column(length: 90)]
    #[Assert\NotBlank(message: 'The name must not be empty')]
    #[Assert\NotNull(message: 'The name must not be null')]
    #[Groups(["getAlbums","getArtist"])]
    private ?string $nom = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'The category must not be empty')]
    #[Assert\NotNull(message: 'The category must not be null')]
    #[Groups(["getAlbums","getSongs","getArtist"])]
    private ?string $categ = null;

    #[ORM\Column(length: 125)]
    #[Groups(["getAlbums","getSongs","getArtist"])]
    private ?string $cover = null;

    #[ORM\Column]
    #[Groups(["getSongs","getArtist"])]
    #[Assert\Type(
        type: 'integer',
        message: 'The value {{ value }} is not a valid {{ type }}.',
    )]
    private ?int $year = 2024;

    #[ORM\ManyToOne(inversedBy: 'albums')]
    private ?Artist $artist_User_idUser = null;

    #[ORM\OneToMany(targetEntity: Song::class, mappedBy: 'album')]
    #[Groups(["getAlbums"])]
    private Collection $songs;

    #[ORM\Column(nullable: true)]

    private ?\DateTimeImmutable $createAt = null;




    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["getArtist"])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->songs = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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
    public function getSongs(): Collection
    {
        return $this->songs;
    }

    public function addSongs(Song $songs): static
    {
        if (!$this->songs->contains($songs)) {
            $this->songs->add($songs);
            $songs->setAlbum($this);
        }

        return $this;
    }

    public function removeSongs(Song $songs): static
    {
        if ($this->songs->removeElement($songs)) {
            // set the owning side to null (unless already changed)
            if ($songs->getAlbum() === $this) {
                $songs->setAlbum(null);
            }
        }

        return $this;
    }



    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->createAt;
    }

    
}
