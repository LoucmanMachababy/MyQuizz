<?php

namespace App\Entity;

use App\Repository\ChallengeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChallengeRepository::class)]
class Challenge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $challenger = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $challenged = null;

    #[ORM\Column]
    private ?int $maxScore = 0;

    #[ORM\Column]
    private ?int $timeLimit = 300; // en secondes

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $category = null;

    #[ORM\Column]
    private ?string $difficulty = 'medium';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $questions = [];

    #[ORM\Column]
    private ?int $questionCount = 10;

    #[ORM\OneToMany(mappedBy: 'challenge', targetEntity: ChallengeResult::class, orphanRemoval: true)]
    private Collection $results;

    #[ORM\Column]
    private ?bool $isAccepted = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $acceptedAt = null;

    #[ORM\Column]
    private ?bool $isCompleted = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column]
    private ?string $status = 'pending'; // pending, accepted, completed, expired

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = (new \DateTimeImmutable())->modify('+7 days');
        $this->results = new ArrayCollection();
        $this->questions = [];
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
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

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getChallenger(): ?User
    {
        return $this->challenger;
    }

    public function setChallenger(?User $challenger): static
    {
        $this->challenger = $challenger;
        return $this;
    }

    public function getChallenged(): ?User
    {
        return $this->challenged;
    }

    public function setChallenged(?User $challenged): static
    {
        $this->challenged = $challenged;
        return $this;
    }

    public function getMaxScore(): ?int
    {
        return $this->maxScore;
    }

    public function setMaxScore(int $maxScore): static
    {
        $this->maxScore = $maxScore;
        return $this;
    }

    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    public function setTimeLimit(int $timeLimit): static
    {
        $this->timeLimit = $timeLimit;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getDifficulty(): ?string
    {
        return $this->difficulty;
    }

    public function setDifficulty(string $difficulty): static
    {
        $this->difficulty = $difficulty;
        return $this;
    }

    public function getQuestions(): array
    {
        return $this->questions;
    }

    public function setQuestions(array $questions): static
    {
        $this->questions = $questions;
        return $this;
    }

    public function getQuestionCount(): ?int
    {
        return $this->questionCount;
    }

    public function setQuestionCount(int $questionCount): static
    {
        $this->questionCount = $questionCount;
        return $this;
    }

    /**
     * @return Collection<int, ChallengeResult>
     */
    public function getResults(): Collection
    {
        return $this->results;
    }

    public function addResult(ChallengeResult $result): static
    {
        if (!$this->results->contains($result)) {
            $this->results->add($result);
            $result->setChallenge($this);
        }
        return $this;
    }

    public function removeResult(ChallengeResult $result): static
    {
        if ($this->results->removeElement($result)) {
            if ($result->getChallenge() === $this) {
                $result->setChallenge(null);
            }
        }
        return $this;
    }

    public function isAccepted(): ?bool
    {
        return $this->isAccepted;
    }

    public function setIsAccepted(bool $isAccepted): static
    {
        $this->isAccepted = $isAccepted;
        return $this;
    }

    public function getAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(?\DateTimeImmutable $acceptedAt): static
    {
        $this->acceptedAt = $acceptedAt;
        return $this;
    }

    public function isCompleted(): ?bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): static
    {
        $this->isCompleted = $isCompleted;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function getTimeRemaining(): int
    {
        $remaining = $this->expiresAt->getTimestamp() - time();
        return max(0, $remaining);
    }

    public function getTimeLimitFormatted(): string
    {
        $minutes = floor($this->timeLimit / 60);
        $seconds = $this->timeLimit % 60;
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function getChallengerResult(): ?ChallengeResult
    {
        foreach ($this->results as $result) {
            if ($result->getUser() === $this->challenger) {
                return $result;
            }
        }
        return null;
    }

    public function getChallengedResult(): ?ChallengeResult
    {
        foreach ($this->results as $result) {
            if ($result->getUser() === $this->challenged) {
                return $result;
            }
        }
        return null;
    }

    public function getWinner(): ?User
    {
        $challengerResult = $this->getChallengerResult();
        $challengedResult = $this->getChallengedResult();

        if (!$challengerResult || !$challengedResult) {
            return null;
        }

        if ($challengerResult->getScore() > $challengedResult->getScore()) {
            return $this->challenger;
        } elseif ($challengedResult->getScore() > $challengerResult->getScore()) {
            return $this->challenged;
        }

        // En cas d'égalité, le plus rapide gagne
        if ($challengerResult->getDuration() < $challengedResult->getDuration()) {
            return $this->challenger;
        } elseif ($challengedResult->getDuration() < $challengerResult->getDuration()) {
            return $this->challenged;
        }

        return null; // Égalité parfaite
    }

    public function accept(): static
    {
        $this->isAccepted = true;
        $this->acceptedAt = new \DateTimeImmutable();
        $this->status = 'accepted';
        return $this;
    }

    public function complete(): static
    {
        $this->isCompleted = true;
        $this->completedAt = new \DateTimeImmutable();
        $this->status = 'completed';
        return $this;
    }

    public function expire(): static
    {
        $this->isActive = false;
        $this->status = 'expired';
        return $this;
    }
} 