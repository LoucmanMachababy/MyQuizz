<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
final class AdminUserController extends AbstractController
{

    #[Route('/', name: 'admin_users_list')]
    public function index(UserRepository $userRepository): Response
    {
        dump($this->getUser());
    dump($this->getUser()->getRoles());
    die();

        $users = $userRepository->findAll();

        return $this->render('admin_user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/{id}/promote', name: 'admin_users_promote', methods: ['POST'])]
    public function promote(User $user, EntityManagerInterface $em): Response
    {
        $user->setRoles(['ROLE_ADMIN']);
        $em->flush();

        $this->addFlash('success', 'Utilisateur promu en admin.');
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