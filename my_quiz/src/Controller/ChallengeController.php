<?php

namespace App\Controller;

use App\Entity\Challenge;
use App\Entity\ChallengeResult;
use App\Repository\ChallengeRepository;
use App\Repository\QuestionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/challenge')]
#[IsGranted('ROLE_USER')]
class ChallengeController extends AbstractController
{
    #[Route('/', name: 'challenge_index', methods: ['GET'])]
    public function index(ChallengeRepository $challengeRepository): Response
    {
        $user = $this->getUser();
        $activeChallenges = $challengeRepository->findActiveChallengesByUser($user);
        $pendingChallenges = $challengeRepository->findPendingChallengesByUser($user);
        $completedChallenges = $challengeRepository->findCompletedChallengesByUser($user);
        $recentChallenges = $challengeRepository->findRecentChallenges(10);

        return $this->render('challenge/index.html.twig', [
            'active_challenges' => $activeChallenges,
            'pending_challenges' => $pendingChallenges,
            'completed_challenges' => $completedChallenges,
            'recent_challenges' => $recentChallenges,
        ]);
    }

    #[Route('/create', name: 'challenge_create', methods: ['GET', 'POST'])]
    public function create(Request $request, UserRepository $userRepository, QuestionRepository $questionRepository, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $challengedEmail = $request->request->get('challenged_email');
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $category = $request->request->get('category');
            $difficulty = $request->request->get('difficulty', 'medium');
            $questionCount = (int) $request->request->get('questionCount', 10);
            $timeLimit = (int) $request->request->get('timeLimit', 300);

            if (empty($challengedEmail) || empty($title)) {
                $this->addFlash('error', 'L\'email du challengé et le titre sont requis.');
                return $this->redirectToRoute('challenge_create');
            }

            $challenged = $userRepository->findOneBy(['email' => $challengedEmail]);
            if (!$challenged) {
                $this->addFlash('error', 'Utilisateur non trouvé.');
                return $this->redirectToRoute('challenge_create');
            }

            if ($challenged === $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas vous défier vous-même.');
                return $this->redirectToRoute('challenge_create');
            }

            // Récupérer les questions
            $questions = $questionRepository->findByCategoryAndDifficulty($category, $difficulty, $questionCount);
            
            if (empty($questions)) {
                $this->addFlash('error', 'Aucune question trouvée pour cette catégorie et difficulté.');
                return $this->redirectToRoute('challenge_create');
            }

            $challenge = new Challenge();
            $challenge->setTitle($title);
            $challenge->setDescription($description);
            $challenge->setCategory($category);
            $challenge->setDifficulty($difficulty);
            $challenge->setQuestionCount($questionCount);
            $challenge->setTimeLimit($timeLimit);
            $challenge->setChallenger($this->getUser());
            $challenge->setChallenged($challenged);
            $challenge->setMaxScore($questionCount * 10);

            // Préparer les questions pour le JSON
            $questionsData = [];
            foreach ($questions as $question) {
                $reponses = $question->getReponses()->toArray();
                $questionsData[] = [
                    'id' => $question->getId(),
                    'question' => $question->getQuestion(),
                    'reponses' => array_map(fn($r) => $r->getReponse(), $reponses),
                    'correct' => array_search(true, array_map(fn($r) => $r->isCorrect(), $reponses))
                ];
            }
            $challenge->setQuestions($questionsData);

            $entityManager->persist($challenge);
            $entityManager->flush();

            $this->addFlash('success', 'Défi envoyé avec succès !');
            return $this->redirectToRoute('challenge_show', ['id' => $challenge->getId()]);
        }

        $categories = $questionRepository->findAllCategories();

