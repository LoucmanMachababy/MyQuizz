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

               