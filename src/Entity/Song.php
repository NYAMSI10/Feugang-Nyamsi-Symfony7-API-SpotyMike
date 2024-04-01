<?php

namespace App\Entity;

use App\Repository\SongRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SongRepository::class)]
class Song
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 90, unique: true)]
    #[Groups(["getSongs", "getAlbums", "getArtist"])]
    private ?string $idSong = null;

    #[Groups(["getSongs", "getAlbums"])]
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[Groups(["getSongs"])]
    #[ORM\Column(length: 125)]
    private ?string $url = null;


    #[Groups(["getSongs", "getAlbums", "getArtist"])]
    #[ORM\Column(length: 125)]
    private ?string $cover = null;

    #[ORM\Column]
    private ?bool $visibility = true;

    #[ORM\ManyToMany(targetEntity: Artist::class, inversedBy: 'songs')]
    private Collection $Artist_idUser;

    #[ORM\ManyToOne(inversedBy: 'song_idSong')]
    private ?Album $album = null;

    #[ORM\ManyToOne(inversedBy: 'Song_idSong')]
    private ?PlaylistHasSong $playlistHasSong = null;

    #[Groups(["getSongs", "getArtist"])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->Artist_idUser = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdSong(): ?string
    {
        return $this->idSong;
    }

    public function setIdSong(string $idSong): static
    {
        $this->idSong = $idSong;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

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

    public function isVisibility(): ?bool
    {
        return $this->visibility;
    }

    public function setVisibility(bool $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @return Collection<int, Artist>
     */
    public function getArtistIdUser(): Collection
    {
        return $this->Artist_idUser;
    }

    public function addArtistIdUser(Artist $artistIdUser): static
    {
        if (!$this->Artist_idUser->contains($artistIdUser)) {
            $this->Artist_idUser->add($artistIdUser);
        }

        return $this;
    }

    public function removeArtistIdUser(Artist $artistIdUser): static
    {
        $this->Artist_idUser->removeElement($artistIdUser);

        return $this;
    }

    public function getAlbum(): ?Album
    {
        return $this->album;
    }

    public function setAlbum(?Album $album): static
    {
        $this->album = $album;

        return $this;
    }

    public function getPlaylistHasSong(): ?PlaylistHasSong
    {
        return $this->playlistHasSong;
    }

    public function setPlaylistHasSong(?PlaylistHasSong $playlistHasSong): static
    {
        $this->playlistHasSong = $playlistHasSong;

        return $this;
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
}
