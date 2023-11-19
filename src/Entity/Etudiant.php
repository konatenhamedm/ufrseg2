<?php

namespace App\Entity;

use App\Repository\EtudiantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;

#[ORM\Entity(repositoryClass: EtudiantRepository::class)]
#[Table(name: 'user_etudiant')]
class Etudiant extends Personne
{
    #[ORM\OneToMany(mappedBy: 'etudiant', targetEntity: NiveauEtudiant::class)]
    private Collection $niveauEtudiants;

    #[ORM\OneToMany(mappedBy: 'etudiant', targetEntity: Deliberation::class, orphanRemoval: true)]
    private Collection $deliberations;


    public function __construct()
    {
        $this->niveauEtudiants = new ArrayCollection();
        $this->deliberations = new ArrayCollection();

    }

    /**
     * @return Collection<int, NiveauEtudiant>
     */
    public function getNiveauEtudiants(): Collection
    {
        return $this->niveauEtudiants;
    }

    public function addNiveauEtudiant(NiveauEtudiant $niveauEtudiant): static
    {
        if (!$this->niveauEtudiants->contains($niveauEtudiant)) {
            $this->niveauEtudiants->add($niveauEtudiant);
            $niveauEtudiant->setEtudiant($this);
        }

        return $this;
    }

    public function removeNiveauEtudiant(NiveauEtudiant $niveauEtudiant): static
    {
        if ($this->niveauEtudiants->removeElement($niveauEtudiant)) {
            // set the owning side to null (unless already changed)
            if ($niveauEtudiant->getEtudiant() === $this) {
                $niveauEtudiant->setEtudiant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Deliberation>
     */
    public function getDeliberations(): Collection
    {
        return $this->deliberations;
    }

    public function addDeliberation(Deliberation $deliberation): static
    {
        if (!$this->deliberations->contains($deliberation)) {
            $this->deliberations->add($deliberation);
            $deliberation->setEtudiant($this);
        }

        return $this;
    }

    public function removeDeliberation(Deliberation $deliberation): static
    {
        if ($this->deliberations->removeElement($deliberation)) {
            // set the owning side to null (unless already changed)
            if ($deliberation->getEtudiant() === $this) {
                $deliberation->setEtudiant(null);
            }
        }

        return $this;
    }


}
