<?php

namespace App\Controller;

use App\Entity\Categorie; 
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

    #[Route('/quiz/{id}', name: 'quiz_categorie')]
public function quizCategorie(Categorie $categorie, Request $request, SessionInterface $session)
{
    $questions = $categorie->getQuestions()->getValues();

    $currentIndex = $session->get('quiz_index_' . $categorie->getId(), 0);

    if ($request->isMethod('POST')) {
        $currentIndex++;
        $session->set('quiz_index_' . $categorie->getId(), $currentIndex);

        return $this->redirectToRoute('quiz_categorie', ['id' => $categorie->getId()]);
    }

    if ($currentIndex >= count($questions)) {
        $session->remove('quiz_index_' . $categorie->getId());
        return $this->render('quiz/finished.html.twig', [
            'categorie' => $categorie
        ]);
    }

    $question = $questions[$currentIndex];

    return $this->render('quiz/question.html.twig', [
        'categorie' => $categorie,
        'question' => $question,
        'index' => $currentIndex + 1,
        'total' => count($questions),
    ]);

    return $this->render('quiz/question.html.twig', [
      
    ]);
}
}
