<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\TeamQuiz;
use App\Entity\TeamQuizParticipant;
use App\Repository\TeamRepository;
use App\Repository\TeamQuizRepository;
use App\Repository\QuestionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/team')]
#[IsGranted('ROLE_USER')]
class TeamController extends AbstractController
{
    #[Route('/', name: 'team_index', methods: ['GET'])]
    public function index(TeamRepository $teamRepository): Response
    {
        $user = $this->getUser();
        $userTeams = $teamRepository->findTeamsByUser($user);
        $publicTeams = $teamRepository->findPublicTeams();
        $topTeams = $teamRepository->findTopTeams(5);

        return $this->render('team/index.html.twig', [
            'user_teams' => $userTeams,
            'public_teams' => $publicTeams,
            'top_teams' => $topTeams,
        ]);
    }

    #[Route('/create', name: 'team_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $description = $request->request->get('description');
            $isPublic = $request->request->getBoolean('isPublic', true);

            if (empty($name)) {
                $this->addFlash('error', 'Le nom de l\'équipe est requis.');
                return $this->redirectToRoute('team_create');
            }

            $team = new Team();
            $team->setName($name);
            $team->setDescription($description);
            $team->setIsPublic($isPublic);
            $team->setOwner($this->getUser());

            $entityManager->persist($team);
            $entityManager->flush();

