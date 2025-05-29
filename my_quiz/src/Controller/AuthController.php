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

            // gestion derreur
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
                return new Response('Email ou mdp incorrect.', 400);
            }

            $user = new User();
            $user->setEmail($email);
            $user->setPassword(password_hash($password, PASSWORD_BCRYPT)); //bcrypt pr haser le mdp

            $em->persist($user);
            $em->flush();

            return new Response('Vous etes inscris');
        }

       
    }
}
