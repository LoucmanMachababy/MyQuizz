<?php

namespace App\Entity;

use App\Repository\ReponseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReponseRepository::class)]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?string $texte = null;

    #[ORM\Column]
    private ?bool $estCorrecte = null;

    #[ORM\ManyToOne(inversedBy: 'reponses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Question $question = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isTexte(): ?bool
    {
        return $this->texte;
    }

    public function setTexte(bool $texte): static
    {
        $this->texte = $texte;

        return $this;
    }

    public function isEstCorrecte(): ?bool
    {
        return $this->estCorrecte;
    }

    public function setEstCorrecte(bool $estCorrecte): static
    {
        $this->estCorrecte = $estCorrecte;

        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): static
    {
        $this->question = $question;

        return $this;
    }
}
