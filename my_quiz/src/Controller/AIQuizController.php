<?php

namespace App\Controller;

use App\Service\AIQuizService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ai-quiz')]
class AIQuizController extends AbstractController
{
    public function __construct(
        private AIQuizService $aiService
    ) {}

    #[Route('/create', name: 'ai_quiz_create', methods: ['GET', 'POST'])]
    public function createQuiz(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $topic = $request->request->get('topic', 'Général');
            $questionCount = (int) $request->request->get('questionCount', 5);
            $difficulty = $request->request->get('difficulty', 'medium');

            $quiz = $this->aiService->generateQuiz($topic, $questionCount, $difficulty);

            return $this->render('ai_quiz/generated.html.twig', [
                'quiz' => $quiz,
                'topic' => $topic
            ]);
        }

        return $this->render('ai_quiz/create.html.twig');
    }

    #[Route('/generate', name: 'ai_quiz_generate', methods: ['POST'])]
    public function generateQuizAjax(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $topic = $data['topic'] ?? 'Général';
        $questionCount = (int) ($data['questionCount'] ?? 5);
        $difficulty = $data['difficulty'] ?? 'medium';

        $quiz = $this->aiService->generateQuiz($topic, $questionCount, $difficulty);

        return new JsonResponse($quiz);
    }

    #[Route('/explain', name: 'ai_quiz_explain', methods: ['POST'])]
    public function explainAnswer(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $question = $data['question'] ?? '';
        $correctAnswer = $data['correctAnswer'] ?? '';
        $userAnswer = $data['userAnswer'] ?? null;

        $explanation = $this->aiService->generateExplanation($question, $correctAnswer, $userAnswer);

        return new JsonResponse(['explanation' => $explanation]);
    }

    #[Route('/suggestions', name: 'ai_quiz_suggestions', methods: ['POST'])]
    public function getRevisionSuggestions(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $wrongAnswers = $data['wrongAnswers'] ?? [];
        $topic = $data['topic'] ?? 'Général';

        $suggestions = $this->aiService->generateRevisionSuggestions($wrongAnswers, $topic);

        return new JsonResponse(['suggestions' => $suggestions]);
    }

    #[Route('/play/{topic}', name: 'ai_quiz_play')]
    public function playQuiz(string $topic, Request $request): Response
    {
        $questionCount = (int) $request->query->get('questions', 5);
        $difficulty = $request->query->get('difficulty', 'medium');

        $quiz = $this->aiService->generateQuiz($topic, $questionCount, $difficulty);

        return $this->render('ai_quiz/play.html.twig', [
            'quiz' => $quiz,
            'topic' => $topic
        ]);
    }
} 