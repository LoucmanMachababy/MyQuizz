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

    #[Route('/quiz/{id}', name: 'quiz_categorie')]
    public function quizCategorie(Categorie $categorie, Request $request, SessionInterface $session)
    {
        $questions = $categorie->getQuestions()->getValues();
        $currentIndex = $session->get('quiz_index_' . $categorie->getId(), 0);
        $userAnswers = $session->get('quiz_answers_' . $categorie->getId(), []);

        if ($request->isMethod('POST')) {
            $userReponseId = $request->request->get('reponse_id');
            if ($userReponseId !== null) {
                $question = $questions[$currentIndex];
                $reponse = null;

                foreach ($question->getReponses() as $r) {
                    if ($r->getId() == $userReponseId) {
                        $reponse = $r;
                        break;
                    }
                }

                if ($reponse) {
                    $userAnswers[] = [
                        'question' => $question,
                        'user_reponse' => $reponse,
                        'correcte' => $reponse->isEstCorrecte()
                    ];
                    $session->set('quiz_answers_' . $categorie->getId(), $userAnswers);
                }
            }

            $currentIndex++;
            $session->set('quiz_index_' . $categorie->getId(), $currentIndex);

            return $this->redirectToRoute('quiz_categorie', ['id' => $categorie->getId()]);
        }

        if ($currentIndex >= count($questions)) {
            $session->remove('quiz_index_' . $categorie->getId());
            $answers = $session->get('quiz_answers_' . $categorie->getId(), []);
            $score = 0;
            foreach ($answers as $answer) {
                if ($answer['correcte']) {
                    $score++;
                }
            }

            return $this->render('quiz/finished.html.twig', [
                'categorie' => $categorie,
                'answers' => $answers,
                'score' => $score,
                'total' => count($questions),
            ]);
        }

        $question = $questions[$currentIndex];

        return $this->render('quiz/question.html.twig', [
            'categorie' => $categorie,
            'question' => $question,
            'index' => $currentIndex + 1,
            'total' => count($questions),
        ]);
    }
}



