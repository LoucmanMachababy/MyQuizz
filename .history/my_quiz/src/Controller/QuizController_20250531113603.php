<?php

// src/Controller/QuizController.php

namespace App\Controller;

use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuizController extends AbstractController
{
    #[Route('/quiz/{id}', name: 'quiz_categorie')]
public function show(CategorieRepository $categorieRepo, int $id): Response
{
    $categorie = $categorieRepo->find($id);

    if (!$categorie) {
        throw $this->createNotFoundException('Catégorie introuvable');
    }

    return $this->render('quiz_categorie.html.twig', [
        'categorie' => $categorie,
    ]);
}
x
}
