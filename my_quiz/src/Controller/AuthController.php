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
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AuthController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
                $this->addFlash('error', 'Email ou mot de passe incorrect.');
                return $this->redirectToRoute('app_register');
            }

            // Vérifier si l'email existe déjà
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->redirectToRoute('app_register');
            }

            $user = new User();
            $user->setEmail($email);
            $user->setPassword(password_hash($password, PASSWORD_BCRYPT));

            $token = bin2hex(random_bytes(32));
            $user->setConfirmationToken($token);
            $user->setEmailConfirmed(false);

            $em->persist($user);
            $em->flush();

            $url = $this->generateUrl('app_confirm_email', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            $emailMessage = (new Email())
                ->from('no-reply@myquizz.com')
                ->to($email)
                ->subject('Confirme ton email')
                ->html("
                    <h2>Bienvenue sur MyQuiz !</h2>
                    <p>Cliquez sur le lien suivant pour confirmer votre adresse email :</p>
                    <a href='$url'>Confirmer mon email</a>
                    <p>Si le lien ne fonctionne pas, copiez cette URL dans votre navigateur : $url</p>
                ");

            $mailer->send($emailMessage);

            $this->addFlash('success', 'Inscription réussie ! Vérifiez votre email pour confirmer votre compte.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/register.html.twig');
    }

    #[Route('/confirm/{token}', name: 'app_confirm_email')]
    public function confirmEmail(string $token, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['confirmationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Lien de confirmation invalide.');
            return $this->redirectToRoute('app_login');
        }

        $user->setEmailConfirmed(true);
        $user->setConfirmationToken(null);
        $em->flush();

        $this->addFlash('success', 'Email confirmé ! Vous pouvez maintenant vous connecter.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET', 'POST'])]
    public function logout(TokenStorageInterface $tokenStorage, SessionInterface $session): Response
    {
        // Déconnexion manuelle
        $tokenStorage->setToken(null);
        $session->invalidate();
        
        return $this->redirectToRoute('app_login');
    }
}
