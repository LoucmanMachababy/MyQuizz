<?php

namespace App\Controller;

use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request; // ✅ IMPORT MANQUANT
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuizController extends AbstractController
{
    #[Route('/quiz', name: 'quiz_global')]
    public function index(CategorieRepository $categorieRepo, Request $request): Response
    {
        $session = $request->getSession();

        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }

        $categories = $categorieRepo->findAll();

        return $this->render('quiz.html.twig', [
            'categories' => $categories,
        ]);
    }
}
