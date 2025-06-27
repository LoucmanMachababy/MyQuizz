<?php

namespace App\DataFixtures;

use App\Entity\Categorie;
use App\Entity\Question;
use App\Entity\Reponse;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Créer un utilisateur admin
        $admin = new User();
        $admin->setEmail('admin@myquiz.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setEmailConfirmed(true);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsActive(true);
        $manager->persist($admin);

        // Créer un utilisateur normal
        $user = new User();
        $user->setEmail('user@myquiz.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));
        $user->setEmailConfirmed(true);
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);
        $manager->persist($user);

        // Catégorie Histoire
        $histoire = new Categorie();
        $histoire->setNom('Histoire');
        $manager->persist($histoire);

        $questionsHistoire = [
            [
                'question' => 'En quelle année a eu lieu la Révolution française ?',
                'reponses' => [
                    ['reponse' => '1789', 'correcte' => true],
                    ['reponse' => '1799', 'correcte' => false],
                    ['reponse' => '1779', 'correcte' => false],
                    ['reponse' => '1769', 'correcte' => false],
                ]
            ],
            [
                'question' => 'Qui était le premier empereur romain ?',
                'reponses' => [
                    ['reponse' => 'Jules César', 'correcte' => false],
                    ['reponse' => 'Auguste', 'correcte' => true],
                    ['reponse' => 'Néron', 'correcte' => false],
                    ['reponse' => 'Caligula', 'correcte' => false],
                ]
            ],
            [
                'question' => 'Quel roi de France était surnommé le Roi Soleil ?',
                'reponses' => [
                    ['reponse' => 'Louis XIII', 'correcte' => false],
                    ['reponse' => 'Louis XIV', 'correcte' => true],
                    ['reponse' => 'Louis XV', 'correcte' => false],
                    ['reponse' => 'Louis XVI', 'correcte' => false],
                ]
            ],
            [
                'question' => 'En quelle année Christophe Colomb a-t-il découvert l\'Amérique ?',
                'reponses' => [
                    ['reponse' => '1492', 'correcte' => true],
                    ['reponse' => '1498', 'correcte' => false],
                    ['reponse' => '1500', 'correcte' => false],
                    ['reponse' => '1485', 'correcte' => false],
                ]
            ],
            [
                'question' => 'Quel était le nom de la première femme dans l\'espace ?',
                'reponses' => [
                    ['reponse' => 'Valentina Terechkova', 'correcte' => true],
                    ['reponse' => 'Sally Ride', 'correcte' => false],
                    ['reponse' => 'Mae Jemison', 'correcte' => false],
                    ['reponse' => 'Eileen Collins', 'correcte' => false],
                ]
            ]
        ];

        foreach ($questionsHistoire as $qData) {
            $question = new Question();
            $question->setQuestion($qData['question']);
            $question->setCategorie($histoire);
            $manager->persist($question);

            foreach ($qData['reponses'] as $rData) {
                $reponse = new Reponse();
                $reponse->setReponse($rData['reponse']);
                $reponse->setEstCorrecte($rData['correcte']);
                $reponse->setQuestion($question);
                $manager->persist($reponse);
            }
        }

        // Catégorie Géographie
        $geographie = new Categorie();
        $geographie->setNom('Géographie');
        $manager->persist($geographie);

        $questionsGeographie = [
            [
                'question' => 'Quelle est la capitale du Japon ?',
                'reponses' => [
                    ['reponse' => 'Tokyo', 'correcte' => true],
                    ['reponse' => 'Kyoto', 'correcte' => false],
                    ['reponse' => 'Osaka', 'correcte' => false],
                    ['reponse' => 'Yokohama', 'correcte' => false],
                ]
            ],
            [
                'question' => 'Quel est le plus grand désert du monde ?',
                'reponses' => [
                    ['reponse' => 'Le Sahara', 'correcte' => true],
                    ['reponse' => 'Le désert de Gobi', 'correcte' => false],
                    ['reponse' => 'Le désert d\'Arabie', 'correcte' => false],
                    ['reponse' => 'Le désert du Kalahari', 'correcte' => false],
                ]
            ],
            [
                'question' => 'Quel fleuve traverse Paris ?',
                'reponses' => [
                    ['reponse' => 'La Seine', 'correcte' => true],
                    ['reponse' => 'La Loire', 'correcte' => false],
                    ['reponse' => 'Le Rhône', 'correcte' => false],
                    ['reponse' => 'La Garonne', 'correcte' => false],
                ]
            ],
            [
                'question' => 'Combien y a-t-il de continents sur Terre ?',
                'reponses' => [
                    ['reponse' => '5', 'correcte' => false],
                    ['reponse' => '6', 'correcte' => false],
                    ['reponse' => '7', 'correcte' => true],
                    ['reponse' => '8', 'correcte' => false],
                ]
            ],
            [
                'question' => 'Quel est le plus haut sommet du monde ?',
                'reponses' => [
                    ['reponse' => 'Le Mont Blanc', 'correcte' => false],
                    ['reponse' => 'Le K2', 'correcte' => false],
                    ['reponse' => 'L\'Everest', 'correcte' => true],
                    ['reponse' => 'Le Kilimandjaro', 'correcte' => false],
                ]
            ]
        ];

        foreach ($questionsGeographie as $qData) {
            $question = new Question();
            $question->setQuestion($qData['question']);
            $question->setCategorie($geographie);
            $manager->persist($question);

            foreach ($qData['reponses'] as $rData) {
                $reponse = new Reponse();
                $reponse->setReponse($rData['reponse']);
                $reponse->setEstCorrecte($rData['correcte']);
                $reponse->setQuestion($question);
                $manager->persist($reponse);
            }
        }

        // Catégorie Sciences
        $sciences = new Categorie();
        $sciences->setNom('Sciences');
        $manager->persist($sciences);

        $questionsSciences = [
            [
                'question' => 'Quel est le symbole chimique de l\'or ?',
                'reponses' => [
                    ['reponse' => 'Au', 'correcte' => true],
                    ['reponse' => 'Ag', 'correcte' => false],
                    ['reponse' => 'Fe', 'correcte' => false],
                    ['reponse' => 'Cu', 'correcte' => false],
                ]
            ],
            [
                'question' => 'Quelle est la formule chimique de l\'eau ?',
                'reponses' => [
                    ['reponse' => 'H2O', 'correcte' => true],
                    ['reponse' => 'CO2', 'correcte' => false],
                    ['reponse' => 'O2', 'correcte' => false],
                    ['reponse' => 'N2', 'correcte' => false],
                ]
            ],
            [
                'question' => 'Combien de planètes y a-t-il dans notre système solaire ?',
                'reponses' => [
                    ['reponse' => '7', 'correcte' => false],
                    ['reponse' => '8', 'correcte' => true],
                    ['reponse' => '9', 'correcte' => false],
                    ['reponse' => '10', 'correcte' => false],
                ]
            ],
            [
                'question' => 'Quel est l\'organe principal du système circulatoire ?',
                'reponses' => [
                    ['reponse' => 'Le cerveau', 'correcte' => false],
                    ['reponse' => 'Le cœur', 'correcte' => true],
                    ['reponse' => 'Les poumons', 'correcte' => false],
                    ['reponse' => 'Le foie', 'correcte' => false],
                ]
            ],
            [
                'question' => 'Quelle est l\'unité de mesure de la force ?',
                'reponses' => [
                    ['reponse' => 'Le watt', 'correcte' => false],
                    ['reponse' => 'Le joule', 'correcte' => false],
                    ['reponse' => 'Le newton', 'correcte' => true],
                    ['reponse' => 'Le pascal', 'correcte' => false],
                ]
            ]
        ];

        foreach ($questionsSciences as $qData) {
            $question = new Question();
            $question->setQuestion($qData['question']);
            $question->setCategorie($sciences);
            $manager->persist($question);

            foreach ($qData['reponses'] as $rData) {
                $reponse = new Reponse();
                $reponse->setReponse($rData['reponse']);
                $reponse->setEstCorrecte($rData['correcte']);
                $reponse->setQuestion($question);
                $manager->persist($reponse);
            }
        }

        $manager->flush();
    }
}
