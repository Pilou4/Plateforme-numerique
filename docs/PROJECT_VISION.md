# Plateforme Numérique

## 1. Vision du projet

**Plateforme Numérique** est un projet développé avec Symfony 8, PHP 8.5+, MySQL et Docker.

L'objectif est de construire progressivement une plateforme numérique moderne qui servira à la fois de :

* site de présentation de la future entreprise ;
* portfolio professionnel ;
* laboratoire technique ;
* support d'apprentissage ;
* plateforme interne ;
* espace client à terme ;
* centre de formation à terme ;
* base pour de futurs outils métier et services SaaS.

Le projet doit permettre de développer progressivement les compétences techniques nécessaires à la création et à la gestion d'une future entreprise informatique.

---

# 2. Philosophie du projet

Le projet est volontairement construit progressivement.

Chaque nouvelle compétence acquise doit, lorsque cela est pertinent, être intégrée au projet afin d'être réellement mise en pratique.

Exemples :

* Docker → environnement de développement et déploiement ;
* Git → gestion professionnelle du code ;
* PHPUnit → tests ;
* GitHub Actions → CI/CD ;
* Python → futurs services spécialisés ;
* FastAPI → APIs Python ;
* IA / LLM → fonctionnalités intelligentes ;
* RAG → exploitation de connaissances ;
* MCP → intégration avec des outils et services ;
* cybersécurité → sécurisation de la plateforme ;
* SEO → optimisation du site public.

Le projet constitue donc à la fois **un produit, un laboratoire et un support d'apprentissage**.

---

# 3. Objectifs techniques

Le projet doit permettre de renforcer progressivement les compétences suivantes :

### Backend

* PHP 8.5+
* Symfony 8
* Doctrine
* MySQL
* API REST
* architecture modulaire
* services Symfony
* événements
* sécurité Symfony
* authentification et autorisation

### Frontend

* Twig
* HTML5
* CSS3 classique
* JavaScript
* Symfony UX / Stimulus lorsque pertinent
* responsive design
* accessibilité

### Infrastructure

* Docker
* Docker Compose
* Nginx
* PHP-FPM
* Linux
* Git
* GitHub
* CI/CD

### Qualité

* PHPUnit
* tests unitaires
* tests fonctionnels
* tests d'intégration
* analyse statique
* qualité du code
* documentation

### IA et technologies futures

* Python
* FastAPI
* LLM
* RAG
* agents
* MCP
* intégration d'APIs d'IA

---

# 4. Architecture des contrôleurs

L'organisation des contrôleurs Symfony doit être claire dès le début afin de séparer les différentes responsabilités de l'application.

## 4.1 Contrôleurs publics

Les contrôleurs liés aux pages publiques du site sont placés directement dans :

```text
src/Controller/
```

Exemple :

```text
src/
└── Controller/
    └── HomeController.php
```

`HomeController` est notamment destiné à gérer la page d'accueil publique.

---

## 4.2 Contrôleurs de l'application

Toutes les fonctionnalités de l'application interne sont regroupées dans :

```text
src/Controller/App/
```

Exemple :

```text
src/
└── Controller/
    └── App/
        └── DashboardController.php
```

La règle est la suivante :

> **Tous les contrôleurs correspondant à l'application interne doivent être placés dans `Controller/App/`.**

Exemples futurs :

```text
src/Controller/App/
├── DashboardController.php
├── ProjectController.php
├── LearningController.php
├── WebLanguagesController.php
├── ProfileController.php
└── ...
```

Les routes associées pourront utiliser un préfixe tel que :

```text
/app
```

Exemples :

```text
/app
/app/project
/app/learning
/app/web-languages
```

---

## 4.3 Contrôleurs API

Les contrôleurs destinés aux APIs sont regroupés dans :

```text
src/Controller/Api/
```

Exemple :

```text
src/
└── Controller/
    └── Api/
        ├── ProjectController.php
        ├── LearningController.php
        └── ...
```

La règle est la suivante :

> **Toutes les routes API doivent être gérées par des contrôleurs situés dans `Controller/Api/`.**

Les routes API utiliseront à terme un préfixe permettant de les distinguer clairement des routes HTML classiques.

Exemple :

```text
/api/projects
/api/learning
/api/...
```

Cette séparation permettra notamment de distinguer clairement :

```text
Controller/
├── HomeController.php          # Site public
├── App/                        # Application interne
│   └── ...
└── Api/                        # API
    └── ...
```

---

# 5. Principe de séparation

La séparation des contrôleurs doit rester cohérente avec les responsabilités de chaque partie de la plateforme.

