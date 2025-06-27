<?php

namespace App\Entity;

use App\Repository\TeamQuizParticipantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamQuizParticipantRepository::class)]
class TeamQuizParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TeamQuiz $teamQuiz = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?int $score = 0;

    #[ORM\Column]
    private ?int $correctAnswers = 0;

    #[ORM\Column]
    private ?int $totalQuestions = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column]
    private ?int $duration = 0; // en secondes

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $answers = [];

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column]
    private ?int $position = 0; // Position dans le classement

    public function __construct()
    {
        $this->startedAt = new \DateTimeImmutable();
        $this->answers = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTeamQuiz(): ?TeamQuiz
    {
        return $this->teamQuiz;
    }

    public function setTeamQuiz(?TeamQuiz $teamQuiz): static
    {
        $this->teamQuiz = $teamQuiz;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;
        return $this;
    }

    public function addScore(int $points): static
    {
        $this->score += $points;
        return $this;
    }

    public function getCorrectAnswers(): ?int
    {
        return $this->correctAnswers;
    }

    public function setCorrectAnswers(int $correctAnswers): static
    {
        $this->correctAnswers = $correctAnswers;
        return $this;
    }

    public function getTotalQuestions(): ?int
    {
        return $this->totalQuestions;
    }

    public function setTotalQuestions(int $totalQuestions): static
    {
        $this->totalQuestions = $totalQuestions;
        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;
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

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getAnswers(): array
    {
        return $this->answers;
    }

    public function setAnswers(array $answers): static
    {
        $this->answers = $answers;
        return $this;
    }

    public function addAnswer(int $questionIndex, int $answerIndex): static
    {
        $this->answers[$questionIndex] = $answerIndex;
        return $this;
    }

    public function getAnswer(int $questionIndex): ?int
    {
        return $this->answers[$questionIndex] ?? null;
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

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getPercentage(): float
    {
        if ($this->totalQuestions === 0) {
            return 0.0;
        }
        return round(($this->correctAnswers / $this->totalQuestions) * 100, 2);
    }

    public function getDurationFormatted(): string
    {
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }

    public function getTimeRemaining(): int
    {
        if ($this->completedAt) {
            return 0;
        }
        
        $elapsed = time() - $this->startedAt->getTimestamp();
        $maxDuration = 300; // 5 minutes par défaut
        return max(0, $maxDuration - $elapsed);
    }

    public function isTimeUp(): bool
    {
        return $this->getTimeRemaining() <= 0;
    }
} 