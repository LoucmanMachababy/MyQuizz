// src/Controller/QuizController.php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class QuizController extends AbstractController
{
    #[Route('/quiz/{id}', name: 'quiz_categorie')]
    public function quizCategorie(Categorie $categorie, Request $request, SessionInterface $session)
    {
        $questions = $categorie->getQuestions()->getValues();

        // Récupérer l'index de la question actuelle depuis la session, ou démarrer à 0
        $currentIndex = $session->get('quiz_index_' . $categorie->getId(), 0);

        // Si le formulaire est soumis (bouton "Suivant")
        if ($request->isMethod('POST')) {
            $currentIndex++;
            $session->set('quiz_index_' . $categorie->getId(), $currentIndex);
        }

        // Si toutes les questions ont été vues
        if ($currentIndex >= count($questions)) {
            $session->remove('quiz_index_' . $categorie->getId());
            return $this->render('quiz/finished.html.twig', [
                'categorie' => $categorie
            ]);
        }

        // Afficher la question actuelle
        $question = $questions[$currentIndex];

        return $this->render('quiz/question.html.twig', [
            'categorie' => $categorie,
            'question' => $question,
            'index' => $currentIndex + 1,
            'total' => count($questions),
        ]);
    }
}
