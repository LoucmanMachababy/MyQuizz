<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories')]
#[IsGranted('ROLE_ADMIN')]
class AdminCategorieController extends AbstractController
{
    #[Route('/', name: 'admin_categories_list')]
    public function index(CategorieRepository $categorieRepository): Response
    {
        $categories = $categorieRepository->findAll();

        return $this->render('admin_categorie/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/create', name: 'admin_categories_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            
            if (empty($nom)) {
                $this->addFlash('error', 'Le nom de la catégorie est requis.');
                return $this->redirectToRoute('admin_categories_create');
            }

            $categorie = new Categorie();
            $categorie->setNom($nom);
            
            $em->persist($categorie);
            $em->flush();

            $this->addFlash('success', 'Catégorie créée avec succès.');
            return $this->redirectToRoute('admin_categories_list');
        }

        return $this->render('admin_categorie/create.html.twig');
    }

    #[Route('/{id}/edit', name: 'admin_categories_edit')]
    public function edit(Categorie $categorie, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            
            if (empty($nom)) {
                $this->addFlash('error', 'Le nom de la catégorie est requis.');
                return $this->redirectToRoute('admin_categories_edit', ['id' => $categorie->getId()]);
            }

            $categorie->setNom($nom);
            $em->flush();

            $this->addFlash('success', 'Catégorie mise à jour avec succès.');
            return $this->redirectToRoute('admin_categories_list');
        }

        return $this->render('admin_categorie/edit.html.twig', [
            'categorie' => $categorie,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_categories_delete', methods: ['POST'])]
    public function delete(Categorie $categorie, EntityManagerInterface $em): Response
    {
        $em->remove($categorie);
        $em->flush();

        $this->addFlash('success', 'Catégorie supprimée avec succès.');
        return $this->redirectToRoute('admin_categories_list');
    }
} 