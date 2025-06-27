<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: "App\Repository\UserRepository")]
#[ORM\Table(name: "user")]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\Column(type: "string", length: 255, unique: true)]
    private $email;

    #[ORM\Column(type: "string", length: 255)]
    private $password;

    #[ORM\Column(type: "boolean", name: "email_confirmed")]
    private $emailConfirmed = false;

    #[ORM\Column(type: "string", length: 64, nullable: true)]
    private $confirmationToken;

    #[ORM\Column(type: "json")]
    private array $roles = [];

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column(type: "datetime")]
    private $createdAt;

    #[ORM\Column(type: "boolean")]
    private $isActive = true;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $points = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $quizzesCompleted = 0;

    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $badges = [];

    // Relations multijoueur
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Team::class, orphanRemoval: true)]
    private Collection $ownedTeams;

    #[ORM\ManyToMany(targetEntity: Team::class, inversedBy: 'members')]
    private Collection $teams;

    #[ORM\OneToMany(mappedBy: 'challenger', targetEntity: Challenge::class, orphanRemoval: true)]
    private Collection $sentChallenges;

    #[ORM\OneToMany(mappedBy: 'challenged', targetEntity: Challenge::class, orphanRemoval: true)]
    private Collection $receivedChallenges;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ChallengeResult::class, orphanRemoval: true)]
    private Collection $challengeResults;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: TeamQuizParticipant::class, orphanRemoval: true)]
    private Collection $teamQuizParticipations;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->ownedTeams = new ArrayCollection();
        $this->teams = new ArrayCollection();
        $this->sentChallenges = new ArrayCollection();
        $this->receivedChallenges = new ArrayCollection();
        $this->challengeResults = new ArrayCollection();
        $this->teamQuizParticipations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function isEmailConfirmed(): bool
    {
        return $this->emailConfirmed;
    }

    public function setEmailConfirmed(bool $confirmed): self
    {
        $this->emailConfirmed = $confirmed;
        return $this;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(?string $token): self
    {
        $this->confirmationToken = $token;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->getRoles());
    }

    public function getPoints(): int
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

    public function getQuizzesCompleted(): int
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

    public function getBadges(): array
    {
        return $this->badges;
    }

    public function setBadges(array $badges): static
    {
        $this->badges = $badges;
        return $this;
    }

    public function addBadge(string $badge): static
    {
        if (!in_array($badge, $this->badges)) {
            $this->badges[] = $badge;
        }
        return $this;
    }

    public function hasBadge(string $badge): bool
    {
        return in_array($badge, $this->badges);
    }

    public function getOwnedTeams(): Collection
    {
        return $this->ownedTeams;
    }

    public function addOwnedTeam(Team $team): static
    {
        if (!$this->ownedTeams->contains($team)) {
            $this->ownedTeams->add($team);
            $team->setOwner($this);
        }
        return $this;
    }

    public function removeOwnedTeam(Team $team): static
    {
        if ($this->ownedTeams->removeElement($team)) {
            if ($team->getOwner() === $this) {
                $team->setOwner(null);
            }
        }
        return $this;
    }

    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): static
    {
        if (!$this->teams->contains($team)) {
            $this->teams->add($team);
        }
        return $this;
    }

    public function removeTeam(Team $team): static
    {
        $this->teams->removeElement($team);
        return $this;
    }

    public function getSentChallenges(): Collection
    {
        return $this->sentChallenges;
    }

    public function addSentChallenge(Challenge $challenge): static
    {
        if (!$this->sentChallenges->contains($challenge)) {
            $this->sentChallenges->add($challenge);
            $challenge->setChallenger($this);
        }
        return $this;
    }

    public function removeSentChallenge(Challenge $challenge): static
    {
        if ($this->sentChallenges->removeElement($challenge)) {
            if ($challenge->getChallenger() === $this) {
                $challenge->setChallenger(null);
            }
        }
        return $this;
    }

    public function getReceivedChallenges(): Collection
    {
        return $this->receivedChallenges;
    }

    public function addReceivedChallenge(Challenge $challenge): static
    {
        if (!$this->receivedChallenges->contains($challenge)) {
            $this->receivedChallenges->add($challenge);
            $challenge->setChallenged($this);
        }
        return $this;
    }

    public function removeReceivedChallenge(Challenge $challenge): static
    {
        if ($this->receivedChallenges->removeElement($challenge)) {
            if ($challenge->getChallenged() === $this) {
                $challenge->setChallenged(null);
            }
        }
        return $this;
    }

    public function getChallengeResults(): Collection
    {
        return $this->challengeResults;
    }

    public function addChallengeResult(ChallengeResult $result): static
    {
        if (!$this->challengeResults->contains($result)) {
            $this->challengeResults->add($result);
            $result->setUser($this);
        }
        return $this;
    }

    public function removeChallengeResult(ChallengeResult $result): static
    {
        if ($this->challengeResults->removeElement($result)) {
            if ($result->getUser() === $this) {
                $result->setUser(null);
            }
        }
        return $this;
    }

    public function getTeamQuizParticipations(): Collection
    {
        return $this->teamQuizParticipations;
    }

    public function addTeamQuizParticipation(TeamQuizParticipant $participation): static
    {
        if (!$this->teamQuizParticipations->contains($participation)) {
            $this->teamQuizParticipations->add($participation);
            $participation->setUser($this);
        }
        return $this;
    }

    public function removeTeamQuizParticipation(TeamQuizParticipant $participation): static
    {
        if ($this->teamQuizParticipations->removeElement($participation)) {
            if ($participation->getUser() === $this) {
                $participation->setUser(null);
            }
        }
        return $this;
    }

    public function getActiveChallenges(): array
    {
        $activeChallenges = [];
        
        foreach ($this->sentChallenges as $challenge) {
            if ($challenge->isActive() && !$challenge->isExpired()) {
                $activeChallenges[] = $challenge;
            }
        }
        
        foreach ($this->receivedChallenges as $challenge) {
            if ($challenge->isActive() && !$challenge->isExpired()) {
                $activeChallenges[] = $challenge;
            }
        }
        
        return $activeChallenges;
    }

    public function getPendingChallenges(): array
    {
        $pendingChallenges = [];
        
        foreach ($this->receivedChallenges as $challenge) {
            if ($challenge->isActive() && !$challenge->isAccepted() && !$challenge->isExpired()) {
                $pendingChallenges[] = $challenge;
            }
        }
        
        return $pendingChallenges;
    }

    public function getWonChallenges(): int
    {
        $wonCount = 0;
        
        foreach ($this->challengeResults as $result) {
            if ($result->isWinner()) {
                $wonCount++;
            }
        }
        
        return $wonCount;
    }

    public function getTotalChallenges(): int
    {
        return $this->challengeResults->count();
    }

    public function getWinRate(): float
    {
        $total = $this->getTotalChallenges();
        if ($total === 0) {
            return 0.0;
        }
        
        return round(($this->getWonChallenges() / $total) * 100, 2);
    }
}
