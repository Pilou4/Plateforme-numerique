<?php

namespace App\Content;

/**
 * Contenu des pages Services du site public (textes provisoires, à ajuster).
 *
 * Tout le texte est ici, séparé des contrôleurs et des templates :
 * pour modifier une page, on ne touche qu'à ce fichier. Plus tard, ce contenu
 * pourra passer en base de données (avec une page d'édition dans l'app).
 *
 * Chaque service :
 *   slug          adresse de la page : /services/{slug}
 *   title         titre
 *   icon          icône dans public/images/icones/
 *   summary       phrase courte (cartes de l'accueil et de la liste des services)
 *   lead          présentation en haut de la page
 *   offers        ce qui est proposé : [titre, texte]
 *   examples      exemples concrets de réalisations
 *   technologies  outils utilisés
 */
final class ServiceCatalog
{
    private const array SERVICES = [
        [
            'slug' => 'applications-web',
            'title' => 'Applications web',
            'icon' => 'service-web.svg',
            'summary' => 'Sites et applications sur mesure avec Symfony, pensés pour évoluer.',
            'lead' => 'Des sites et des applications web construits sur des bases solides : un code clair, testé et sécurisé, qui peut évoluer avec votre activité sans tout reprendre à zéro.',
            'offers' => [
                ['Site vitrine', 'Un site rapide et bien référencé pour présenter votre activité, que vous pouvez mettre à jour vous-même.'],
                ['Application sur mesure', 'Un outil en ligne adapté à votre besoin précis : espace client, réservation, gestion de contenus…'],
                ['Reprise et évolution', 'Ajout de fonctionnalités, correction ou modernisation d\'une application existante.'],
                ['API', 'Des échanges de données propres entre vos outils, vos applications et vos partenaires.'],
            ],
            'examples' => [
                'Un espace client pour suivre ses commandes et ses factures',
                'Un site de présentation avec formulaire de contact et prise de rendez-vous',
                'Une plateforme interne pour centraliser les projets d\'une équipe',
            ],
            'technologies' => ['PHP', 'Symfony', 'MySQL', 'Twig', 'JavaScript', 'Docker'],
        ],
        [
            'slug' => 'intelligence-artificielle',
            'title' => 'Intelligence artificielle',
            'icon' => 'service-ia.svg',
            'summary' => 'Assistants, recherche dans vos documents, intégration de LLM.',
            'lead' => 'Intégrer l\'intelligence artificielle là où elle apporte un vrai gain : répondre plus vite, retrouver une information, rédiger ou analyser, en gardant la maîtrise de vos données.',
            'offers' => [
                ['Assistant sur mesure', 'Un assistant qui connaît votre activité et répond à vos clients ou à votre équipe.'],
                ['Recherche dans vos documents', 'Interroger vos documents en langage naturel (RAG) : procédures, contrats, documentation…'],
                ['Intégration dans vos outils', 'Ajouter des fonctions d\'IA à une application existante grâce aux API des modèles de langage.'],
                ['Conseil et cadrage', 'Identifier les usages utiles, les limites et les risques avant de se lancer.'],
            ],
            'examples' => [
                'Un assistant qui répond aux questions fréquentes à partir de votre documentation',
                'Un résumé automatique des comptes rendus et des e-mails',
                'Une recherche intelligente dans une base de documents internes',
            ],
            'technologies' => ['LLM', 'RAG', 'Python', 'FastAPI', 'MCP', 'APIs d\'IA'],
        ],
        [
            'slug' => 'automatisation',
            'title' => 'Automatisation',
            'icon' => 'service-automatisation.svg',
            'summary' => 'Moins de tâches répétitives grâce à des outils qui travaillent pour vous.',
            'lead' => 'Automatisez les tâches répétitives, optimisez vos processus et libérez du temps pour vous concentrer sur votre activité.',
            'offers' => [
                ['Analyse de vos processus', 'Repérer ensemble les tâches répétitives qui prennent du temps et peuvent être automatisées.'],
                ['Connexion de vos outils', 'Faire communiquer vos logiciels entre eux pour éviter les doubles saisies.'],
                ['Traitements automatiques', 'Génération de documents, envois d\'e-mails, relances, exports et rapports programmés.'],
                ['Suivi et alertes', 'Être prévenu quand quelque chose demande votre attention, sans tout surveiller à la main.'],
            ],
            'examples' => [
                'La création automatique des devis et des factures à partir d\'un formulaire',
                'Un rapport hebdomadaire envoyé chaque lundi matin',
                'La synchronisation des contacts entre plusieurs outils',
            ],
            'technologies' => ['Symfony', 'Python', 'APIs', 'Tâches planifiées', 'Webhooks'],
        ],
        [
            'slug' => 'cybersecurite',
            'title' => 'Cybersécurité',
            'icon' => 'service-securite.svg',
            'summary' => 'Audit, bonnes pratiques et sécurisation de vos outils en ligne.',
            'lead' => 'La sécurité n\'est pas une option : protéger vos données et celles de vos clients, réduire les risques et savoir réagir en cas de problème.',
            'offers' => [
                ['Audit de sécurité', 'Un état des lieux de votre site ou de votre application, avec des recommandations claires et priorisées.'],
                ['Sécurisation', 'Correction des failles, mises à jour, configuration des en-têtes et des accès.'],
                ['Bonnes pratiques', 'Mots de passe, sauvegardes, droits d\'accès : des règles simples pour éviter les incidents.'],
                ['Sensibilisation', 'Former votre équipe à reconnaître les tentatives d\'hameçonnage et les risques courants.'],
            ],
            'examples' => [
                'La vérification des failles courantes d\'un site (injection, XSS, accès non protégés)',
                'La mise en place de sauvegardes automatiques et testées',
                'La révision des droits d\'accès d\'une application interne',
            ],
            'technologies' => ['OWASP', 'HTTPS', 'CSP', 'Sauvegardes', 'Linux'],
        ],
        [
            'slug' => 'formation',
            'title' => 'Formation',
            'icon' => 'service-formation.svg',
            'summary' => 'Accompagnement pour prendre en main le numérique et ses outils.',
            'lead' => 'Un accompagnement concret et adapté à votre niveau, pour gagner en autonomie sur vos outils numériques et sur les nouvelles technologies.',
            'offers' => [
                ['Prise en main d\'outils', 'Apprendre à utiliser efficacement les outils de votre quotidien.'],
                ['Initiation à l\'IA', 'Comprendre ce que l\'intelligence artificielle peut faire pour vous, et comment bien l\'utiliser.'],
                ['Développement web', 'Découvrir ou progresser en HTML, CSS, JavaScript, PHP et Symfony.'],
                ['Supports sur mesure', 'Des guides et des tutoriels écrits pour vos outils et vos usages.'],
            ],
            'examples' => [
                'Une demi-journée pour apprendre à bien utiliser un assistant IA au travail',
                'Un accompagnement pour mettre à jour son site soi-même',
                'Des tutoriels internes pour une nouvelle application',
            ],
            'technologies' => ['IA', 'HTML', 'CSS', 'JavaScript', 'PHP', 'Outils bureautiques'],
        ],
        [
            'slug' => 'outils-metier',
            'title' => 'Outils métier',
            'icon' => 'service-outils.svg',
            'summary' => 'Des applications internes adaptées à votre façon de travailler.',
            'lead' => 'Quand les logiciels du commerce ne correspondent pas à votre façon de travailler, un outil sur mesure simplifie le quotidien de toute l\'équipe.',
            'offers' => [
                ['Gestion interne', 'Suivi de projets, de clients, de stocks ou d\'interventions, dans un outil unique.'],
                ['Tableaux de bord', 'Les chiffres importants de votre activité, à jour et lisibles en un coup d\'œil.'],
                ['Remplacement de tableurs', 'Transformer des fichiers Excel partagés en une application fiable et multi-utilisateurs.'],
                ['Évolution progressive', 'Commencer petit, puis ajouter les fonctionnalités au fur et à mesure des besoins.'],
            ],
            'examples' => [
                'Un outil de suivi des interventions pour une équipe de techniciens',
                'Un tableau de bord des ventes mis à jour automatiquement',
                'Une gestion de planning partagée entre plusieurs personnes',
            ],
            'technologies' => ['Symfony', 'MySQL', 'JavaScript', 'Docker', 'APIs'],
        ],
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function findAll(): array
    {
        return self::SERVICES;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array
    {
        foreach (self::SERVICES as $service) {
            if ($service['slug'] === $slug) {
                return $service;
            }
        }

        return null;
    }
}
