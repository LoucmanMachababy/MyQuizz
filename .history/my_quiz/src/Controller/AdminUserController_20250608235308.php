<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
final class AdminUserController extends AbstractController
{
    #[Route('/', name: 'admin_users_list')]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
    
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }
    
        $user = $userRepository->find($userId);
    
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }
    
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
    
        // Si on arrive ici, l’utilisateur est admin
        return $this->render('admin_user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }
    

    #[Route('/{id}/promote', name: 'admin_users_promote', methods: ['POST'])]
    public function promote(User $user, EntityManagerInterface $em): Response
    {
        // Ajouter ROLE_ADMIN aux rôles existants, éviter d'écraser tous les rôles
        $roles = $user->getRoles();
        if (!in_array('ROLE_ADMIN', $roles, true)) {
            $roles[] = 'ROLE_ADMIN';
            $user->setRoles($roles);
            $em->flush();
            $this->addFlash('success', 'Utilisateur promu en admin.');
        } else {
            $this->addFlash('info', 'Utilisateur est déjà admin.');
        }

        return $this->redirectToRoute('admin_users_list');
    }

    #[Route('/{id}/delete', name: 'admin_users_delete', methods: ['POST'])]
    public function delete(User $user, EntityManagerInterface $em): Response
    {
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé.');
        return $this->redirectToRoute('admin_users_list');
    }
}
