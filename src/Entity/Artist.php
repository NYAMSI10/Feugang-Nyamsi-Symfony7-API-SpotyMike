<?php

namespace App\Entity;

use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

#[ORM\Entity(repositoryClass: ArtistRepository::class)]
class Artist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'artist', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[SerializedName('artist')]
    #[Groups(["getArtist", "getArtists"])]
    private ?User $User_idUser = null;


    #[ORM\Column(length: 90, unique: true)]
    #[Assert\NotBlank(message: 'fullname')]
    #[Assert\NotNull(message: 'fullname')]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9]*$/', message: 'Fullname')]
    #[Groups(["getLogin"])]
    private ?string $fullname = null;


    // #[ORM\Column(length: 90)]
    // #[Assert\NotBlank(message: 'label')]
    // #[Assert\NotNull(message: 'label')]
    // private ?string $label = null;


    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["getLogin"])]
    private ?string $description = null;


    #[Groups(["getArtists"])]
    #[ORM\ManyToMany(targetEntity: Song::class, mappedBy: 'Artist_idUser', cascade: ['persist', 'remove'])]
    private Collection $songs;

    #[Groups(["getArtists"])]
    #[ORM\OneToMany(targetEntity: Album::class, mappedBy: 'artist_User_idUser', cascade: ['persist', 'remove'])]
    private Collection $albums;

    #[Groups(["getArtist"])]
    #[SerializedName('Artist.createdAt')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'followers')]
    private Collection $users;

    #[ORM\OneToMany(targetEntity: ArtistHasLabel::class, mappedBy: 'idArtist')]
    private Collection $artistHasLabels;

    #[ORM\Column]
    private ?bool $active = true;




    public function __construct()
    {
        $this->songs = new ArrayCollection();
        $this->albums = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->users = new ArrayCollection();
        $this->artistHasLabels = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdUser(): ?User
    {
        return $this->User_idUser;
    }

    public function setUserIdUser(User $User_idUser): static
    {
        $this->User_idUser = $User_idUser;

        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(string $fullname): static
    {
        $this->fullname = $fullname;

        return $this;
    }



    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Song>
     */
    public function getSongs(): Collection
    {
        return $this->songs;
    }

    public function addSong(Song $song): static
    {
        if (!$this->songs->contains($song)) {
            $this->songs->add($song);
            $song->addArtistIdUser($this);
        }

        return $this;
    }

    public function removeSong(Song $song): static
    {
        if ($this->songs->removeElement($song)) {
            $song->removeArtistIdUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Album>
     */
    public function getAlbums(): Collection
    {
        return $this->albums;
    }

    public function addAlbum(Album $album): static
    {
        if (!$this->albums->contains($album)) {
            $this->albums->add($album);
            $album->setArtistUserIdUser($this);
        }

        return $this;
    }

    public function removeAlbum(Album $album): static
    {
        if ($this->albums->removeElement($album)) {
            // set the owning side to null (unless already changed)
            if ($album->getArtistUserIdUser() === $this) {
                $album->setArtistUserIdUser(null);
            }
        }

        return $this;
    }

    private function serializeAlbums()
    {
        $albumIds = [];
        foreach ($this->getAlbums() as $album) {
            $albumIds[] = $album->getId(); // Assuming getId() returns the ID of the album
        }
        return $albumIds;
    }

    private function serializeSongs()
    {
        $songIds = [];
        foreach ($this->getSongs() as $song) {
            $songIds[] = $song->getId(); // Assuming getId() returns the ID of the album
        }
        return $songIds;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addFollower($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeFollower($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, ArtistHasLabel>
     */
    public function getArtistHasLabels(): Collection
    {
        return $this->artistHasLabels;
    }

    public function addArtistHasLabel(ArtistHasLabel $artistHasLabel): static
    {
        if (!$this->artistHasLabels->contains($artistHasLabel)) {
            $this->artistHasLabels->add($artistHasLabel);
            $artistHasLabel->setIdArtist($this);
        }

        return $this;
    }

    public function removeArtistHasLabel(ArtistHasLabel $artistHasLabel): static
    {
        if ($this->artistHasLabels->removeElement($artistHasLabel)) {
            // set the owning side to null (unless already changed)
            if ($artistHasLabel->getIdArtist() === $this) {
                $artistHasLabel->setIdArtist(null);
            }
        }

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
}
