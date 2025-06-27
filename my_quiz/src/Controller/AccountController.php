<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Service\QuizRewardService;


class AccountController extends AbstractController
{
    #[Route('/account/email', name: 'account_email')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function changeEmail(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
   
        if ($request->isMethod('POST')) {
            $newEmail = $request->request->get('email');

            if ($newEmail && $newEmail !== $user->getEmail()) {
                $user->setEmail($newEmail);
                $user->setEmailConfirmed(false);
                $token = bin2hex(random_bytes(32));
                $user->setConfirmationToken($token);

                $em->flush();

                $url = $this->generateUrl('account_confirm_new_email', [
                'token' => $token
                ], UrlGeneratorInterface::ABSOLUTE_URL);


                $email = (new Email())
                    ->from('ne-pas-repondre@myquizz.com')
                    ->to($newEmail)
                    ->subject('Confirme ton nouveau mail')
                    ->text("Clique ici pour confirmer ton nouvel email : $url");

                $mailer->send($email);

                $this->addFlash('success', 'Un email de confirmation a été envoyé à ton nouvel email.');
            }
        }

        return $this->render('accountsettings/change_email.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/account/confirm/{token}', name: 'account_confirm_new_email')]
    public function confirmNewEmail(string $token, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['confirmationToken' => $token]);

        if (!$user) {
            return new Response('Lien invalide ou expiré.', 404);
        }

        $user->setEmailConfirmed(true);
        $user->setConfirmationToken(null);
        $em->flush();

        $this->addFlash('success', 'Ton nouvel email a bien été confirmé.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/account/password', name: 'account_password')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            $user = $this->getUser();

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                return $this->redirectToRoute('account_password');
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
                return $this->redirectToRoute('account_password');
            }

            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Le nouveau mot de passe doit contenir au moins 6 caractères.');
                return $this->redirectToRoute('account_password');
            }

            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $em->flush();

            $this->addFlash('success', 'Mot de passe modifié avec succès !');
            return $this->redirectToRoute('account_password');
        }

        return $this->render('accountsettings/change_password.html.twig');
    }

    #[Route('/account/profile', name: 'account_profile')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function profile(QuizRewardService $rewardService): Response
    {
        $user = $this->getUser();
        $badgeInfo = $rewardService->getBadgeInfo();
        
        return $this->render('accountsettings/profile.html.twig', [
            'user' => $user,
            'badgeInfo' => $badgeInfo
        ]);
    }
}
