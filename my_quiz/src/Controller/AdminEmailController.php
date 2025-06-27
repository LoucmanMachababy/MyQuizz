<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\QuizHistoryRepository;
use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/admin/email')]
#[IsGranted('ROLE_ADMIN')]
class AdminEmailController extends AbstractController
{
    #[Route('/', name: 'admin_email')]
    public function index(
        UserRepository $userRepo,
        CategorieRepository $categorieRepo
    ): Response {
        // Utilisateurs qui ont passé des quiz
        $usersWithQuizzes = $userRepo->createQueryBuilder('u')
            ->join('App\Entity\QuizHistory', 'qh', 'WITH', 'qh.user = u')
            ->select('DISTINCT u')
            ->getQuery()
            ->getResult();

        // Utilisateurs qui n'ont pas passé de quiz
        $usersWithoutQuizzes = $userRepo->createQueryBuilder('u')
            ->leftJoin('App\Entity\QuizHistory', 'qh', 'WITH', 'qh.user = u')
            ->where('qh.id IS NULL')
            ->select('u')
            ->getQuery()
            ->getResult();

        // Utilisateurs actifs (connexion dans le mois)
        $usersActive = $userRepo->createQueryBuilder('u')
            ->where('u.lastLoginAt >= :date')
            ->setParameter('date', new \DateTime('-1 month'))
            ->select('u')
            ->getQuery()
            ->getResult();

        // Utilisateurs inactifs
        $usersInactive = $userRepo->createQueryBuilder('u')
            ->where('u.lastLoginAt < :date OR u.lastLoginAt IS NULL')
            ->setParameter('date', new \DateTime('-1 month'))
            ->select('u')
            ->getQuery()
            ->getResult();

        return $this->render('admin_email/index.html.twig', [
            'usersWithQuizzes' => $usersWithQuizzes,
            'usersWithoutQuizzes' => $usersWithoutQuizzes,
            'usersActive' => $usersActive,
            'usersInactive' => $usersInactive,
            'categories' => $categorieRepo->findAll(),
        ]);
    }

    #[Route('/send', name: 'admin_email_send', methods: ['POST'])]
    public function sendEmail(Request $request, MailerInterface $mailer, UserRepository $userRepo): Response
    {
        $type = $request->request->get('type');
        $subject = $request->request->get('subject');
        $message = $request->request->get('message');
        $userIds = $request->request->get('users', []);

        if (empty($subject) || empty($message) || empty($userIds)) {
            $this->addFlash('error', 'Tous les champs sont requis.');
            return $this->redirectToRoute('admin_email');
        }

        $users = $userRepo->findBy(['id' => $userIds]);
        $sentCount = 0;

        foreach ($users as $user) {
            $email = (new Email())
                ->from('admin@myquiz.com')
                ->to($user->getEmail())
                ->subject($subject)
                ->html($message);

            $mailer->send($email);
            $sentCount++;
        }

        $this->addFlash('success', "Email envoyé à {$sentCount} utilisateur(s).");
        return $this->redirectToRoute('admin_email');
    }

    #[Route('/users-by-category/{id}', name: 'admin_email_users_by_category')]
    public function getUsersByCategory(
        int $id,
        QuizHistoryRepository $quizHistoryRepo,
        UserRepository $userRepo
    ): Response {
        $users = $userRepo->createQueryBuilder('u')
            ->join('App\Entity\QuizHistory', 'qh', 'WITH', 'qh.user = u')
            ->where('qh.categorie = :categorieId')
            ->setParameter('categorieId', $id)
            ->select('DISTINCT u')
            ->getQuery()
            ->getResult();

        $userData = array_map(function($user) {
            return [
                'id' => $user->getId(),
                'email' => $user->getEmail()
            ];
        }, $users);

        return $this->json($userData);
    }
} 