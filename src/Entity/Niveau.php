<?php

namespace App\Entity;

use App\Repository\NiveauRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: NiveauRepository::class)]
#[Table(name: 'param_niveau')]
#[UniqueConstraint(name: "code_niveau", fields: ["code", "filiere"])]
#[UniqueEntity(fields: ['code', 'filiere'], message: 'Ce code est déjà utilisé pour cette filière')]
class Niveau
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'Veuillez renseigner un code')]
    private ?string $code = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Veuillez renseigner un libellé')]
    private ?string $libelle = null;

    #[ORM\ManyToOne(inversedBy: 'niveaux')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Veuillez sélectionner une filière')]
    private ?Filiere $filiere = null;

    #[ORM\OneToMany(mappedBy: 'niveau', targetEntity: Frais::class, orphanRemoval: true, cascade: ['persist'])]
    #[Assert\Valid(groups: ['niveau-frais'])]
    private Collection $frais;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Veuillez sélectionner un responsable')]
    private ?Employe $responsable = null;

    #[ORM\OneToMany(mappedBy: 'niveau', targetEntity: NiveauEtudiant::class)]
    private Collection $niveauEtudiants;

    public function __construct()
    {
        $this->frais = new ArrayCollection();
        $this->niveauEtudiants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getNom(){
        return $this->libelle . ' '. $this->getFiliere()->getLibelle();
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getFiliere(): ?Filiere
    {
        return $this->filiere;
    }

    public function setFiliere(?Filiere $filiere): static
    {
        $this->filiere = $filiere;

        return $this;
    }

    /**
     * @return Collection<int, Frais>
     */
    public function getFrais(): Collection
    {
        return $this->frais;
    }

    public function addFrai(Frais $frai): static
    {
        if (!$this->frais->contains($frai)) {
            $this->frais->add($frai);
            $frai->setNiveau($this);
        }

        return $this;
    }

    public function removeFrai(Frais $frai): static
    {
        if ($this->frais->removeElement($frai)) {
            // set the owning side to null (unless already changed)
            if ($frai->getNiveau() === $this) {
                $frai->setNiveau(null);
            }
        }

        return $this;
    }

    public function getResponsable(): ?Employe
    {
        return $this->responsable;
    }

    public function setResponsable(?Employe $responsable): static
    {
        $this->responsable = $responsable;

        return $this;
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
            $niveauEtudiant->setNiveau($this);
        }

        return $this;
    }

    public function removeNiveauEtudiant(NiveauEtudiant $niveauEtudiant): static
    {
        if ($this->niveauEtudiants->removeElement($niveauEtudiant)) {
            // set the owning side to null (unless already changed)
            if ($niveauEtudiant->getNiveau() === $this) {
                $niveauEtudiant->setNiveau(null);
            }
        }

        return $this;
    }
}
