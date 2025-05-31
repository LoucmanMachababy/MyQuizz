<?php

// src/Controller/QuizController.php

namespace App\Controller;

use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuizController extends AbstractController
{
    #[Route('/quiz', name: 'quiz_global')]
    public function index(CategorieRepository $categorieRepo): Response
    {
        $categories = $categorieRepo->findAll();

        return $this->render('quiz.html.twig', [
            'categories' => $categories,
        ]);

        
    }
}
