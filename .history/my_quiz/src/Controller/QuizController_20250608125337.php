<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Question;
use App\Entity\Reponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;


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

    #[Route('/quiz/{id}', name: 'quiz_categorie', requirements: ['id' => '\d+'])]
    public function quizCategorie(Categorie $categorie, Request $request, SessionInterface $session)
    {

        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie non trouvée.');
        }

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

    #[Route('/quiz/create', name: 'quiz_create')]
    public function createSimpleQuiz(Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $categorieName = $request->request->get('categorie');
            $questionsData = $request->request->all('questions');

            if (!$categorieName || empty($questionsData)) {
                return new Response('Tous les champs sont requis.', 400);
            }

            $categorie = new Categorie();
            $categorie->setNom($categorieName);
            $em->persist($categorie);

            foreach ($questionsData as $index => $qData) {
                if (empty($qData['text']) || !isset($qData['bonne_reponse']) || empty($qData['reponses'])) {
                    continue; // ignore les questions incomplètes
                }

                $question = new Question();
                $question->setQuestion($qData['text']);
                $question->setCategorie($categorie);
                $em->persist($question);

                foreach ($qData['reponses'] as $rIndex => $rData) {
                    if (empty($rData['text'])) continue;

                    $reponse = new Reponse();
                    $reponse->setReponse($rData['text']);
                    $reponse->setEstCorrecte((int)$qData['bonne_reponse'] === (int)$rIndex);
                    $reponse->setQuestion($question);
                    $em->persist($reponse);
                }
            }

        $em->flush();

        return $this->redirectToRoute('quiz_global');
    }

    return $this->render('quiz/create.html.twig');
}

}



