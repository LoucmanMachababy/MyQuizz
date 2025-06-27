<?php

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column]
    private ?int $points = 0;

    #[ORM\Column]
    private ?int $quizzesCompleted = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'ownedTeams')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'teams')]
    private Collection $members;

    #[ORM\OneToMany(mappedBy: 'team', targetEntity: TeamQuiz::class, orphanRemoval: true)]
    private Collection $teamQuizzes;

    #[ORM\Column]
    private ?bool $isPublic = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $inviteCode = null;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->teamQuizzes = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->inviteCode = $this->generateInviteCode();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
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

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;
        return $this;
    }

    public function addPoints(int $points): static
    {
        $this->points += $points;
        return $this;
    }

    public function getQuizzesCompleted(): ?int
    {
        return $this->quizzesCompleted;
    }

    public function setQuizzesCompleted(int $quizzesCompleted): static
    {
        $this->quizzesCompleted = $quizzesCompleted;
        return $this;
    }

    public function incrementQuizzesCompleted(): static
    {
        $this->quizzesCompleted++;
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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(User $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
        }
        return $this;
    }

    public function removeMember(User $member): static
    {
        $this->members->removeElement($member);
        return $this;
    }

    public function hasMember(User $user): bool
    {
        return $this->members->contains($user) || $this->owner === $user;
    }

    public function getMemberCount(): int
    {
        return $this->members->count() + 1; // +1 pour l'owner
    }

    /**
     * @return Collection<int, TeamQuiz>
     */
    public function getTeamQuizzes(): Collection
    {
        return $this->teamQuizzes;
    }

    public function addTeamQuiz(TeamQuiz $teamQuiz): static
    {
        if (!$this->teamQuizzes->contains($teamQuiz)) {
            $this->teamQuizzes->add($teamQuiz);
            $teamQuiz->setTeam($this);
        }
        return $this;
    }

    public function removeTeamQuiz(TeamQuiz $teamQuiz): static
    {
        if ($this->teamQuizzes->removeElement($teamQuiz)) {
            if ($teamQuiz->getTeam() === $this) {
                $teamQuiz->setTeam(null);
            }
        }
        return $this;
    }

    public function isPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function getInviteCode(): ?string
    {
        return $this->inviteCode;
    }

    public function setInviteCode(?string $inviteCode): static
    {
        $this->inviteCode = $inviteCode;
        return $this;
    }

    private function generateInviteCode(): string
    {
        return strtoupper(substr(md5(uniqid()), 0, 8));
    }

    public function getAverageScore(): float
    {
        if ($this->teamQuizzes->isEmpty()) {
            return 0.0;
        }

        $totalScore = 0;
        foreach ($this->teamQuizzes as $teamQuiz) {
            $totalScore += $teamQuiz->getScore();
        }

        return round($totalScore / $this->teamQuizzes->count(), 2);
    }
} 