            $this->addFlash('success', 'Équipe créée avec succès !');
            return $this->redirectToRoute('team_show', ['id' => $team->getId()]);
        }

        return $this->render('team/create.html.twig');
    }

    #[Route('/{id}', name: 'team_show', methods: ['GET'])]
    public function show(Team $team, TeamQuizRepository $teamQuizRepository): Response
    {
        $user = $this->getUser();
        
        if (!$team->hasMember($user) && !$team->isPublic()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette équipe.');
        }

        $activeQuizzes = $teamQuizRepository->findActiveQuizzesByTeam($team);
        $completedQuizzes = $teamQuizRepository->findCompletedQuizzesByTeam($team);

        return $this->render('team/show.html.twig', [
            'team' => $team,
            'active_quizzes' => $activeQuizzes,
            'completed_quizzes' => $completedQuizzes,
            'is_member' => $team->hasMember($user),
            'is_owner' => $team->getOwner() === $user,
        ]);
    }

    #[Route('/{id}/join', name: 'team_join', methods: ['POST'])]
    public function join(Team $team, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if ($team->hasMember($user)) {
            $this->addFlash('error', 'Vous êtes déjà membre de cette équipe.');
            return $this->redirectToRoute('team_show', ['id' => $team->getId()]);
        }

        if (!$team->isPublic()) {
            $this->addFlash('error', 'Cette équipe n\'est pas publique.');
            return $this->redirectToRoute('team_show', ['id' => $team->getId()]);
        }

        $team->addMember($user);
        $entityManager->flush();

        $this->addFlash('success', 'Vous avez rejoint l\'équipe avec succès !');
        return $this->redirectToRoute('team_show', ['id' => $team->getId()]);
    }

    #[Route('/{id}/leave', name: 'team_leave', methods: ['POST'])]
    public function leave(Team $team, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if ($team->getOwner() === $user) {
            $this->addFlash('error', 'Le propriétaire ne peut pas quitter l\'équipe.');
            return $this->redirectToRoute('team_show', ['id' => $team->getId()]);
        }

        if (!$team->hasMember($user)) {
            $this->addFlash('error', 'Vous n\'êtes pas membre de cette équipe.');
            return $this->redirectToRoute('team_show', ['id' => $team->getId()]);
        }

        $team->removeMember($user);
        $entityManager->flush();

        $this->addFlash('success', 'Vous avez quitté l\'équipe.');
        return $this->redirectToRoute('team_index');
    }

    #[Route('/{id}/quiz/create', name: 'team_quiz_create', methods: ['GET', 'POST'])]
    public function createQuiz(Request $request, Team $team, QuestionRepository $questionRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$team->hasMember($user)) {
            throw $this->createAccessDeniedException('Vous devez être membre de l\'équipe pour créer un quiz.');
        }

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $category = $request->request->get('category');
            $difficulty = $request->request->get('difficulty', 'medium');
            $questionCount = (int) $request->request->get('questionCount', 10);

            if (empty($title)) {
                $this->addFlash('error', 'Le titre du quiz est requis.');
                return $this->redirectToRoute('team_quiz_create', ['id' => $team->getId()]);
            }

            // Récupérer les questions
            $questions = $questionRepository->findByCategoryAndDifficulty($category, $difficulty, $questionCount);
            
            if (empty($questions)) {
                $this->addFlash('error', 'Aucune question trouvée pour cette catégorie et difficulté.');
                return $this->redirectToRoute('team_quiz_create', ['id' => $team->getId()]);
            }

            $teamQuiz = new TeamQuiz();
            $teamQuiz->setTitle($title);
            $teamQuiz->setDescription($description);
            $teamQuiz->setCategory($category);
            $teamQuiz->setDifficulty($difficulty);
            $teamQuiz->setQuestionCount($questionCount);
            $teamQuiz->setTeam($team);
            $teamQuiz->setCreatedBy($user);
            $teamQuiz->setTotalQuestions(count($questions));

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
            $teamQuiz->setQuestions($questionsData);

            $entityManager->persist($teamQuiz);
            $entityManager->flush();

            $this->addFlash('success', 'Quiz d\'équipe créé avec succès !');
            return $this->redirectToRoute('team_quiz_play', ['id' => $team->getId(), 'quizId' => $teamQuiz->getId()]);
        }

        $categories = $questionRepository->findAllCategories();

        return $this->render('team/create_quiz.html.twig', [
            'team' => $team,
            'categories' => $categories,
        ]);
    }

    #[Route('/{id}/quiz/{quizId}/play', name: 'team_quiz_play', methods: ['GET', 'POST'])]
    public function playQuiz(Request $request, Team $team, TeamQuiz $teamQuiz, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$team->hasMember($user)) {
            throw $this->createAccessDeniedException('Vous devez être membre de l\'équipe pour participer.');
        }

        if (!$teamQuiz->isActive()) {
            $this->addFlash('error', 'Ce quiz n\'est plus actif.');
            return $this->redirectToRoute('team_show', ['id' => $team->getId()]);
        }

        // Vérifier si l'utilisateur a déjà participé
        $participant = $entityManager->getRepository(TeamQuizParticipant::class)
            ->findParticipantByUserAndQuiz($user, $teamQuiz);

        if (!$participant) {
            $participant = new TeamQuizParticipant();
            $participant->setUser($user);
            $participant->setTeamQuiz($teamQuiz);
            $participant->setTotalQuestions($teamQuiz->getTotalQuestions());
            $entityManager->persist($participant);
            $entityManager->flush();
        }

        if ($request->isMethod('POST')) {
            $answers = $request->request->all('answers');
            $correctAnswers = 0;
            $score = 0;

            foreach ($answers as $questionIndex => $answerIndex) {
                $participant->addAnswer($questionIndex, (int) $answerIndex);
                
                $questionData = $teamQuiz->getQuestions()[$questionIndex] ?? null;
                if ($questionData && $questionData['correct'] == $answerIndex) {
                    $correctAnswers++;
                    $score += 10; // 10 points par bonne réponse
                }
            }

            $participant->setCorrectAnswers($correctAnswers);
            $participant->setScore($score);
            $participant->complete();

            // Mettre à jour le score de l'équipe
            $team->addPoints($score);
            $team->incrementQuizzesCompleted();

            $entityManager->flush();

            $this->addFlash('success', 'Quiz terminé ! Score: ' . $score . ' points');
            return $this->redirectToRoute('team_quiz_results', ['id' => $team->getId(), 'quizId' => $teamQuiz->getId()]);
        }

        return $this->render('team/play_quiz.html.twig', [
            'team' => $team,
            'team_quiz' => $teamQuiz,
            'participant' => $participant,
        ]);
    }

    #[Route('/{id}/quiz/{quizId}/results', name: 'team_quiz_results', methods: ['GET'])]
    public function quizResults(Team $team, TeamQuiz $teamQuiz): Response
    {
        $user = $this->getUser();
        
        if (!$team->hasMember($user)) {
            throw $this->createAccessDeniedException('Vous devez être membre de l\'équipe pour voir les résultats.');
        }

        $participants = $entityManager->getRepository(TeamQuizParticipant::class)
            ->findParticipantsByQuiz($teamQuiz);

        return $this->render('team/quiz_results.html.twig', [
            'team' => $team,
            'team_quiz' => $teamQuiz,
            'participants' => $participants,
        ]);
    }

    #[Route('/join/{inviteCode}', name: 'team_join_by_code', methods: ['GET', 'POST'])]
    public function joinByCode(Request $request, string $inviteCode, TeamRepository $teamRepository, EntityManagerInterface $entityManager): Response
    {
        $team = $teamRepository->findByInviteCode($inviteCode);
        
        if (!$team) {
            $this->addFlash('error', 'Code d\'invitation invalide.');
            return $this->redirectToRoute('team_index');
        }

        $user = $this->getUser();
        
        if ($team->hasMember($user)) {
            $this->addFlash('error', 'Vous êtes déjà membre de cette équipe.');
            return $this->redirectToRoute('team_show', ['id' => $team->getId()]);
        }

        if ($request->isMethod('POST')) {
            $team->addMember($user);
            $entityManager->flush();

            $this->addFlash('success', 'Vous avez rejoint l\'équipe avec succès !');
            return $this->redirectToRoute('team_show', ['id' => $team->getId()]);
        }

        return $this->render('team/join_by_code.html.twig', [
            'team' => $team,
        ]);
    }
} 