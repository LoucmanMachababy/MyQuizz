<?php

namespace App\Controller;

use App\Repository\QuizHistoryRepository;
use App\Repository\UserRepository;
use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/stats')]
#[IsGranted('ROLE_ADMIN')]
class AdminStatsController extends AbstractController
{
    #[Route('/', name: 'admin_stats')]
    public function index(
        QuizHistoryRepository $quizHistoryRepo,
        UserRepository $userRepo,
        CategorieRepository $categorieRepo
    ): Response {
        // Statistiques générales
        $stats = $quizHistoryRepo->getStatistics();
        
        // Statistiques par période
        $stats24h = count($quizHistoryRepo->getQuizzesByPeriod('24h'));
        $statsWeek = count($quizHistoryRepo->getQuizzesByPeriod('week'));
        $statsMonth = count($quizHistoryRepo->getQuizzesByPeriod('month'));
        $statsYear = count($quizHistoryRepo->getQuizzesByPeriod('year'));

        // Utilisateurs actifs/inactifs
        $usersActive = $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.lastLoginAt >= :date')
            ->setParameter('date', new \DateTime('-1 month'))
            ->getQuery()
            ->getSingleScalarResult();

        $usersInactive = $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.lastLoginAt < :date OR u.lastLoginAt IS NULL')
            ->setParameter('date', new \DateTime('-1 month'))
            ->getQuery()
            ->getSingleScalarResult();

        // Statistiques par catégorie
        $categories = $categorieRepo->findAll();
        $categoryStats = [];
        
        foreach ($categories as $categorie) {
            $quizCount = $quizHistoryRepo->createQueryBuilder('qh')
                ->select('COUNT(qh.id)')
                ->where('qh.categorie = :categorie')
                ->setParameter('categorie', $categorie)
                ->getQuery()
                ->getSingleScalarResult();

            $avgScore = $quizHistoryRepo->createQueryBuilder('qh')
                ->select('AVG(qh.score)')
                ->where('qh.categorie = :categorie')
                ->setParameter('categorie', $categorie)
                ->getQuery()
                ->getSingleScalarResult();

            $categoryStats[] = [
                'categorie' => $categorie,
                'quizCount' => $quizCount,
                'avgScore' => round($avgScore, 2)
            ];
        }

        return $this->render('admin_stats/index.html.twig', [
            'stats' => $stats,
            'stats24h' => $stats24h,
            'statsWeek' => $statsWeek,
            'statsMonth' => $statsMonth,
            'statsYear' => $statsYear,
            'usersActive' => $usersActive,
            'usersInactive' => $usersInactive,
            'categoryStats' => $categoryStats,
        ]);
    }
} 