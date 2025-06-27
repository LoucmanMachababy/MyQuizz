<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\QuizHistory;
use Symfony\Component\HttpFoundation\RequestStack;

class NotificationService
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Envoyer une notification de nouveau badge
     */
    public function sendBadgeNotification(User $user, string $badgeKey): void
    {
        $badgeInfo = [
            'perfect_score' => '🏆 Score Parfait',
            'excellent_score' => '⭐ Excellent',
            'first_quiz' => '🎯 Premier Pas',
            'quiz_enthusiast' => '🔥 Passionné',
            'quiz_master' => '👑 Maître du Quiz',
            'quiz_legend' => '💎 Légende'
        ];

        $badgeName = $badgeInfo[$badgeKey] ?? 'Nouveau Badge';
        
        $this->addFlashNotification(
            'success',
            "Félicitations ! Vous avez obtenu le badge {$badgeName} !"
        );
    }

    /**
     * Envoyer une notification de score élevé
     */
    public function sendHighScoreNotification(QuizHistory $quizHistory): void
    {
        $percentage = $quizHistory->getPercentage();
        
        if ($percentage >= 90) {
            $this->addFlashNotification(
                'success',
                "🎉 Score exceptionnel ! {$percentage}% - Continuez comme ça !"
            );
        } elseif ($percentage >= 80) {
            $this->addFlashNotification(
                'info',
                "👍 Bon score ! {$percentage}% - Vous progressez bien !"
            );
        }
    }

    /**
     * Envoyer une notification de niveau atteint
     */
    public function sendLevelUpNotification(User $user): void
    {
        $quizzesCompleted = $user->getQuizzesCompleted();
        
        if ($quizzesCompleted === 10) {
            $this->addFlashNotification(
                'success',
                "🎊 Niveau 10 atteint ! Vous êtes maintenant un passionné de quiz !"
            );
        } elseif ($quizzesCompleted === 50) {
            $this->addFlashNotification(
                'success',
                "🏆 Niveau 50 atteint ! Vous êtes un maître du quiz !"
            );
        } elseif ($quizzesCompleted === 100) {
            $this->addFlashNotification(
                'success',
                "💎 Niveau 100 atteint ! Vous êtes une légende du quiz !"
            );
        }
    }

    /**
     * Envoyer une notification de classement
     */
    public function sendLeaderboardNotification(User $user, int $position): void
    {
        if ($position <= 3) {
            $medals = ['🥇', '🥈', '🥉'];
            $medal = $medals[$position - 1] ?? '';
            
            $this->addFlashNotification(
                'success',
                "{$medal} Félicitations ! Vous êtes {$position}ème au classement !"
            );
        }
    }

    /**
     * Envoyer une notification de défi
     */
    public function sendChallengeNotification(string $challengeType): void
    {
        $messages = [
            'daily' => '🎯 Nouveau défi quotidien disponible !',
            'weekly' => '📅 Défi hebdomadaire à relever !',
            'special' => '🌟 Défi spécial en cours !'
        ];

        $message = $messages[$challengeType] ?? 'Nouveau défi disponible !';
        
        $this->addFlashNotification('info', $message);
    }

    /**
     * Envoyer une notification de maintenance
     */
    public function sendMaintenanceNotification(string $message): void
    {
        $this->addFlashNotification('warning', "🔧 Maintenance : {$message}");
    }

    /**
     * Envoyer une notification d'erreur
     */
    public function sendErrorNotification(string $message): void
    {
        $this->addFlashNotification('error', "❌ Erreur : {$message}");
    }

    /**
     * Ajouter une notification flash
     */
    private function addFlashNotification(string $type, string $message): void
    {
        $session = $this->requestStack->getSession();
        $session->getFlashBag()->add($type, $message);
    }

    /**
     * Envoyer une notification push (si supporté)
     */
    public function sendPushNotification(string $title, string $message, array $options = []): void
    {
        // Cette fonction serait utilisée avec un service de push notifications
        // comme Firebase Cloud Messaging ou OneSignal
        $defaultOptions = [
            'icon' => '/favicon.ico',
            'badge' => '/favicon.ico',
            'vibrate' => [100, 50, 100],
            'data' => [
                'dateOfArrival' => time(),
                'primaryKey' => 1
            ],
            'actions' => [
                [
                    'action' => 'explore',
                    'title' => 'Voir',
                    'icon' => '/favicon.ico'
                ],
                [
                    'action' => 'close',
                    'title' => 'Fermer',
                    'icon' => '/favicon.ico'
                ]
            ]
        ];

        $finalOptions = array_merge($defaultOptions, $options);
        
        // Ici, vous intégreriez votre service de push notifications
        // Par exemple avec OneSignal ou Firebase
    }

    /**
     * Vérifier si les notifications sont supportées
     */
    public function isNotificationSupported(): bool
    {
        // Cette méthode vérifierait si les notifications sont supportées côté client
        // Pour l'instant, on retourne true par défaut
        return true;
    }

    /**
     * Demander la permission pour les notifications
     */
    public function requestNotificationPermission(): void
    {
        // Cette méthode demanderait la permission côté client
        // Pour l'instant, c'est géré côté JavaScript
    }
} 