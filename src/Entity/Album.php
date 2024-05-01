<?php

namespace App\Entity;

use App\Repository\AlbumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

#[ORM\Entity(repositoryClass: AlbumRepository::class)]
class Album
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 90)]
    #[Groups(["getAlbumArtist"])]
    #[SerializedName('id')]
    private ?string $idAlbum = null;

    #[ORM\Column(length: 90)]
    #[Assert\NotBlank(message: 'nom')]
    #[Assert\NotNull(message: 'nom')]
    #[Groups(["getAlbumArtist"])]
    private ?string $nom = null;

    // #[ORM\Column(length: 20)]
    // #[Assert\NotBlank(message: 'category')]
    // #[Assert\NotNull(message: 'category')]
    // #[Groups(["getAlbumArtist"])]
    // private ?string $categ = null;

    #[ORM\Column(length: 125, nullable: true)]
    #[Groups(["getAlbumArtist"])]
    private ?string $cover = null;

    #[ORM\Column]
    #[Groups(["getAlbumArtist"])]
    #[Assert\Type(
        type: 'integer',
        message: 'year',
    )]
    private ?int $year = 2024;
    #[Context([DateTimeNormalizer::FORMAT_KEY => ' Y-m-d'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["getAlbumArtist"])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'albums')]
    #[SerializedName('artist')]
    private ?Artist $artist_User_idUser = null;

    #[ORM\OneToMany(targetEntity: Song::class, mappedBy: 'album', cascade: ['persist', 'remove'])]
    private Collection $songs;



    #[ORM\Column]
    private ?bool $visibility = true;

    #[ORM\Column]
    private ?bool $active = true;

    #[ORM\Column(type: 'json')]
    private array $categ = [];

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

    // public function getCateg(): ?string
    // {
    //     return $this->categ;
    // }

    // public function setCateg(string $categ): static
    // {
    //     $this->categ = $categ;

    //     return $this;
    // }

    public function getCover(): ?string
    {
        return ($this->cover == null) ? '' : $this->cover;
    }

    public function setCover(?string $cover): static
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


    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt instanceof \DateTimeInterface ? \DateTimeImmutable::createFromMutable($this->createdAt) : null;
        //return $this->createdAt;
    }

    public function isVisibility(): ?bool
    {
        return $this->visibility;
    }

    public function setVisibility(bool $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getCateg(): ?array
    {
        return array_unique($this->categ);
    }

    public function setCateg(array $categ): static
    {
        $this->categ = $categ;

        return $this;
    }
}
