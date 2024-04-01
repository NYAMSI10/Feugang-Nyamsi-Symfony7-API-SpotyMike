<?php

namespace App\Entity;

use App\Repository\PlaylistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PlaylistRepository::class)]
class Playlist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // #[ORM\Id]
    #[ORM\Column(length: 90)]
    private ?string $idPlaylist = null;
    #[Groups(["getPlaylist"])]
    #[ORM\Column(length: 50)]
    private ?string $title = null;

    #[ORM\Column]
    #[Groups(["getPlaylist"])]
    private ?bool $public = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[Groups(["getPlaylist"])]
    #[ORM\ManyToOne(inversedBy: 'Playlist_idPlaylist')]
    private ?PlaylistHasSong $playlistHasSong = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdPlaylist(): ?string
    {
        return $this->idPlaylist;
    }

    public function setIdPlaylist(string $idPlaylist): static
    {
        $this->idPlaylist = $idPlaylist;

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

    public function isPublic(): ?bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): static
    {
        $this->public = $public;

        return $this;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

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
}
