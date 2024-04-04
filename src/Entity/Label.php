<?php

namespace App\Entity;

use App\Repository\LabelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LabelRepository::class)]
class Label
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $nom = null;

    #[ORM\OneToMany(targetEntity: ArtistHasLabel::class, mappedBy: 'idLabel')]
    private Collection $artistHasLabels;

    public function __construct()
    {
        $this->artistHasLabels = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
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
            $artistHasLabel->setIdLabel($this);
        }

        return $this;
    }

    public function removeArtistHasLabel(ArtistHasLabel $artistHasLabel): static
    {
        if ($this->artistHasLabels->removeElement($artistHasLabel)) {
            // set the owning side to null (unless already changed)
            if ($artistHasLabel->getIdLabel() === $this) {
                $artistHasLabel->setIdLabel(null);
            }
        }

        return $this;
    }
}
