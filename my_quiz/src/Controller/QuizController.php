<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Question;
use App\Entity\Reponse;
use App\Entity\QuizHistory;
use App\Repository\QuizHistoryRepository;
use App\Service\QuizRewardService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Repository\UserRepository;


class QuizController extends AbstractController
{
    #[Route('/quiz/history', name: 'quiz_history')]
    public function history(SessionInterface $session, QuizHistoryRepository $quizHistoryRepo)
    {
        $user = $this->getUser();
        
        if ($user) {
            // Utilisateur connecté : récupérer l'historique depuis la base de données
            $history = $quizHistoryRepo->findByUser($user);
        } else {
            // Utilisateur non connecté : récupérer depuis la session
            $history = $session->get('quiz_history', []);
        }
    
        return $this->render('quiz/history.html.twig', [
            'history' => $history,
            'isConnected' => $user !== null
        ]);
    }    

    #[Route('/quiz', name: 'quiz_global')]
    public function index(Request $request, \App\Repository\CategorieRepository $categorieRepo)
    {
        $categories = $categorieRepo->findAll();

        return $this->render('quiz.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/quiz/{id}', name: 'quiz_categorie', requirements: ['id' => '\d+'])]
    public function quizCategorie(Categorie $categorie, Request $request, SessionInterface $session, EntityManagerInterface $em)
    {
        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie non trouvée.');
        }

        $questions = $categorie->getQuestions()->getValues();
        
        // Vérifier s'il y a des questions
        if (empty($questions)) {
            $this->addFlash('error', 'Aucune question disponible pour cette catégorie.');
            return $this->redirectToRoute('quiz_global');
        }

        // Récupérer le nombre de questions choisi (10 ou 20)
        $questionCount = $session->get('quiz_question_count_' . $categorie->getId(), 10);
        
        // Si c'est la première visite, proposer le choix
        if (!$session->has('quiz_started_' . $categorie->getId())) {
            return $this->render('quiz/choose_questions.html.twig', [
                'categorie' => $categorie,
                'totalQuestions' => count($questions)
            ]);
        }

        // Limiter le nombre de questions
        $questions = array_slice($questions, 0, $questionCount);
        
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
            $session->remove('quiz_started_' . $categorie->getId());
            $answers = $session->get('quiz_answers_' . $categorie->getId(), []);
            $score = 0;
            foreach ($answers as $answer) {
                if ($answer['correcte']) {
                    $score++;
                }
            }

            $session->remove('quiz_answers_' . $categorie->getId());

            // Sauvegarder l'historique en base de données si l'utilisateur est connecté
            $user = $this->getUser();
            if ($user) {
                $quizHistory = new QuizHistory();
                $quizHistory->setUser($user);
                $quizHistory->setCategorie($categorie);
                $quizHistory->setScore($score);
                $quizHistory->setTotalQuestions(count($questions));
                $quizHistory->setUserIp($request->getClientIp());
                
                $em->persist($quizHistory);
                $em->flush();

                // Traiter les récompenses (points et badges)
                $rewardService = new QuizRewardService($em);
                $rewardService->processQuizCompletion($user, $quizHistory);
            } else {
                // Utilisateur non connecté : sauvegarder en session
                $history = $session->get('quiz_history', []);
                $history[] = [
                    'categorie' => $categorie->getNom(), 
                    'score' => $score,
                    'total' => count($questions),
                    'date' => (new \DateTime('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d H:i:s'),
                ];
                $session->set('quiz_history', $history);
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

    #[Route('/quiz/{id}/start', name: 'quiz_start', methods: ['POST'])]
    public function startQuiz(Categorie $categorie, Request $request, SessionInterface $session)
    {
        $questionCount = $request->request->get('question_count', 10);
        
        // Valider le choix (10 ou 20)
        if (!in_array($questionCount, [10, 20])) {
            $questionCount = 10;
        }
        
        $session->set('quiz_question_count_' . $categorie->getId(), $questionCount);
        $session->set('quiz_started_' . $categorie->getId(), true);
        $session->set('quiz_index_' . $categorie->getId(), 0);
        $session->set('quiz_answers_' . $categorie->getId(), []);
        
        return $this->redirectToRoute('quiz_categorie', ['id' => $categorie->getId()]);
    }

    #[Route('/quiz/create', name: 'quiz_create')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function createSimpleQuiz(Request $request, EntityManagerInterface $em): Response
    {
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
                    continue; // ignore les questions incompletes
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

    #[Route('/quiz/leaderboard', name: 'quiz_leaderboard')]
    public function leaderboard(UserRepository $userRepo, QuizHistoryRepository $historyRepo): Response
    {
        // Top 10 des joueurs par points
        $topPlayers = $userRepo->createQueryBuilder('u')
            ->where('u.points > 0')
            ->orderBy('u.points', 'DESC')
            ->addOrderBy('u.quizzesCompleted', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Statistiques globales
        $totalUsers = $userRepo->count([]);
        $activeUsers = $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.quizzesCompleted > 0')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('quiz/leaderboard.html.twig', [
            'topPlayers' => $topPlayers,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers
        ]);
    }
}



