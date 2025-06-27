<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\QuizHistory;
use Doctrine\ORM\EntityManagerInterface;

class QuizRewardService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function processQuizCompletion(User $user, QuizHistory $quizHistory): void
    {
        $score = $quizHistory->getScore();
        $totalQuestions = $quizHistory->getTotalQuestions();
        $percentage = ($score / $totalQuestions) * 100;

        // Calculer les points basés sur le score
        $points = $this->calculatePoints($score, $totalQuestions, $percentage);
        
        // Ajouter les points à l'utilisateur
        $user->addPoints($points);
        $user->incrementQuizzesCompleted();

        // Vérifier et attribuer les badges
        $this->checkAndAwardBadges($user, $quizHistory);

        $this->em->flush();
    }

    private function calculatePoints(int $score, int $totalQuestions, float $percentage): int
    {
        $basePoints = $score * 10; // 10 points par bonne réponse
        
        // Bonus pour le pourcentage
        if ($percentage >= 90) {
            $basePoints += 50; // Bonus parfait
        } elseif ($percentage >= 80) {
            $basePoints += 30; // Bonus excellent
        } elseif ($percentage >= 70) {
            $basePoints += 15; // Bonus bon
        }

        // Bonus pour le nombre de questions
        if ($totalQuestions >= 20) {
            $basePoints += 20; // Bonus quiz long
        }

        return $basePoints;
    }

    private function checkAndAwardBadges(User $user, QuizHistory $quizHistory): void
    {
        $score = $quizHistory->getScore();
        $totalQuestions = $quizHistory->getTotalQuestions();
        $percentage = ($score / $totalQuestions) * 100;
        $quizzesCompleted = $user->getQuizzesCompleted();

        // Badge pour un score parfait
        if ($percentage == 100) {
            $user->addBadge('perfect_score');
        }

        // Badge pour un score excellent
        if ($percentage >= 90) {
            $user->addBadge('excellent_score');
        }

        // Badge pour le premier quiz
        if ($quizzesCompleted == 1) {
            $user->addBadge('first_quiz');
        }

        // Badge pour 10 quiz complétés
        if ($quizzesCompleted == 10) {
            $user->addBadge('quiz_enthusiast');
        }

        // Badge pour 50 quiz complétés
        if ($quizzesCompleted == 50) {
            $user->addBadge('quiz_master');
        }

        // Badge pour 100 quiz complétés
        if ($quizzesCompleted == 100) {
            $user->addBadge('quiz_legend');
        }
    }

    public function getBadgeInfo(): array
    {
        return [
            'perfect_score' => [
                'name' => 'Score Parfait',
                'description' => 'Obtenez 100% à un quiz',
                'icon' => '🏆'
            ],
            'excellent_score' => [
                'name' => 'Excellent',
                'description' => 'Obtenez au moins 90% à un quiz',
                'icon' => '⭐'
            ],
            'first_quiz' => [
                'name' => 'Premier Pas',
                'description' => 'Complétez votre premier quiz',
                'icon' => '🎯'
            ],
            'quiz_enthusiast' => [
                'name' => 'Passionné',
                'description' => 'Complétez 10 quiz',
                'icon' => '🔥'
            ],
            'quiz_master' => [
                'name' => 'Maître du Quiz',
                'description' => 'Complétez 50 quiz',
                'icon' => '👑'
            ],
            'quiz_legend' => [
                'name' => 'Légende',
                'description' => 'Complétez 100 quiz',
                'icon' => '💎'
            ]
        ];
    }
} 