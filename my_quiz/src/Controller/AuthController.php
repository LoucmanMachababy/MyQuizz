<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AuthController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $em): Response
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

            $em->persist($user);
            $em->flush();

            // Génération du lien de confirmation (affiché dans la réponse ici)
            $link = 'http://localhost:8000/confirm-email?id=' . $user->getId();
            $message = "Cliquez sur ce lien pour confirmer votre email : " . $link;
            mail($user->getEmail(), "Confirmation d'email", $message);

            return new Response("Vous êtes inscrit. Cliquez sur ce lien pour confirmer votre email : <a href='/confirm-email?id=" . $user->getId() . "'>Confirmer l'email</a>", 200);
        }

        return $this->render('auth/register.html.twig');
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

        return $this->render('auth/login.html.twig');
    }

    //lien mail pour verif
    #[Route('/confirm-email', name: 'app_confirm_email')]
    public function confirmEmail(Request $request, EntityManagerInterface $em): Response
    {
        $id = $request->query->get('id');

        if (!$id) {
            return new Response("erreur de lien", 400);
        }

        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            return new Response("Utilisateur non trouvé", 404);
        }

        $user->setEmailConfirmed(true);
        $em->flush();

        return new Response("Email confirmé: Vous pouvez vous connecté");
    }
}
