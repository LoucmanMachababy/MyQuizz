<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AuthController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
                return new Response('Email ou mdp incorrect.', 400);
            }

            $user = new User();
            $user->setEmail($email);
            $user->setPassword(password_hash($password, PASSWORD_BCRYPT));

            // Ajout de la vérification d’email
            $token = bin2hex(random_bytes(32));
            $user->setConfirmationToken($token);
            $user->setEmailConfirmed(false);

            $em->persist($user);
            $em->flush();

            // Envoi de l’email de vérification
            $url = $this->generateUrl('app_confirm_email', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            $emailMessage = (new Email())
                ->from('no-reply@myquizz.com')
                ->to($email)
                ->subject('Confirme ton email')
                ->text("Clique ici pour confirmer ton email : $url");

            $mailer->send($emailMessage);

            return new Response('Vous êtes inscrit. Vérifiez votre email pour confirmer.');
        }

        return $this->render('auth/register.html.twig');
    }

    #[Route('/confirm/{token}', name: 'app_confirm_email')]
    public function confirmEmail(string $token, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['confirmationToken' => $token]);

        if (!$user) {
            return new Response('Lien invalide.', 404);
        }

        $user->setEmailConfirmed(true);
        $user->setConfirmationToken(null);
        $em->flush();

        return new Response('Email confirmé, vous pouvez maintenant vous connecter.');
    }

    #[Route('/login', name: 'app_login')]
    public function login(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user || !password_verify($password, $user->getPassword())) {
                return $this->render('auth/login.html.twig', [
                    'error' => 'mdp ou mail incorrects',
                ]);
            }

            if (!$user->isEmailConfirmed()) {
                return $this->render('auth/login.html.twig', [
                    'error' => 'Veuillez confirmer votre adresse email.',
                ]);
            }

            $session = $request->getSession();
            $session->set('user_id', $user->getId());

            return new Response('Connexion en cours !');
        }

        return $this->render('auth/login.html.twig', [
            'error' => null,
        ]);

    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(Request $request): Response
    {
        $session = $request->getSession();
    $session->invalidate(); 
    return $this->redirectToRoute('app_login'); 
}
}
