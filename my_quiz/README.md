# 🎮 MyQuizz - Plateforme de Quiz Multijoueur

Une application web moderne de quiz développée avec Symfony 7, offrant des fonctionnalités multijoueur avancées, des quiz générés par IA, et une expérience utilisateur immersive.

## ✨ Fonctionnalités Principales

### 🎯 Quiz Classiques
- Création et gestion de quiz personnalisés
- Système de catégories et difficultés
- Historique des quiz joués
- Statistiques détaillées et classements

### 🤖 Quiz IA
- Génération automatique de questions avec OpenAI GPT
- Questions de fallback si l'API n'est pas disponible
- Interface intuitive pour la création de quiz IA
- Suggestions de révision personnalisées

### 👥 Système Multijoueur
- **Équipes** : Création, gestion et quiz d'équipe
- **Défis** : Défis entre amis avec système de points
- **Classements** : Classements par équipe et individuel
- **Notifications** : Notifications par email pour les événements

### 🏆 Gamification
- Système de points et badges
- Progression des utilisateurs
- Classements dynamiques
- Récompenses pour les performances

### 🎨 Interface Moderne
- Design responsive et accessible
- Thème sombre/clair avec persistance
- Animations fluides et transitions
- Notifications push et service worker

## 🚀 Technologies Utilisées

- **Backend** : Symfony 7, PHP 8.2+
- **Base de données** : MySQL/PostgreSQL avec Doctrine ORM
- **Frontend** : Twig, Bootstrap 5, JavaScript ES6+
- **IA** : OpenAI GPT-3.5 API
- **Déploiement** : Render (gratuit)

## 📋 Prérequis

- PHP 8.2 ou supérieur
- Composer
- MySQL/PostgreSQL
- Symfony CLI (optionnel)

## 🛠️ Installation

1. **Cloner le repository**
   ```bash
   git clone https://github.com/LoucmanMachababy/MyQuizz.git
   cd MyQuizz/my_quiz
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configurer l'environnement**
   ```bash
   cp .env .env.local
   # Modifier .env.local avec vos paramètres de base de données
   ```

4. **Créer la base de données**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   php bin/console doctrine:fixtures:load
   ```

5. **Lancer le serveur**
   ```bash
   symfony server:start
   ```

## 📱 Fonctionnalités Avancées

### Système d'Équipes
- Création d'équipes publiques ou privées
- Gestion des membres et permissions
- Quiz d'équipe avec participants multiples
- Classements par équipe

### Système de Défis
- Défis entre utilisateurs
- Acceptation/refus des défis
- Système de points et récompenses
- Historique des défis

### Quiz IA
- Génération automatique de questions
- Questions par catégorie et difficulté
- Système de fallback robuste
- Interface utilisateur intuitive

## 🔧 Configuration

### Variables d'environnement importantes

```env
# Base de données
DATABASE_URL="mysql://user:password@localhost/myquiz"

# Clé secrète Symfony
APP_SECRET="your-secret-key"

# OpenAI API (optionnel)
OPENAI_API_KEY="your-openai-api-key"

# Configuration email
MAILER_DSN="smtp://user:pass@smtp.example.com:25"
```

## 📊 Structure du Projet

```
my_quiz/
├── src/
│   ├── Controller/          # Contrôleurs de l'application
│   ├── Entity/             # Entités Doctrine
│   ├── Repository/         # Repositories pour les requêtes
│   ├── Service/            # Services métier
│   └── Security/           # Configuration de sécurité
├── templates/              # Templates Twig
├── public/                 # Fichiers publics
├── migrations/             # Migrations de base de données
└── config/                 # Configuration Symfony
```

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :

1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Commiter vos changements
4. Pousser vers la branche
5. Ouvrir une Pull Request

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 👨‍💻 Auteur

**Loucman Machababy**
- GitHub: [@LoucmanMachababy](https://github.com/LoucmanMachababy)

## 🙏 Remerciements

- Symfony pour le framework exceptionnel
- OpenAI pour l'API GPT
- Bootstrap pour le design
- La communauté Symfony pour le support

---

⭐ N'oubliez pas de donner une étoile si ce projet vous plaît ! 