```text
Site public
    ↓
Controller/

Application interne
    ↓
Controller/App/

API
    ↓
Controller/Api/
```

Cette organisation pourra évoluer si les besoins du projet deviennent plus complexes.

Toute modification importante de cette architecture devra être documentée et discutée avant d'être mise en place.

---

# 6. Règle importante concernant l'architecture

L'architecture ne doit pas être complexifiée prématurément.

Une nouvelle couche, un nouveau dossier ou un nouveau système architectural ne doit être ajouté que lorsqu'il répond à un besoin réel.

**Règle fondamentale :**

> Ne jamais modifier ou créer une architecture importante sans expliquer le raisonnement, les avantages, les inconvénients et l'impact sur le projet, puis obtenir la validation du développeur.

L'objectif est de construire une architecture :

* simple ;
* compréhensible ;
* maintenable ;
* sécurisée ;
* testable ;
* évolutive.

---

# 7. Méthode de développement

Chaque fonctionnalité doit progressivement passer par les étapes suivantes :

1. définir le besoin ;
2. réfléchir à l'architecture ;
3. développer le backend ;
4. gérer la base de données si nécessaire ;
5. développer le HTML/Twig ;
6. ajouter le CSS ;
7. ajouter JavaScript uniquement si nécessaire ;
8. gérer la sécurité ;
9. gérer le SEO pour les pages publiques ;
10. écrire les tests pertinents ;
11. vérifier la qualité du code ;
12. documenter si nécessaire ;
13. effectuer une revue avant commit.

---

# 8. Utilisation de l'IA

L'IA est utilisée comme **assistant de développement**, et non comme développeur autonome.

Elle peut notamment :

* expliquer une technologie ;
* proposer une architecture ;
* générer du code répétitif ;
* proposer des tests ;
* analyser du code ;
* détecter des problèmes potentiels ;
* proposer des améliorations ;
* aider à documenter le projet ;
* aider à apprendre de nouvelles technologies.

Cependant :

> **Le développeur reste responsable des décisions techniques et du code intégré au projet.**

Tout code généré par une IA doit être :

1. compris ;
2. vérifié ;
3. adapté au contexte du projet ;
4. testé ;
5. validé avant intégration.

L'IA ne doit pas prendre de décision architecturale importante sans explication et validation.

---

# 9. Priorités du projet

L'ordre de priorité est le suivant :

1. fonctionnalité ;
2. architecture et qualité du code ;
3. sécurité ;
4. tests ;
5. SEO ;
6. accessibilité ;
7. performance ;
8. documentation ;
9. amélioration visuelle et animations.

Le design et les animations pourront être améliorés progressivement.

La priorité initiale est de construire une base technique solide.

---

# 10. Stack V1

### Backend

* PHP 8.5+
* Symfony 8
* Doctrine

### Base de données

* MySQL

### Frontend

* Twig
* HTML5
* CSS3 classique
* JavaScript

### Infrastructure

* Docker
* Docker Compose
* Nginx
* PHP-FPM

### Outils

* Git
* GitHub
* PHPUnit

---

# 11. Premiers modules

Les premiers modules de l'application seront notamment :

### Projet

Présentation du projet :

* vision ;
* objectifs ;
* architecture ;
* technologies ;
* roadmap ;
* organisation ;
* principes de développement.

### Learning

Espace permettant de suivre :

* les technologies à apprendre ;
* les technologies à revoir ;
* les notions étudiées ;
* la progression ;
* les expérimentations.

### Web Languages

Base de connaissances permettant de documenter les langages et technologies du Web :

* HTML ;
* CSS ;
* JavaScript ;
* PHP ;
* SQL ;
* HTTP ;
* etc.

Cette partie pourra également servir de base à de futurs contenus pédagogiques.

---

# 12. Évolution prévue

La plateforme pourra progressivement intégrer :

* authentification ;
* gestion des utilisateurs ;
* espace personnel ;
* gestion de projets ;
* CRM ;
* espace client ;
* centre de formation ;
* outils métier ;
* APIs ;
* services Python ;
* fonctionnalités IA ;
* outils de cybersécurité ;
* automatisations ;
* monitoring ;
* administration ;
* futurs produits SaaS.

Ces fonctionnalités seront ajoutées progressivement en fonction des besoins réels.

---

# 13. Règle générale

Le projet doit rester :

> **simple au départ, solide techniquement, documenté et capable d'évoluer progressivement.**

L'objectif n'est pas de construire immédiatement une architecture complexe.

L'objectif est de construire **une base professionnelle qui pourra grandir avec les compétences, les besoins et la future entreprise**.