        return $this->render('challenge/create.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/{id}', name: 'challenge_show', methods: ['GET'])]
    public function show(Challenge $challenge): Response
    {
        $user = $this->getUser();
        
        if ($challenge->getChallenger() !== $user && $challenge->getChallenged() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce défi.');
        }

        $challengerResult = $challenge->getChallengerResult();
        $challengedResult = $challenge->getChallengedResult();
        $winner = $challenge->getWinner();

        return $this->render('challenge/show.html.twig', [
            'challenge' => $challenge,
            'challenger_result' => $challengerResult,
            'challenged_result' => $challengedResult,
            'winner' => $winner,
            'is_challenger' => $challenge->getChallenger() === $user,
            'is_challenged' => $challenge->getChallenged() === $user,
        ]);
    }

    #[Route('/{id}/accept', name: 'challenge_accept', methods: ['POST'])]
    public function accept(Challenge $challenge, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if ($challenge->getChallenged() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accepter ce défi.');
        }

        if ($challenge->isAccepted()) {
            $this->addFlash('error', 'Ce défi a déjà été accepté.');
            return $this->redirectToRoute('challenge_show', ['id' => $challenge->getId()]);
        }

        if ($challenge->isExpired()) {
            $this->addFlash('error', 'Ce défi a expiré.');
            return $this->redirectToRoute('challenge_show', ['id' => $challenge->getId()]);
        }

        $challenge->accept();
        $entityManager->flush();

        $this->addFlash('success', 'Défi accepté ! Vous pouvez maintenant le jouer.');
        return $this->redirectToRoute('challenge_play', ['id' => $challenge->getId()]);
    }

    #[Route('/{id}/play', name: 'challenge_play', methods: ['GET', 'POST'])]
    public function play(Request $request, Challenge $challenge, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if ($challenge->getChallenger() !== $user && $challenge->getChallenged() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas jouer ce défi.');
        }

        if (!$challenge->isAccepted()) {
            $this->addFlash('error', 'Ce défi n\'a pas encore été accepté.');
            return $this->redirectToRoute('challenge_show', ['id' => $challenge->getId()]);
        }

        if ($challenge->isCompleted()) {
            $this->addFlash('error', 'Ce défi est déjà terminé.');
            return $this->redirectToRoute('challenge_show', ['id' => $challenge->getId()]);
        }

        // Vérifier si l'utilisateur a déjà joué
        $result = $entityManager->getRepository(ChallengeResult::class)
            ->findResultByUserAndChallenge($user, $challenge);

        if (!$result) {
            $result = new ChallengeResult();
            $result->setUser($user);
            $result->setChallenge($challenge);
            $result->setTotalQuestions($challenge->getQuestionCount());
            $entityManager->persist($result);
            $entityManager->flush();
        }

        if ($request->isMethod('POST')) {
            $answers = $request->request->all('answers');
            $correctAnswers = 0;
            $score = 0;

            foreach ($answers as $questionIndex => $answerIndex) {
                $result->addAnswer($questionIndex, (int) $answerIndex);
                
                $questionData = $challenge->getQuestions()[$questionIndex] ?? null;
                if ($questionData && $questionData['correct'] == $answerIndex) {
                    $correctAnswers++;
                    $score += 10; // 10 points par bonne réponse
                }
            }

            $result->setCorrectAnswers($correctAnswers);
            $result->setScore($score);
            $result->complete();

            // Vérifier si les deux joueurs ont terminé
            $challengerResult = $challenge->getChallengerResult();
            $challengedResult = $challenge->getChallengedResult();

            if ($challengerResult && $challengerResult->isCompleted() && 
                $challengedResult && $challengedResult->isCompleted()) {
                $challenge->complete();
            }

            $entityManager->flush();

            $this->addFlash('success', 'Défi terminé ! Score: ' . $score . ' points');
            return $this->redirectToRoute('challenge_show', ['id' => $challenge->getId()]);
        }

        return $this->render('challenge/play.html.twig', [
            'challenge' => $challenge,
            'result' => $result,
        ]);
    }

    #[Route('/{id}/decline', name: 'challenge_decline', methods: ['POST'])]
    public function decline(Challenge $challenge, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if ($challenge->getChallenged() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas décliner ce défi.');
        }

        if ($challenge->isAccepted()) {
            $this->addFlash('error', 'Ce défi a déjà été accepté.');
            return $this->redirectToRoute('challenge_show', ['id' => $challenge->getId()]);
        }

        $challenge->setIsActive(false);
        $challenge->setStatus('declined');
        $entityManager->flush();

        $this->addFlash('success', 'Défi décliné.');
        return $this->redirectToRoute('challenge_index');
    }

    #[Route('/stats', name: 'challenge_stats', methods: ['GET'])]
    public function stats(ChallengeRepository $challengeRepository): Response
    {
        $user = $this->getUser();
        $stats = $challengeRepository->getChallengeStats($user);
        $recentChallenges = $challengeRepository->findCompletedChallengesByUser($user);

        return $this->render('challenge/stats.html.twig', [
            'stats' => $stats,
            'recent_challenges' => $recentChallenges,
        ]);
    }
} 