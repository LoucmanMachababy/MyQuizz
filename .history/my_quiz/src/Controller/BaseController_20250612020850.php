<?php

// src/Controller/BaseController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class BaseController extends AbstractController
{
    protected function getLoggedUser(Request $request, EntityManagerInterface $em): ?User
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');

        if (!$userId) {
            return null;
        }

        return $em->getRepository(User::class)->find($userId);
    }
}
