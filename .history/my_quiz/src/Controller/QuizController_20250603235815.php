<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Question;
use App\Entity\Reponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class QuizController extends AbstractController
{
    #[Route('/quiz', name: 'quiz_global')]
    public function index(\App\Repository\CategorieRepository $categorieRepo)
    {
        $categories = $categorieRepo->findAll();

        return $this->render('quiz.html.twig', [
            'categories' => $categories,
        ]);
    }

    
}
