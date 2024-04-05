<?php

namespace App\Entity;

use App\Repository\ArtistHasLabelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArtistHasLabelRepository::class)]
class ArtistHasLabel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'artistHasLabels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Artist $idArtist = null;

    #[ORM\ManyToOne(inversedBy: 'artistHasLabels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Label $idLabel = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $entrydate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $issuedate = null;

    public function __construct()
    {
        $this->entrydate = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEntrydate(): ?\DateTimeInterface
    {
        return $this->entrydate;
    }

    public function setEntrydate(\DateTimeInterface $entrydate): static
    {
        $this->entrydate = $entrydate;

        return $this;
    }

    public function getIssuedate(): ?\DateTimeInterface
    {
        return $this->issuedate;
    }

    public function setIssuedate(?\DateTimeInterface $issuedate): static
    {
        $this->issuedate = $issuedate;

        return $this;
    }
}
