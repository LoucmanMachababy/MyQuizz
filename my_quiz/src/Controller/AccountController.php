<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

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
                $token = bin2hex(random_bytes(32));                $user->setConfirmationToken($token);

                $em->flush();

                $email = (new Email())
                    ->from('ne-pas-repondre@myquizz.com')
                    ->to($newEmail)
                    ->subject('Confirme ton nvx email')
                    ->text("Clique ici pour confirmer ton nouvel email : http://localhost:8000/confirm/" . $token);

                $mailer->send($email);

                $this->addFlash('success', 'Un email de confirmation a été envoyé.');
            }
        }

        return $this->render('account/change_email.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/account/password', name: 'account_password')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function changePassword(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            if ($password) {
                $user->setPassword($hasher->hashPassword($user, $password));
                $em->flush();
                $this->addFlash('success', 'Mot de passe modifié avec succès.');
            }
        }

        return $this->render('account/change_password.html.twig');
    }
}
