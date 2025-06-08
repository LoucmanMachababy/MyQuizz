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
    #[Route('/quiz/history', name: 'quiz_history')]
    public function history(SessionInterface $session)
    {
        $history = $session->get('quiz_history', []);
    
        return $this->render('quiz/history.html.twig', [
            'history' => $history
        ]);
    }    

    #[Route('/quiz', name: 'quiz_global')]
    public function index(Request $request, \App\Repository\CategorieRepository $categorieRepo)
    {
        # $session = $request->getSession(); #}

        # if (!$session->has('user_id')) {
           # return $this->redirectToRoute('app_login');
        # } 

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

                    $userAnswers = $session->get('quiz_answers_' . $categorie->getId(), []);

                    $userAnswers[$question->getId()] = [
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

        $session->remove('quiz_answers_' . $categorie->getId());

        $history = $session->get('quiz_history', []);
        $history[] = [
        'categorie' => $categorie->getNom(), 
        'score' => $score,
        'total' => count($questions),
        'date' => (new \DateTime('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d H:i:s'),
        ];

        $session->set('quiz_history', $history);


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

    #[Route('/quiz/simple/create', name: 'quiz_simple_create')]
public function createSimpleQuiz(Request $request, EntityManagerInterface $em): Response
{
    if ($request->isMethod('POST')) {
        $categorieName = $request->request->get('categorie');
        $questionText = $request->request->get('question');
        $reponses = $request->request->get('reponses');
        $bonneReponse = $request->request->get('bonne_reponse');

        if (!$categorieName || !$questionText || !$reponses || $bonneReponse === null) {
            return new Response('Tous les champs sont requis.', 400);
        }

        // Création de la catégorie
        $categorie = new Categorie();
        $categorie->setNom($categorieName);
        $em->persist($categorie);

        // Création de la question
        $question = new Question();
        $question->setQuestion($questionText);
        $question->setCategorie($categorie);
        $em->persist($question);

        // Ajout des réponses
        foreach ($reponses as $index => $text) {
            $reponse = new Reponse();
            $reponse->setReponse($text);
            $reponse->setEstCorrecte($index == $bonneReponse); // cocher bonne réponse
            $reponse->setQuestion($question);
            $em->persist($reponse);
        }

        $em->flush();

        return new Response('Quiz créé avec succès !');
    }

    return $this->render('quiz/simple_create.html.twig');
}
}



