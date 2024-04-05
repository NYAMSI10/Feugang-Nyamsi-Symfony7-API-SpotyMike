<?php

namespace App\Entity;

use App\Repository\ArtistHasLabelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArtistHasLabelRepository::class)]
class ArtistHasLabel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'artistHasLabels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Artist $idArtist = null;

    #[ORM\ManyToOne(inversedBy: 'artistHasLabels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Label $idLabel = null;



    public function getId(): ?int
    {
        return $this->id;
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

    public function getIdArtist(): ?Artist
    {
        return $this->idArtist;
    }

    public function setIdArtist(?Artist $idArtist): static
    {
        $this->idArtist = $idArtist;

        return $this;
    }

    public function getIdLabel(): ?Label
    {
        return $this->idLabel;
    }

    public function setIdLabel(?Label $idLabel): static
    {
        $this->idLabel = $idLabel;

        return $this;
    }
}
