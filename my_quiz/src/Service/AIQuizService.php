<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class AIQuizService
{
    private Client $client;
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct(ParameterBagInterface $params)
    {
        $this->client = new Client();
        $this->apiKey = $params->get('app.openai_api_key') ?? '';
        
        // Si la clé est vide ou égale à la valeur par défaut, on la considère comme non définie
        if (empty($this->apiKey) || $this->apiKey === '%env(default::OPENAI_API_KEY)%') {
            $this->apiKey = '';
        }
    }

    /**
     * Génère un quiz complet avec l'IA
     */
    public function generateQuiz(string $topic, int $questionCount = 5, string $difficulty = 'medium'): array
    {
        if (empty($this->apiKey)) {
            return $this->generateFallbackQuiz($topic, $questionCount);
        }

        try {
            $prompt = $this->buildPrompt($topic, $questionCount, $difficulty);
            $response = $this->callOpenAI($prompt);
            
            return $this->parseAIResponse($response);
        } catch (GuzzleException $e) {
            // Fallback si l'API échoue
            return $this->generateFallbackQuiz($topic, $questionCount);
        }
    }

    /**
     * Génère des explications intelligentes pour les réponses
     */
    public function generateExplanation(string $question, string $correctAnswer, string $userAnswer = null): string
    {
        if (empty($this->apiKey)) {
            return $this->generateFallbackExplanation($question, $correctAnswer);
        }

        try {
            $prompt = $this->buildExplanationPrompt($question, $correctAnswer, $userAnswer);
            $response = $this->callOpenAI($prompt);
            
            return $this->parseExplanationResponse($response);
        } catch (GuzzleException $e) {
            return $this->generateFallbackExplanation($question, $correctAnswer);
        }
    }

    /**
     * Génère des suggestions de révision personnalisées
     */
    public function generateRevisionSuggestions(array $wrongAnswers, string $topic): array
    {
        if (empty($this->apiKey)) {
            return $this->generateFallbackSuggestions($wrongAnswers, $topic);
        }

        try {
            $prompt = $this->buildRevisionPrompt($wrongAnswers, $topic);
            $response = $this->callOpenAI($prompt);
            
            return $this->parseRevisionResponse($response);
        } catch (GuzzleException $e) {
            return $this->generateFallbackSuggestions($wrongAnswers, $topic);
        }
    }

    private function callOpenAI(string $prompt): array
    {
        $response = $this->client->post($this->baseUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un expert en création de quiz éducatifs. Tu génères des questions de qualité avec des réponses claires et des explications détaillées.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 2000,
                'temperature' => 0.7
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        return $data['choices'][0]['message']['content'] ?? '';
    }

    private function buildPrompt(string $topic, int $questionCount, string $difficulty): string
    {
        $difficultyText = match($difficulty) {
            'easy' => 'faciles',
            'medium' => 'moyennes',
            'hard' => 'difficiles',
            default => 'moyennes'
        };

        return "Crée un quiz sur le thème '$topic' avec $questionCount questions $difficultyText.

Format de réponse JSON strict :
{
  \"title\": \"Titre du quiz\",
  \"description\": \"Description courte\",
  \"questions\": [
    {
      \"question\": \"Question text\",
      \"options\": [\"A\", \"B\", \"C\", \"D\"],
      \"correct_answer\": 0,
      \"explanation\": \"Explication détaillée de la réponse\",
      \"difficulty\": \"$difficulty\"
    }
  ]
}

Règles :
- Questions variées et intéressantes
- 4 options par question (A, B, C, D)
- correct_answer = index (0-3)
- Explications claires et pédagogiques
- Réponse en français uniquement";
    }

    private function buildExplanationPrompt(string $question, string $correctAnswer, ?string $userAnswer): string
    {
        $userPart = $userAnswer ? "L'utilisateur a répondu : '$userAnswer'" : "L'utilisateur n'a pas répondu";
        
        return "Question : $question
Bonne réponse : $correctAnswer
$userPart

Génère une explication claire et pédagogique (2-3 phrases) qui explique pourquoi c'est la bonne réponse et donne un contexte supplémentaire.";
    }

    private function buildRevisionPrompt(array $wrongAnswers, string $topic): string
    {
        $wrongQuestions = implode("\n- ", $wrongAnswers);
        
        return "L'utilisateur a eu des difficultés avec ces questions sur '$topic' :
- $wrongQuestions

Génère 3 suggestions de révision personnalisées (format JSON) :
{
  \"suggestions\": [
    {
      \"title\": \"Titre de la suggestion\",
      \"description\": \"Description détaillée\",
      \"resources\": [\"Ressource 1\", \"Ressource 2\"],
      \"practice_questions\": [\"Question 1\", \"Question 2\"]
    }
  ]
}";
    }

    private function parseAIResponse(string $response): array
    {
        // Essayer de parser le JSON
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart !== false && $jsonEnd !== false) {
            $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
            $data = json_decode($jsonString, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['questions'])) {
                return $data;
            }
        }

        // Fallback si le parsing échoue
        return $this->generateFallbackQuiz('Général', 5);
    }

    private function parseExplanationResponse(string $response): string
    {
        // Nettoyer la réponse
        $explanation = trim($response);
        $explanation = preg_replace('/^["\']|["\']$/', '', $explanation);
        
        return $explanation ?: $this->generateFallbackExplanation('Question', 'Réponse');
    }

    private function parseRevisionResponse(string $response): array
    {
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart !== false && $jsonEnd !== false) {
            $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
            $data = json_decode($jsonString, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['suggestions'])) {
                return $data['suggestions'];
            }
        }

        return $this->generateFallbackSuggestions([], 'Général');
    }

    // Quiz de fallback (sans IA)
    private function generateFallbackQuiz(string $topic, int $questionCount): array
    {
        $fallbackQuestions = [
            'Sport' => [
                [
                    'question' => 'Quel pays a remporté le plus de Coupes du monde de football ?',
                    'options' => ['Brésil', 'Allemagne', 'Argentine', 'France'],
                    'correct_answer' => 0,
                    'explanation' => 'Le Brésil a remporté 5 Coupes du monde (1958, 1962, 1970, 1994, 2002).',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Quel est le sport national du Japon ?',
                    'options' => ['Sumo', 'Baseball', 'Judo', 'Karate'],
                    'correct_answer' => 0,
                    'explanation' => 'Le sumo est considéré comme le sport national du Japon depuis des siècles.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Combien de joueurs composent une équipe de basketball ?',
                    'options' => ['5', '6', '7', '8'],
                    'correct_answer' => 0,
                    'explanation' => 'Une équipe de basketball compte 5 joueurs sur le terrain.',
                    'difficulty' => 'easy'
                ],
                [
                    'question' => 'Quel est le record du monde du 100m masculin ?',
                    'options' => ['9.58 secondes', '9.69 secondes', '9.79 secondes', '9.89 secondes'],
                    'correct_answer' => 0,
                    'explanation' => 'Le record du monde du 100m est détenu par Usain Bolt avec 9.58 secondes.',
                    'difficulty' => 'hard'
                ],
                [
                    'question' => 'Quel sport se pratique sur glace avec des bâtons ?',
                    'options' => ['Hockey sur glace', 'Curling', 'Patinage', 'Ski'],
                    'correct_answer' => 0,
                    'explanation' => 'Le hockey sur glace se pratique avec des bâtons et une rondelle sur glace.',
                    'difficulty' => 'easy'
                ]
            ],
            'Histoire' => [
                [
                    'question' => 'Quelle est la capitale de la France ?',
                    'options' => ['Paris', 'Lyon', 'Marseille', 'Toulouse'],
                    'correct_answer' => 0,
                    'explanation' => 'Paris est la capitale de la France depuis le Moyen Âge.',
                    'difficulty' => 'easy'
                ],
                [
                    'question' => 'En quelle année a eu lieu la Révolution française ?',
                    'options' => ['1789', '1799', '1769', '1779'],
                    'correct_answer' => 0,
                    'explanation' => 'La Révolution française a commencé en 1789 avec la prise de la Bastille.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Qui était le premier empereur romain ?',
                    'options' => ['Auguste', 'Jules César', 'Néron', 'Caligula'],
                    'correct_answer' => 0,
                    'explanation' => 'Auguste (Octave) fut le premier empereur romain de 27 av. J.-C. à 14 ap. J.-C.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Quel événement marque la fin de la Seconde Guerre mondiale en Europe ?',
                    'options' => ['Victoire en Europe (8 mai 1945)', 'Bombardement d\'Hiroshima', 'Débarquement de Normandie', 'Chute de Berlin'],
                    'correct_answer' => 0,
                    'explanation' => 'Le 8 mai 1945 marque la capitulation de l\'Allemagne nazie.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Quel roi de France était surnommé le Roi Soleil ?',
                    'options' => ['Louis XIV', 'Louis XIII', 'Louis XV', 'Louis XVI'],
                    'correct_answer' => 0,
                    'explanation' => 'Louis XIV (1638-1715) était surnommé le Roi Soleil.',
                    'difficulty' => 'medium'
                ]
            ],
            'Géographie' => [
                [
                    'question' => 'Quel est le plus grand océan du monde ?',
                    'options' => ['Océan Pacifique', 'Océan Atlantique', 'Océan Indien', 'Océan Arctique'],
                    'correct_answer' => 0,
                    'explanation' => 'L\'océan Pacifique couvre environ un tiers de la surface terrestre.',
                    'difficulty' => 'easy'
                ],
                [
                    'question' => 'Quel est le plus haut sommet du monde ?',
                    'options' => ['Mont Everest', 'K2', 'Kangchenjunga', 'Lhotse'],
                    'correct_answer' => 0,
                    'explanation' => 'Le Mont Everest culmine à 8 848 mètres d\'altitude.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Quel est le plus grand désert du monde ?',
                    'options' => ['Sahara', 'Antarctique', 'Gobi', 'Kalahari'],
                    'correct_answer' => 0,
                    'explanation' => 'Le Sahara est le plus grand désert chaud du monde.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Quel fleuve traverse Paris ?',
                    'options' => ['Seine', 'Loire', 'Rhône', 'Garonne'],
                    'correct_answer' => 0,
                    'explanation' => 'La Seine traverse Paris et se jette dans la Manche.',
                    'difficulty' => 'easy'
                ],
                [
                    'question' => 'Quel est le plus petit pays du monde ?',
                    'options' => ['Vatican', 'Monaco', 'San Marino', 'Liechtenstein'],
                    'correct_answer' => 0,
                    'explanation' => 'Le Vatican est le plus petit État souverain du monde.',
                    'difficulty' => 'medium'
                ]
            ],
            'Sciences' => [
                [
                    'question' => 'Quel est le symbole chimique de l\'or ?',
                    'options' => ['Au', 'Ag', 'Fe', 'Cu'],
                    'correct_answer' => 0,
                    'explanation' => 'Au vient du latin "aurum" qui signifie or.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Combien de planètes dans notre système solaire ?',
                    'options' => ['8', '9', '7', '10'],
                    'correct_answer' => 0,
                    'explanation' => 'Il y a 8 planètes : Mercure, Vénus, Terre, Mars, Jupiter, Saturne, Uranus, Neptune.',
                    'difficulty' => 'easy'
                ],
                [
                    'question' => 'Quel est l\'élément le plus abondant dans l\'univers ?',
                    'options' => ['Hydrogène', 'Hélium', 'Oxygène', 'Carbone'],
                    'correct_answer' => 0,
                    'explanation' => 'L\'hydrogène représente environ 75% de la masse de l\'univers.',
                    'difficulty' => 'hard'
                ],
                [
                    'question' => 'Quel est le nom scientifique de l\'être humain ?',
                    'options' => ['Homo sapiens', 'Homo erectus', 'Homo habilis', 'Homo neanderthalensis'],
                    'correct_answer' => 0,
                    'explanation' => 'Homo sapiens signifie "homme sage" en latin.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Quel est le point d\'ébullition de l\'eau ?',
                    'options' => ['100°C', '90°C', '110°C', '80°C'],
                    'correct_answer' => 0,
                    'explanation' => 'L\'eau bout à 100°C au niveau de la mer.',
                    'difficulty' => 'easy'
                ]
            ],
            'Technologie' => [
                [
                    'question' => 'Quel est le fondateur d\'Apple ?',
                    'options' => ['Steve Jobs', 'Bill Gates', 'Mark Zuckerberg', 'Elon Musk'],
                    'correct_answer' => 0,
                    'explanation' => 'Steve Jobs a cofondé Apple avec Steve Wozniak en 1976.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Quel langage de programmation a été créé par Guido van Rossum ?',
                    'options' => ['Python', 'Java', 'C++', 'JavaScript'],
                    'correct_answer' => 0,
                    'explanation' => 'Python a été créé par Guido van Rossum en 1991.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Quel est le nom du premier navigateur web ?',
                    'options' => ['WorldWideWeb', 'Internet Explorer', 'Netscape', 'Mosaic'],
                    'correct_answer' => 0,
                    'explanation' => 'WorldWideWeb a été créé par Tim Berners-Lee en 1990.',
                    'difficulty' => 'hard'
                ],
                [
                    'question' => 'Quel est le système d\'exploitation le plus utilisé au monde ?',
                    'options' => ['Android', 'Windows', 'iOS', 'Linux'],
                    'correct_answer' => 0,
                    'explanation' => 'Android est le système d\'exploitation mobile le plus utilisé.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Quel est le nom du premier ordinateur personnel ?',
                    'options' => ['Altair 8800', 'Apple I', 'IBM PC', 'Commodore 64'],
                    'correct_answer' => 0,
                    'explanation' => 'L\'Altair 8800, sorti en 1975, est considéré comme le premier PC.',
                    'difficulty' => 'hard'
                ]
            ],
            'Cinéma' => [
                [
                    'question' => 'Quel réalisateur a dirigé "Titanic" ?',
                    'options' => ['James Cameron', 'Steven Spielberg', 'Christopher Nolan', 'Quentin Tarantino'],
                    'correct_answer' => 0,
                    'explanation' => 'James Cameron a réalisé Titanic en 1997.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Quel acteur a joué Iron Man dans l\'univers Marvel ?',
                    'options' => ['Robert Downey Jr.', 'Chris Evans', 'Chris Hemsworth', 'Mark Ruffalo'],
                    'correct_answer' => 0,
                    'explanation' => 'Robert Downey Jr. a incarné Tony Stark/Iron Man.',
                    'difficulty' => 'easy'
                ],
                [
                    'question' => 'Quel film a remporté l\'Oscar du meilleur film en 2020 ?',
                    'options' => ['Parasite', '1917', 'Joker', 'Once Upon a Time in Hollywood'],
                    'correct_answer' => 0,
                    'explanation' => 'Parasite est le premier film non-anglophone à remporter l\'Oscar du meilleur film.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Quel est le nom du studio qui a créé "Toy Story" ?',
                    'options' => ['Pixar', 'Disney', 'DreamWorks', 'Blue Sky'],
                    'correct_answer' => 0,
                    'explanation' => 'Pixar a créé le premier film d\'animation entièrement en 3D.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Quel acteur a joué le Joker dans "The Dark Knight" ?',
                    'options' => ['Heath Ledger', 'Joaquin Phoenix', 'Jared Leto', 'Jack Nicholson'],
                    'correct_answer' => 0,
                    'explanation' => 'Heath Ledger a remporté l\'Oscar posthume pour ce rôle.',
                    'difficulty' => 'medium'
                ]
            ],
            'Musique' => [
                [
                    'question' => 'Quel groupe a sorti "Bohemian Rhapsody" ?',
                    'options' => ['Queen', 'The Beatles', 'Led Zeppelin', 'Pink Floyd'],
                    'correct_answer' => 0,
                    'explanation' => 'Queen a sorti Bohemian Rhapsody en 1975.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Quel instrument joue principalement un pianiste ?',
                    'options' => ['Piano', 'Violon', 'Guitare', 'Flûte'],
                    'correct_answer' => 0,
                    'explanation' => 'Le pianiste joue du piano, un instrument à cordes frappées.',
                    'difficulty' => 'easy'
                ],
                [
                    'question' => 'Quel est le nom de scène de Madonna ?',
                    'options' => ['Madonna', 'Lady Gaga', 'Beyoncé', 'Rihanna'],
                    'correct_answer' => 0,
                    'explanation' => 'Madonna est le nom de scène de Madonna Louise Ciccone.',
                    'difficulty' => 'easy'
                ],
                [
                    'question' => 'Quel genre musical est né à la Nouvelle-Orléans ?',
                    'options' => ['Jazz', 'Blues', 'Rock', 'Hip-hop'],
                    'correct_answer' => 0,
                    'explanation' => 'Le jazz est né à la Nouvelle-Orléans au début du 20e siècle.',
                    'difficulty' => 'medium'
                ],
                [
                    'question' => 'Quel compositeur a écrit la "Symphonie n°9" ?',
                    'options' => ['Beethoven', 'Mozart', 'Bach', 'Chopin'],
                    'correct_answer' => 0,
                    'explanation' => 'Beethoven a composé sa 9e symphonie alors qu\'il était sourd.',
                    'difficulty' => 'medium'
                ]
            ]
        ];

        // Essayer de trouver une correspondance exacte
        if (isset($fallbackQuestions[$topic])) {
            $category = $fallbackQuestions[$topic];
        } else {
            // Chercher une correspondance partielle avec mots-clés
            $matchedCategory = null;
            
            // Mots-clés pour chaque catégorie
            $keywords = [
                'Sport' => ['sport', 'foot', 'football', 'basket', 'basketball', 'tennis', 'natation', 'athlétisme', 'hockey', 'rugby', 'volley', 'handball', 'badminton', 'ping-pong', 'golf', 'ski', 'snowboard', 'surf', 'escalade', 'boxe', 'mma', 'ufc', 'olympique', 'jo', 'championnat', 'coupe', 'ligue', 'équipe', 'joueur', 'entraîneur', 'stade', 'terrain', 'ballon', 'but', 'score', 'match', 'compétition'],
                'Histoire' => ['histoire', 'historique', 'guerre', 'révolution', 'empire', 'roi', 'reine', 'empereur', 'président', 'premier ministre', 'bataille', 'conquête', 'découverte', 'invention', 'civilisation', 'antiquité', 'moyen âge', 'renaissance', 'révolution industrielle', 'colonisation', 'décolonisation', 'monarchie', 'république', 'démocratie', 'dictature', 'nazisme', 'communisme', 'capitalisme'],
                'Géographie' => ['géographie', 'géographique', 'pays', 'ville', 'capitale', 'continent', 'océan', 'mer', 'fleuve', 'rivière', 'montagne', 'désert', 'forêt', 'île', 'archipel', 'péninsule', 'détroit', 'canal', 'volcan', 'climat', 'météo', 'population', 'démographie', 'économie', 'ressources', 'agriculture', 'industrie', 'tourisme', 'culture', 'langue', 'religion'],
                'Sciences' => ['science', 'scientifique', 'physique', 'chimie', 'biologie', 'mathématiques', 'maths', 'astronomie', 'astrophysique', 'géologie', 'météorologie', 'médecine', 'anatomie', 'physiologie', 'génétique', 'évolution', 'écologie', 'environnement', 'énergie', 'électricité', 'magnétisme', 'optique', 'mécanique', 'thermodynamique', 'atome', 'molécule', 'cellule', 'organe', 'système'],
                'Technologie' => ['technologie', 'tech', 'informatique', 'ordinateur', 'programmation', 'code', 'logiciel', 'hardware', 'software', 'internet', 'web', 'site', 'application', 'app', 'smartphone', 'tablette', 'laptop', 'pc', 'mac', 'windows', 'linux', 'android', 'ios', 'python', 'java', 'javascript', 'html', 'css', 'php', 'sql', 'ai', 'intelligence artificielle', 'robot', 'automation', 'cyber', 'digital', 'virtuel', 'réelité virtuelle', 'blockchain', 'crypto', 'bitcoin'],
                'Cinéma' => ['cinéma', 'film', 'movie', 'réalisateur', 'acteur', 'actrice', 'scénario', 'scénariste', 'producteur', 'studio', 'hollywood', 'bollywood', 'oscar', 'césar', 'cannes', 'festival', 'documentaire', 'animation', '3d', 'effets spéciaux', 'box office', 'critique', 'critique de film', 'cinéphile', 'cinémathèque', 'projection', 'salle de cinéma', 'bobine', 'pellicule', 'numérique'],
                'Musique' => ['musique', 'musical', 'chanson', 'chanteur', 'chanteuse', 'groupe', 'band', 'album', 'single', 'hit', 'concert', 'festival', 'instrument', 'piano', 'guitare', 'violon', 'batterie', 'basse', 'flûte', 'saxophone', 'trompette', 'accordéon', 'harpe', 'genre', 'rock', 'pop', 'jazz', 'blues', 'classique', 'opéra', 'rap', 'hip-hop', 'électro', 'reggae', 'country', 'folk', 'métal', 'punk', 'disco', 'funk', 'soul', 'r&b']
            ];
            
            // Normaliser le topic (minuscules, sans accents)
            $normalizedTopic = strtolower(trim($topic));
            $normalizedTopic = str_replace(['é', 'è', 'ê', 'ë', 'à', 'â', 'ä', 'î', 'ï', 'ô', 'ö', 'ù', 'û', 'ü', 'ç'], ['e', 'e', 'e', 'e', 'a', 'a', 'a', 'i', 'i', 'o', 'o', 'u', 'u', 'u', 'c'], $normalizedTopic);
            
            // Chercher une correspondance avec les mots-clés
            foreach ($keywords as $category => $categoryKeywords) {
                foreach ($categoryKeywords as $keyword) {
                    if (strpos($normalizedTopic, $keyword) !== false) {
                        $matchedCategory = $category;
                        break 2;
                    }
                }
            }
            
            // Si pas de correspondance, utiliser une catégorie aléatoire
            if ($matchedCategory === null) {
                $categories = array_keys($fallbackQuestions);
                $matchedCategory = $categories[array_rand($categories)];
            }
            
            $category = $fallbackQuestions[$matchedCategory];
        }

        $questions = array_slice($category, 0, $questionCount);

        return [
            'title' => "Quiz sur $topic",
            'description' => "Testez vos connaissances sur $topic",
            'questions' => $questions
        ];
    }

    private function generateFallbackExplanation(string $question, string $correctAnswer): string
    {
        return "Explication : $correctAnswer est la bonne réponse. Cette question teste votre compréhension du sujet.";
    }

    private function generateFallbackSuggestions(array $wrongAnswers, string $topic): array
    {
        $suggestionsByTopic = [
            'Sport' => [
                [
                    'title' => 'Réviser les règles des sports populaires',
                    'description' => 'Consultez les règles officielles du football, basketball, tennis et autres sports majeurs.',
                    'resources' => ['Fédérations sportives officielles', 'Manuels de règles sportives'],
                    'practice_questions' => ['Combien de joueurs au football ?', 'Quelle est la durée d\'un match de basketball ?']
                ],
                [
                    'title' => 'Étudier l\'histoire des Jeux Olympiques',
                    'description' => 'Apprenez l\'histoire des Jeux Olympiques modernes et leurs records.',
                    'resources' => ['Site officiel des JO', 'Documentaires sportifs'],
                    'practice_questions' => ['Quand ont eu lieu les premiers JO modernes ?', 'Quel pays a organisé le plus de JO ?']
                ],
                [
                    'title' => 'Connaître les champions et records',
                    'description' => 'Mémorisez les grands champions et records du monde dans différents sports.',
                    'resources' => ['Biographies de sportifs', 'Livres de records'],
                    'practice_questions' => ['Qui détient le record du 100m ?', 'Quel pays a le plus de titres de football ?']
                ]
            ],
            'Histoire' => [
                [
                    'title' => 'Réviser les grandes périodes historiques',
                    'description' => 'Étudiez chronologiquement les grandes périodes : Antiquité, Moyen Âge, Renaissance, etc.',
                    'resources' => ['Manuels d\'histoire', 'Documentaires historiques'],
                    'practice_questions' => ['Quand a eu lieu la chute de Rome ?', 'Qu\'est-ce que la Renaissance ?']
                ],
                [
                    'title' => 'Mémoriser les dates importantes',
                    'description' => 'Apprenez les dates clés de l\'histoire française et mondiale.',
                    'resources' => ['Frises chronologiques', 'Applications de révision'],
                    'practice_questions' => ['Quand a eu lieu la Révolution française ?', 'Quand a eu lieu la Seconde Guerre mondiale ?']
                ],
                [
                    'title' => 'Étudier les personnages historiques',
                    'description' => 'Concentrez-vous sur les grandes figures historiques et leurs actions.',
                    'resources' => ['Biographies', 'Portraits historiques'],
                    'practice_questions' => ['Qui était Napoléon Bonaparte ?', 'Quel était le rôle de Louis XIV ?']
                ]
            ],
            'Géographie' => [
                [
                    'title' => 'Apprendre les capitales et pays',
                    'description' => 'Mémorisez les capitales des pays du monde et leur localisation.',
                    'resources' => ['Atlas géographiques', 'Applications de géographie'],
                    'practice_questions' => ['Quelle est la capitale du Japon ?', 'Où se trouve le Brésil ?']
                ],
                [
                    'title' => 'Étudier les reliefs et climats',
                    'description' => 'Concentrez-vous sur les montagnes, océans, déserts et zones climatiques.',
                    'resources' => ['Cartes physiques', 'Documentaires géographiques'],
                    'practice_questions' => ['Quel est le plus haut sommet ?', 'Quel est le plus grand océan ?']
                ],
                [
                    'title' => 'Connaître la géographie française',
                    'description' => 'Apprenez les régions, départements, fleuves et villes de France.',
                    'resources' => ['Cartes de France', 'Guides touristiques'],
                    'practice_questions' => ['Quel fleuve traverse Paris ?', 'Combien de régions en France ?']
                ]
            ],
            'Sciences' => [
                [
                    'title' => 'Réviser les éléments chimiques',
                    'description' => 'Apprenez le tableau périodique et les symboles des éléments.',
                    'resources' => ['Tableau périodique', 'Applications de chimie'],
                    'practice_questions' => ['Quel est le symbole de l\'or ?', 'Combien d\'éléments dans le tableau périodique ?']
                ],
                [
                    'title' => 'Étudier l\'astronomie',
                    'description' => 'Concentrez-vous sur le système solaire et l\'univers.',
                    'resources' => ['Livres d\'astronomie', 'Observatoires virtuels'],
                    'practice_questions' => ['Combien de planètes dans le système solaire ?', 'Quel est l\'élément le plus abondant ?']
                ],
                [
                    'title' => 'Comprendre la biologie',
                    'description' => 'Apprenez les bases de la biologie et de l\'évolution.',
                    'resources' => ['Manuels de biologie', 'Documentaires scientifiques'],
                    'practice_questions' => ['Quel est le nom scientifique de l\'homme ?', 'Qu\'est-ce que l\'évolution ?']
                ]
            ],
            'Technologie' => [
                [
                    'title' => 'Apprendre l\'histoire de l\'informatique',
                    'description' => 'Étudiez l\'évolution des ordinateurs et d\'Internet.',
                    'resources' => ['Livres d\'histoire informatique', 'Musées de l\'informatique'],
                    'practice_questions' => ['Quand a été créé le premier ordinateur ?', 'Qui a créé Internet ?']
                ],
                [
                    'title' => 'Connaître les langages de programmation',
                    'description' => 'Apprenez les bases des langages de programmation populaires.',
                    'resources' => ['Tutoriels en ligne', 'Cours de programmation'],
                    'practice_questions' => ['Qui a créé Python ?', 'Quel langage pour le web ?']
                ],
                [
                    'title' => 'Étudier les entreprises technologiques',
                    'description' => 'Concentrez-vous sur l\'histoire des grandes entreprises tech.',
                    'resources' => ['Biographies de fondateurs', 'Documentaires tech'],
                    'practice_questions' => ['Qui a fondé Apple ?', 'Quand a été créé Google ?']
                ]
            ],
            'Cinéma' => [
                [
                    'title' => 'Découvrir l\'histoire du cinéma',
                    'description' => 'Apprenez l\'évolution du cinéma des frères Lumière à nos jours.',
                    'resources' => ['Livres d\'histoire du cinéma', 'Documentaires sur le cinéma'],
                    'practice_questions' => ['Quand a été créé le cinéma ?', 'Qui sont les frères Lumière ?']
                ],
                [
                    'title' => 'Connaître les réalisateurs célèbres',
                    'description' => 'Étudiez les œuvres des grands réalisateurs et leurs styles.',
                    'resources' => ['Rétrospectives de films', 'Biographies de réalisateurs'],
                    'practice_questions' => ['Qui a réalisé Titanic ?', 'Quel style pour Hitchcock ?']
                ],
                [
                    'title' => 'Mémoriser les films cultes',
                    'description' => 'Apprenez les films marquants et leurs récompenses.',
                    'resources' => ['Listes de films cultes', 'Cérémonies des Oscars'],
                    'practice_questions' => ['Quel film a remporté l\'Oscar 2020 ?', 'Quels sont les films cultes ?']
                ]
            ],
            'Musique' => [
                [
                    'title' => 'Étudier l\'histoire de la musique',
                    'description' => 'Apprenez l\'évolution musicale de la préhistoire à nos jours.',
                    'resources' => ['Livres d\'histoire musicale', 'Documentaires musicaux'],
                    'practice_questions' => ['Quand est né le jazz ?', 'Quels sont les genres musicaux ?']
                ],
                [
                    'title' => 'Connaître les instruments de musique',
                    'description' => 'Apprenez à identifier les instruments et leurs familles.',
                    'resources' => ['Encyclopédies musicales', 'Concerts virtuels'],
                    'practice_questions' => ['Quel instrument joue un pianiste ?', 'Quelle famille pour le violon ?']
                ],
                [
                    'title' => 'Découvrir les artistes légendaires',
                    'description' => 'Étudiez la vie et l\'œuvre des grands musiciens.',
                    'resources' => ['Biographies d\'artistes', 'Discographies complètes'],
                    'practice_questions' => ['Quel groupe a sorti Bohemian Rhapsody ?', 'Qui était Beethoven ?']
                ]
            ]
        ];

        // Chercher des suggestions spécifiques au sujet
        $matchedSuggestions = null;
        
        // Mots-clés pour chaque catégorie (même que dans generateFallbackQuiz)
        $keywords = [
            'Sport' => ['sport', 'foot', 'football', 'basket', 'basketball', 'tennis', 'natation', 'athlétisme', 'hockey', 'rugby', 'volley', 'handball', 'badminton', 'ping-pong', 'golf', 'ski', 'snowboard', 'surf', 'escalade', 'boxe', 'mma', 'ufc', 'olympique', 'jo', 'championnat', 'coupe', 'ligue', 'équipe', 'joueur', 'entraîneur', 'stade', 'terrain', 'ballon', 'but', 'score', 'match', 'compétition'],
            'Histoire' => ['histoire', 'historique', 'guerre', 'révolution', 'empire', 'roi', 'reine', 'empereur', 'président', 'premier ministre', 'bataille', 'conquête', 'découverte', 'invention', 'civilisation', 'antiquité', 'moyen âge', 'renaissance', 'révolution industrielle', 'colonisation', 'décolonisation', 'monarchie', 'république', 'démocratie', 'dictature', 'nazisme', 'communisme', 'capitalisme'],
            'Géographie' => ['géographie', 'géographique', 'pays', 'ville', 'capitale', 'continent', 'océan', 'mer', 'fleuve', 'rivière', 'montagne', 'désert', 'forêt', 'île', 'archipel', 'péninsule', 'détroit', 'canal', 'volcan', 'climat', 'météo', 'population', 'démographie', 'économie', 'ressources', 'agriculture', 'industrie', 'tourisme', 'culture', 'langue', 'religion'],
            'Sciences' => ['science', 'scientifique', 'physique', 'chimie', 'biologie', 'mathématiques', 'maths', 'astronomie', 'astrophysique', 'géologie', 'météorologie', 'médecine', 'anatomie', 'physiologie', 'génétique', 'évolution', 'écologie', 'environnement', 'énergie', 'électricité', 'magnétisme', 'optique', 'mécanique', 'thermodynamique', 'atome', 'molécule', 'cellule', 'organe', 'système'],
            'Technologie' => ['technologie', 'tech', 'informatique', 'ordinateur', 'programmation', 'code', 'logiciel', 'hardware', 'software', 'internet', 'web', 'site', 'application', 'app', 'smartphone', 'tablette', 'laptop', 'pc', 'mac', 'windows', 'linux', 'android', 'ios', 'python', 'java', 'javascript', 'html', 'css', 'php', 'sql', 'ai', 'intelligence artificielle', 'robot', 'automation', 'cyber', 'digital', 'virtuel', 'réelité virtuelle', 'blockchain', 'crypto', 'bitcoin'],
            'Cinéma' => ['cinéma', 'film', 'movie', 'réalisateur', 'acteur', 'actrice', 'scénario', 'scénariste', 'producteur', 'studio', 'hollywood', 'bollywood', 'oscar', 'césar', 'cannes', 'festival', 'documentaire', 'animation', '3d', 'effets spéciaux', 'box office', 'critique', 'critique de film', 'cinéphile', 'cinémathèque', 'projection', 'salle de cinéma', 'bobine', 'pellicule', 'numérique'],
            'Musique' => ['musique', 'musical', 'chanson', 'chanteur', 'chanteuse', 'groupe', 'band', 'album', 'single', 'hit', 'concert', 'festival', 'instrument', 'piano', 'guitare', 'violon', 'batterie', 'basse', 'flûte', 'saxophone', 'trompette', 'accordéon', 'harpe', 'genre', 'rock', 'pop', 'jazz', 'blues', 'classique', 'opéra', 'rap', 'hip-hop', 'électro', 'reggae', 'country', 'folk', 'métal', 'punk', 'disco', 'funk', 'soul', 'r&b']
        ];
        
        // Normaliser le topic (minuscules, sans accents)
        $normalizedTopic = strtolower(trim($topic));
        $normalizedTopic = str_replace(['é', 'è', 'ê', 'ë', 'à', 'â', 'ä', 'î', 'ï', 'ô', 'ö', 'ù', 'û', 'ü', 'ç'], ['e', 'e', 'e', 'e', 'a', 'a', 'a', 'i', 'i', 'o', 'o', 'u', 'u', 'u', 'c'], $normalizedTopic);
        
        // Chercher une correspondance avec les mots-clés
        foreach ($keywords as $category => $categoryKeywords) {
            foreach ($categoryKeywords as $keyword) {
                if (strpos($normalizedTopic, $keyword) !== false) {
                    $matchedSuggestions = $suggestionsByTopic[$category] ?? null;
                    break 2;
                }
            }
        }

        // Si pas de correspondance, utiliser des suggestions générales
        if ($matchedSuggestions === null) {
            return [
                [
                    'title' => "Réviser les bases de $topic",
                    'description' => "Consultez les concepts fondamentaux pour mieux comprendre le sujet.",
                    'resources' => ["Manuels scolaires", "Vidéos éducatives"],
                    'practice_questions' => ["Question de révision 1", "Question de révision 2"]
                ],
                [
                    'title' => "Faire des exercices pratiques",
                    'description' => "La pratique est la meilleure façon de progresser.",
                    'resources' => ["Exercices en ligne", "Quiz interactifs"],
                    'practice_questions' => ["Exercice 1", "Exercice 2"]
                ],
                [
                    'title' => "Demander de l'aide",
                    'description' => "N'hésitez pas à poser des questions à vos professeurs ou camarades.",
                    'resources' => ["Forum d'entraide", "Tutorat"],
                    'practice_questions' => ["Question d'aide 1", "Question d'aide 2"]
                ]
            ];
        }

        return $matchedSuggestions;
    }
} 