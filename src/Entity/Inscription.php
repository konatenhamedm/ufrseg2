<?php

namespace App\Entity;

use App\Repository\InscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InscriptionRepository::class)]
class Inscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(length: 255,nullable: true)]
    private ?string $montant = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    private ?NiveauEtudiant $niveauEtudiant = null;

    #[ORM\Column(length: 255)]
    private ?string $codeUtilisateur = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE,nullable: true)]
    private ?\DateTimeInterface $datePaiement = null;

    public function getId(): ?int
    {
        return $this->id;
    }



    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeInterface $dateInscription): static
    {
        $this->dateInscription = $dateInscription;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getNiveauEtudiant(): ?NiveauEtudiant
    {
        return $this->niveauEtudiant;
    }

    public function setNiveauEtudiant(?NiveauEtudiant $niveauEtudiant): static
    {
        $this->niveauEtudiant = $niveauEtudiant;

        return $this;
    }

    public function getCodeUtilisateur(): ?string
    {
        return $this->codeUtilisateur;
    }

    public function setCodeUtilisateur(string $codeUtilisateur): static
    {
        $this->codeUtilisateur = $codeUtilisateur;

        return $this;
    }

    public function getDatePaiement(): ?\DateTimeInterface
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(\DateTimeInterface $datePaiement): static
    {
        $this->datePaiement = $datePaiement;

        return $this;
    }
}
