<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // #[ORM\Id]
    //#[Groups(["getUsers"])]
    #[ORM\Column(length: 90)]
    private ?string $idUser = null;

    #[ORM\Column(length: 55)]
    #[Assert\NotBlank(message: 'firstname')]
    #[Assert\NotNull(message: 'firstname')]
    #[Assert\Length(max: 55, maxMessage: 'firstname')]
    #[Groups(["getUsers", "getLogin", "getArtist", "getAlbums"])]
    private ?string $firstname = null;

    #[ORM\Column(length: 80, unique: true)]
    #[Assert\NotBlank(message: 'email')]
    #[Assert\NotNull(message: 'email')]
    #[Assert\Email(message: 'email')]
    #[Assert\Length(max: 55, maxMessage: 'email')]
    #[Groups(["getUsers", "getLogin"])]
    private ?string $email = null;

    #[ORM\Column(length: 90)]
    #[Assert\NotBlank(message: 'The password must not be empty')]
    #[Assert\NotNull(message: 'The password must not be null')]
    private ?string $password = null;

    #[ORM\Column(length: 15, nullable: true)]
    #[Groups(["getUsers", "getLogin"])]
    #[Assert\Length(min: 10, max: 12, maxMessage: 'Telephone', minMessage: 'Telephone', exactMessage: 'Telephone')]
    private ?string $tel = null;

    #[ORM\OneToOne(mappedBy: 'User_idUser')]
    #[Groups(["getLogin"])]
    private ?Artist $artist = null;

    #[ORM\Column(length: 55)]
    #[Groups(["getUsers", "getLogin", "getArtist", "getAlbums"])]
    #[Assert\Length(max: 55, maxMessage: 'lastname')]
    private ?string $lastname = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(["getUsers", "getLogin", "getArtist", "getAlbums"])]
    #[Context([DateTimeNormalizer::FORMAT_KEY => ' d-m-Y'])]
    private ?\DateTimeInterface $dateBirth = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Groups(["getUsers", "getLogin", "getArtist", "getAlbums"])]
    #[Assert\Length(max: 30, maxMessage: 'sexe')]
    private ?string $sexe = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    #[Groups(["getUsers", "getLogin"])]
    #[Context([DateTimeNormalizer::FORMAT_KEY => ' Y-m-d'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(["getUsers"])]
    #[Context([DateTimeNormalizer::FORMAT_KEY => ' Y-m-d'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToMany(targetEntity: Artist::class, inversedBy: 'users')]
    private Collection $followers;

    #[ORM\ManyToMany(targetEntity: Playlist::class, inversedBy: 'users')]
    private Collection $share;

    #[ORM\OneToMany(targetEntity: Playlist::class, mappedBy: 'createdBy')]
    private Collection $playlists;

    #[ORM\Column]
    private ?bool $active = true;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        //$this->idUser = random_bytes(10);
        $this->followers = new ArrayCollection();
        $this->share = new ArrayCollection();
        $this->playlists = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdUser(): ?string
    {
        return $this->idUser;
    }

    public function setIdUser(string $idUser): static
    {
        $this->idUser = $idUser;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getTel(): ?string
    {

        return ($this->tel != null) ? $this->tel : '';
    }

    public function setTel(?string $tel): static
    {
        $this->tel = $tel;

        return $this;
    }



    public function getArtist(): ?Artist
    {
        return $this->artist;
    }

    public function setArtist(Artist $artist): static
    {
        // set the owning side of the relation if necessary
        if ($artist->getUserIdUser() !== $this) {
            $artist->setUserIdUser($this);
        }

        $this->artist = $artist;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getDateBirth(): ?\DateTimeInterface
    {
        return $this->dateBirth;
    }

    public function setDateBirth(\DateTimeInterface $dateBirth): static
    {
        $this->dateBirth = $dateBirth;

        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): static
    {
        $this->sexe = $sexe;

        return $this;
    }

    /**
     * The public representation of the user (e.g. a username, an email address, etc.)
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Artist>
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function addFollower(Artist $follower): static
    {
        if (!$this->followers->contains($follower)) {
            $this->followers->add($follower);
        }

        return $this;
    }

    public function removeFollower(Artist $follower): static
    {
        $this->followers->removeElement($follower);

        return $this;
    }

    /**
     * @return Collection<int, Playlist>
     */
    public function getShare(): Collection
    {
        return $this->share;
    }

    public function addShare(Playlist $share): static
    {
        if (!$this->share->contains($share)) {
            $this->share->add($share);
        }

        return $this;
    }

    public function removeShare(Playlist $share): static
    {
        $this->share->removeElement($share);

        return $this;
    }

    /**
     * @return Collection<int, Playlist>
     */
    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    public function addPlaylist(Playlist $playlist): static
    {
        if (!$this->playlists->contains($playlist)) {
            $this->playlists->add($playlist);
            $playlist->setCreatedBy($this);
        }

        return $this;
    }

    public function removePlaylist(Playlist $playlist): static
    {
        if ($this->playlists->removeElement($playlist)) {
            // set the owning side to null (unless already changed)
            if ($playlist->getCreatedBy() === $this) {
                $playlist->setCreatedBy(null);
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
