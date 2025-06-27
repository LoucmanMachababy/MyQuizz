<?php

namespace App\Service;

use App\Entity\Challenge;
use App\Entity\Team;
use App\Entity\TeamQuiz;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MultiplayerNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function notifyChallengeReceived(User $challenged, Challenge $challenge): void
    {
        $email = (new Email())
            ->from('noreply@myquizz.com')
            ->to($challenged->getEmail())
            ->subject('Nouveau défi reçu !')
            ->html($this->renderChallengeEmail($challenged, $challenge));

        $this->mailer->send($email);
    }

    public function notifyChallengeAccepted(User $challenger, Challenge $challenge): void
    {
        $email = (new Email())
            ->from('noreply@myquizz.com')
            ->to($challenger->getEmail())
            ->subject('Votre défi a été accepté !')
            ->html($this->renderChallengeAcceptedEmail($challenger, $challenge));

        $this->mailer->send($email);
    }

    public function notifyChallengeCompleted(User $winner, User $loser, Challenge $challenge): void
    {
        // Email au gagnant
        $winnerEmail = (new Email())
            ->from('noreply@myquizz.com')
            ->to($winner->getEmail())
            ->subject('Félicitations ! Vous avez gagné le défi !')
            ->html($this->renderChallengeWinnerEmail($winner, $challenge));

        // Email au perdant
        $loserEmail = (new Email())
            ->from('noreply@myquizz.com')
            ->to($loser->getEmail())
            ->subject('Défi terminé - Continuez à vous améliorer !')
            ->html($this->renderChallengeLoserEmail($loser, $challenge));

        $this->mailer->send($winnerEmail);
        $this->mailer->send($loserEmail);
    }

    public function notifyTeamQuizCreated(Team $team, TeamQuiz $quiz): void
    {
        foreach ($team->getMembers() as $member) {
            $email = (new Email())
                ->from('noreply@myquizz.com')
                ->to($member->getEmail())
                ->subject('Nouveau quiz d\'équipe disponible !')
                ->html($this->renderTeamQuizEmail($member, $team, $quiz));

            $this->mailer->send($email);
        }

        // Email au propriétaire aussi
        $ownerEmail = (new Email())
            ->from('noreply@myquizz.com')
            ->to($team->getOwner()->getEmail())
            ->subject('Quiz d\'équipe créé avec succès !')
            ->html($this->renderTeamQuizCreatedEmail($team->getOwner(), $team, $quiz));

        $this->mailer->send($ownerEmail);
    }

    public function notifyTeamMemberJoined(Team $team, User $newMember): void
    {
        $email = (new Email())
            ->from('noreply@myquizz.com')
            ->to($team->getOwner()->getEmail())
            ->subject('Nouveau membre dans votre équipe !')
            ->html($this->renderTeamMemberJoinedEmail($team, $newMember));

        $this->mailer->send($email);
    }

    private function renderChallengeEmail(User $user, Challenge $challenge): string
    {
        $challengeUrl = $this->urlGenerator->generate('challenge_show', ['id' => $challenge->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #667eea;'>🎯 Nouveau défi reçu !</h2>
            <p>Bonjour {$user->getEmail()},</p>
            <p><strong>{$challenge->getChallenger()->getEmail()}</strong> vous a lancé un défi !</p>
            
            <div style='background: #f8fafc; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                <h3>{$challenge->getTitle()}</h3>
                <p>{$challenge->getDescription()}</p>
                <ul>
                    <li><strong>Catégorie:</strong> {$challenge->getCategory()}</li>
                    <li><strong>Difficulté:</strong> {$challenge->getDifficulty()}</li>
                    <li><strong>Questions:</strong> {$challenge->getQuestionCount()}</li>
                    <li><strong>Temps limite:</strong> {$challenge->getTimeLimitFormatted()}</li>
                </ul>
            </div>
            
            <p>Vous avez 7 jours pour accepter ce défi.</p>
            
            <a href='{$challengeUrl}' style='background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>
                Voir le défi
            </a>
        </div>";
    }

    private function renderChallengeAcceptedEmail(User $user, Challenge $challenge): string
    {
        $challengeUrl = $this->urlGenerator->generate('challenge_show', ['id' => $challenge->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #10b981;'>✅ Défi accepté !</h2>
            <p>Bonjour {$user->getEmail()},</p>
            <p>Votre défi <strong>{$challenge->getTitle()}</strong> a été accepté par <strong>{$challenge->getChallenged()->getEmail()}</strong> !</p>
            
            <p>Le défi peut maintenant commencer. Bonne chance !</p>
            
            <a href='{$challengeUrl}' style='background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>
                Voir le défi
            </a>
        </div>";
    }

    private function renderChallengeWinnerEmail(User $user, Challenge $challenge): string
    {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #f59e0b;'>🏆 Félicitations !</h2>
            <p>Bonjour {$user->getEmail()},</p>
            <p>Vous avez gagné le défi <strong>{$challenge->getTitle()}</strong> !</p>
            
            <div style='background: #fef3c7; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center;'>
                <h3>🎉 Victoire !</h3>
                <p>Vous avez prouvé votre excellence dans ce défi.</p>
            </div>
            
            <p>Continuez à vous améliorer et lancez de nouveaux défis !</p>
        </div>";
    }

    private function renderChallengeLoserEmail(User $user, Challenge $challenge): string
    {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #667eea;'>💪 Défi terminé</h2>
            <p>Bonjour {$user->getEmail()},</p>
            <p>Le défi <strong>{$challenge->getTitle()}</strong> est terminé.</p>
            
            <div style='background: #f8fafc; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center;'>
                <h3>📈 Continuez à vous améliorer !</h3>
                <p>Chaque défi est une opportunité d'apprendre et de progresser.</p>
            </div>
            
            <p>N'abandonnez pas, la prochaine fois sera la bonne !</p>
        </div>";
    }

    private function renderTeamQuizEmail(User $user, Team $team, TeamQuiz $quiz): string
    {
        $quizUrl = $this->urlGenerator->generate('team_quiz_play', ['id' => $team->getId(), 'quizId' => $quiz->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #667eea;'>🎮 Nouveau quiz d'équipe !</h2>
            <p>Bonjour {$user->getEmail()},</p>
            <p>Un nouveau quiz a été créé dans votre équipe <strong>{$team->getName()}</strong> !</p>
            
            <div style='background: #f8fafc; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                <h3>{$quiz->getTitle()}</h3>
                <p>{$quiz->getDescription()}</p>
                <ul>
                    <li><strong>Questions:</strong> {$quiz->getQuestionCount()}</li>
                    <li><strong>Difficulté:</strong> {$quiz->getDifficulty()}</li>
                    <li><strong>Créé par:</strong> {$quiz->getCreatedBy()->getEmail()}</li>
                </ul>
            </div>
            
            <a href='{$quizUrl}' style='background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>
                Jouer au quiz
            </a>
        </div>";
    }

    private function renderTeamQuizCreatedEmail(User $user, Team $team, TeamQuiz $quiz): string
    {
        $teamUrl = $this->urlGenerator->generate('team_show', ['id' => $team->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #10b981;'>✅ Quiz créé avec succès !</h2>
            <p>Bonjour {$user->getEmail()},</p>
            <p>Votre quiz <strong>{$quiz->getTitle()}</strong> a été créé dans l'équipe <strong>{$team->getName()}</strong> !</p>
            
            <p>Tous les membres de votre équipe ont été notifiés et peuvent maintenant participer.</p>
            
            <a href='{$teamUrl}' style='background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>
                Voir l'équipe
            </a>
        </div>";
    }

    private function renderTeamMemberJoinedEmail(Team $team, User $newMember): string
    {
        $teamUrl = $this->urlGenerator->generate('team_show', ['id' => $team->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #10b981;'>👋 Nouveau membre !</h2>
            <p>Bonjour {$team->getOwner()->getEmail()},</p>
            <p><strong>{$newMember->getEmail()}</strong> a rejoint votre équipe <strong>{$team->getName()}</strong> !</p>
            
            <p>Votre équipe compte maintenant {$team->getMemberCount()} membres.</p>
            
            <a href='{$teamUrl}' style='background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>
                Voir l'équipe
            </a>
        </div>";
    }
} 