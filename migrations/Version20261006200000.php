<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Projets :
 * - tables project et project_section ;
 * - création du projet « Plateforme Numérique » avec sa présentation (reprise de l'ancienne page Projet) ;
 * - rattachement de toutes les tâches existantes à ce projet ;
 * - le titre d'une tâche devient unique par projet (et non plus dans toute la base).
 */
final class Version20261006200000 extends AbstractMigration
{
    private const string PROJECT_SLUG = 'plateforme-numerique';

    public function getDescription(): string
    {
        return 'Projets : tables project et project_section, rattachement des tâches au projet Plateforme Numérique';
    }

    public function up(Schema $schema): void
    {
        // 1. Tables des projets et de leurs sections
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, summary VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_2FB3D0EE5E237E06 (name), UNIQUE INDEX UNIQ_2FB3D0EE989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE project_section (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, position INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, project_id INT NOT NULL, INDEX IDX_BB6D768A166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE project_section ADD CONSTRAINT FK_BB6D768A166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');

        // 2. Le projet Plateforme Numérique et sa présentation
        $this->addSql(
            'INSERT INTO project (name, slug, summary, created_at) VALUES (?, ?, ?, NOW())',
            ['Plateforme Numérique', self::PROJECT_SLUG, 'Une plateforme développée avec Symfony 8, PHP 8.5+, MySQL et Docker, construite progressivement pour devenir le cœur de la future entreprise.'],
        );

        foreach ($this->getPresentationSections() as $position => $section) {
            $this->addSql(
                'INSERT INTO project_section (project_id, title, content, position, created_at) SELECT id, ?, ?, ?, NOW() FROM project WHERE slug = ?',
                [$section['title'], $section['content'], $position, self::PROJECT_SLUG],
            );
        }

        // 3. Rattachement des tâches existantes au projet
        $this->addSql('ALTER TABLE project_step ADD project_id INT DEFAULT NULL');
        $this->addSql('UPDATE project_step SET project_id = (SELECT id FROM project WHERE slug = ?)', [self::PROJECT_SLUG]);
        $this->addSql('ALTER TABLE project_step MODIFY project_id INT NOT NULL');
        $this->addSql('ALTER TABLE project_step ADD CONSTRAINT FK_7A283624166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_7A283624166D1F9C ON project_step (project_id)');

        // 4. Titre unique par projet (remplace l'ancien index unique sur le titre seul)
        $this->addSql('DROP INDEX UNIQ_7A2836242B36786B ON project_step');
        $this->addSql('CREATE UNIQUE INDEX uniq_project_step_title ON project_step (project_id, title)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_project_step_title ON project_step');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7A2836242B36786B ON project_step (title)');
        $this->addSql('ALTER TABLE project_step DROP FOREIGN KEY FK_7A283624166D1F9C');
        $this->addSql('DROP INDEX IDX_7A283624166D1F9C ON project_step');
        $this->addSql('ALTER TABLE project_step DROP project_id');
        $this->addSql('ALTER TABLE project_section DROP FOREIGN KEY FK_BB6D768A166D1F9C');
        $this->addSql('DROP TABLE project_section');
        $this->addSql('DROP TABLE project');
    }

    /**
     * Contenu de l'ancienne page Projet (templates/app/project/index.html.twig), découpé en sections.
     *
     * @return list<array{title: string, content: string}>
     */
    private function getPresentationSections(): array
    {
        return [
            [
                'title' => 'Vision du projet',
                'content' => <<<'SECTION'
                    <p class="project__text">La plateforme servira à la fois de :</p>
                    <ul class="project__list">
                        <li class="project__list-item">site de présentation de la future entreprise ;</li>
                        <li class="project__list-item">portfolio professionnel ;</li>
                        <li class="project__list-item">laboratoire technique ;</li>
                        <li class="project__list-item">support d'apprentissage ;</li>
                        <li class="project__list-item">plateforme interne ;</li>
                        <li class="project__list-item">espace client à terme ;</li>
                        <li class="project__list-item">centre de formation à terme ;</li>
                        <li class="project__list-item">base pour de futurs outils métier et services SaaS.</li>
                    </ul>
                    <p class="project__text">
                        Elle doit permettre de développer progressivement les compétences techniques
                        nécessaires à la création et à la gestion d'une future entreprise informatique.
                    </p>
                    SECTION,
            ],
            [
                'title' => 'Philosophie',
                'content' => <<<'SECTION'
                    <p class="project__text">
                        Le projet est volontairement construit progressivement. Chaque nouvelle compétence
                        acquise est, lorsque c'est pertinent, intégrée au projet pour être réellement mise
                        en pratique.
                    </p>
                    <dl class="project__definitions">
                        <div class="project__definition">
                            <dt class="project__term">Docker</dt>
                            <dd class="project__description">environnement de développement et déploiement</dd>
                        </div>
                        <div class="project__definition">
                            <dt class="project__term">Git</dt>
                            <dd class="project__description">gestion professionnelle du code</dd>
                        </div>
                        <div class="project__definition">
                            <dt class="project__term">PHPUnit</dt>
                            <dd class="project__description">tests</dd>
                        </div>
                        <div class="project__definition">
                            <dt class="project__term">GitHub Actions</dt>
                            <dd class="project__description">CI/CD</dd>
                        </div>
                        <div class="project__definition">
                            <dt class="project__term">Python</dt>
                            <dd class="project__description">futurs services spécialisés</dd>
                        </div>
                        <div class="project__definition">
                            <dt class="project__term">FastAPI</dt>
                            <dd class="project__description">APIs Python</dd>
                        </div>
                        <div class="project__definition">
                            <dt class="project__term">IA / LLM</dt>
                            <dd class="project__description">fonctionnalités intelligentes</dd>
                        </div>
                        <div class="project__definition">
                            <dt class="project__term">RAG</dt>
                            <dd class="project__description">exploitation de connaissances</dd>
                        </div>
                        <div class="project__definition">
                            <dt class="project__term">MCP</dt>
                            <dd class="project__description">intégration avec des outils et services</dd>
                        </div>
                        <div class="project__definition">
                            <dt class="project__term">Cybersécurité</dt>
                            <dd class="project__description">sécurisation de la plateforme</dd>
                        </div>
                        <div class="project__definition">
                            <dt class="project__term">SEO</dt>
                            <dd class="project__description">optimisation du site public</dd>
                        </div>
                    </dl>
                    <p class="project__highlight">
                        Le projet est à la fois <strong>un produit, un laboratoire et un support d'apprentissage</strong>.
                    </p>
                    SECTION,
            ],
            [
                'title' => 'Objectifs techniques',
                'content' => <<<'SECTION'
                    <div class="project__groups">
                        <section class="project__group">
                            <h3 class="project__subheading">Backend</h3>
                            <ul class="project__tags">
                                <li class="project__tag">PHP 8.5+</li>
                                <li class="project__tag">Symfony 8</li>
                                <li class="project__tag">Doctrine</li>
                                <li class="project__tag">MySQL</li>
                                <li class="project__tag">API REST</li>
                                <li class="project__tag">Architecture modulaire</li>
                                <li class="project__tag">Services Symfony</li>
                                <li class="project__tag">Événements</li>
                                <li class="project__tag">Sécurité Symfony</li>
                                <li class="project__tag">Authentification et autorisation</li>
                            </ul>
                        </section>
                        <section class="project__group">
                            <h3 class="project__subheading">Frontend</h3>
                            <ul class="project__tags">
                                <li class="project__tag">Twig</li>
                                <li class="project__tag">HTML5</li>
                                <li class="project__tag">CSS3 classique</li>
                                <li class="project__tag">JavaScript</li>
                                <li class="project__tag">Symfony UX / Stimulus</li>
                                <li class="project__tag">Responsive design</li>
                                <li class="project__tag">Accessibilité</li>
                            </ul>
                        </section>
                        <section class="project__group">
                            <h3 class="project__subheading">Infrastructure</h3>
                            <ul class="project__tags">
                                <li class="project__tag">Docker</li>
                                <li class="project__tag">Docker Compose</li>
                                <li class="project__tag">Nginx</li>
                                <li class="project__tag">PHP-FPM</li>
                                <li class="project__tag">Linux</li>
                                <li class="project__tag">Git</li>
                                <li class="project__tag">GitHub</li>
                                <li class="project__tag">CI/CD</li>
                            </ul>
                        </section>
                        <section class="project__group">
                            <h3 class="project__subheading">Qualité</h3>
                            <ul class="project__tags">
                                <li class="project__tag">PHPUnit</li>
                                <li class="project__tag">Tests unitaires</li>
                                <li class="project__tag">Tests fonctionnels</li>
                                <li class="project__tag">Tests d'intégration</li>
                                <li class="project__tag">Analyse statique</li>
                                <li class="project__tag">Qualité du code</li>
                                <li class="project__tag">Documentation</li>
                            </ul>
                        </section>
                        <section class="project__group">
                            <h3 class="project__subheading">IA et technologies futures</h3>
                            <ul class="project__tags">
                                <li class="project__tag">Python</li>
                                <li class="project__tag">FastAPI</li>
                                <li class="project__tag">LLM</li>
                                <li class="project__tag">RAG</li>
                                <li class="project__tag">Agents</li>
                                <li class="project__tag">MCP</li>
                                <li class="project__tag">APIs d'IA</li>
                            </ul>
                        </section>
                    </div>
                    SECTION,
            ],
            [
                'title' => 'Architecture des contrôleurs',
                'content' => <<<'SECTION'
                    <p class="project__text">
                        Les contrôleurs sont séparés selon la partie de la plateforme qu'ils servent.
                    </p>
                    <table class="project__table">
                        <caption class="project__table-caption">Organisation des contrôleurs Symfony</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="project__table-head">Partie</th>
                                <th scope="col" class="project__table-head">Dossier</th>
                                <th scope="col" class="project__table-head">Préfixe de route</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row" class="project__table-cell project__table-cell--label">Site public</th>
                                <td class="project__table-cell"><code class="project__code">src/Controller/</code></td>
                                <td class="project__table-cell"><code class="project__code">/</code></td>
                            </tr>
                            <tr>
                                <th scope="row" class="project__table-cell project__table-cell--label">Application interne</th>
                                <td class="project__table-cell"><code class="project__code">src/Controller/App/</code></td>
                                <td class="project__table-cell"><code class="project__code">/app</code></td>
                            </tr>
                            <tr>
                                <th scope="row" class="project__table-cell project__table-cell--label">API</th>
                                <td class="project__table-cell"><code class="project__code">src/Controller/Api/</code></td>
                                <td class="project__table-cell"><code class="project__code">/api</code></td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="project__highlight">
                        Ne jamais modifier ou créer une architecture importante sans expliquer le raisonnement,
                        les avantages, les inconvénients et l'impact sur le projet, puis obtenir la validation
                        du développeur.
                    </p>
                    SECTION,
            ],
            [
                'title' => 'Méthode de développement',
                'content' => <<<'SECTION'
                    <p class="project__text">Chaque fonctionnalité passe progressivement par les étapes suivantes :</p>
                    <ol class="project__steps">
                        <li class="project__step">Définir le besoin</li>
                        <li class="project__step">Réfléchir à l'architecture</li>
                        <li class="project__step">Développer le backend</li>
                        <li class="project__step">Gérer la base de données si nécessaire</li>
                        <li class="project__step">Développer le HTML/Twig</li>
                        <li class="project__step">Ajouter le CSS</li>
                        <li class="project__step">Ajouter JavaScript uniquement si nécessaire</li>
                        <li class="project__step">Gérer la sécurité</li>
                        <li class="project__step">Gérer le SEO pour les pages publiques</li>
                        <li class="project__step">Écrire les tests pertinents</li>
                        <li class="project__step">Vérifier la qualité du code</li>
                        <li class="project__step">Documenter si nécessaire</li>
                        <li class="project__step">Effectuer une revue avant commit</li>
                    </ol>
                    SECTION,
            ],
            [
                'title' => "Utilisation de l'IA",
                'content' => <<<'SECTION'
                    <p class="project__text">
                        L'IA est utilisée comme <strong>assistant de développement</strong>, et non comme
                        développeur autonome. Elle peut expliquer une technologie, proposer une architecture
                        ou des tests, générer du code répétitif, analyser du code et aider à documenter.
                    </p>
                    <p class="project__highlight">
                        Le développeur reste responsable des décisions techniques et du code intégré au projet.
                        Tout code généré par une IA doit être compris, vérifié, adapté, testé et validé avant intégration.
                    </p>
                    SECTION,
            ],
            [
                'title' => 'Priorités',
                'content' => <<<'SECTION'
                    <ol class="project__steps">
                        <li class="project__step">Fonctionnalité</li>
                        <li class="project__step">Architecture et qualité du code</li>
                        <li class="project__step">Sécurité</li>
                        <li class="project__step">Tests</li>
                        <li class="project__step">SEO</li>
                        <li class="project__step">Accessibilité</li>
                        <li class="project__step">Performance</li>
                        <li class="project__step">Documentation</li>
                        <li class="project__step">Amélioration visuelle et animations</li>
                    </ol>
                    SECTION,
            ],
            [
                'title' => 'Stack V1',
                'content' => <<<'SECTION'
                    <dl class="project__definitions">
                        <div class="project__definition">
                            <dt class="project__term">Backend</dt>
                            <dd class="project__description">PHP 8.5+, Symfony 8, Doctrine</dd>
                        </div>
                        <div class="project__definition">
                            <dt class="project__term">Base de données</dt>
                            <dd class="project__description">MySQL</dd>
                        </div>
                        <div class="project__definition">
                            <dt class="project__term">Frontend</dt>
                            <dd class="project__description">Twig, HTML5, CSS3 classique, JavaScript</dd>
                        </div>
                        <div class="project__definition">
                            <dt class="project__term">Infrastructure</dt>
                            <dd class="project__description">Docker, Docker Compose, Nginx, PHP-FPM</dd>
                        </div>
                        <div class="project__definition">
                            <dt class="project__term">Outils</dt>
                            <dd class="project__description">Git, GitHub, PHPUnit</dd>
                        </div>
                    </dl>
                    SECTION,
            ],
            [
                'title' => 'Premiers modules',
                'content' => <<<'SECTION'
                    <div class="project__cards">
                        <article class="project__card">
                            <h3 class="project__subheading">Projet</h3>
                            <p class="project__text">
                                Présentation du projet : vision, objectifs, architecture, technologies,
                                roadmap, organisation et principes de développement.
                            </p>
                        </article>
                        <article class="project__card">
                            <h3 class="project__subheading">Learning</h3>
                            <p class="project__text">
                                Suivi des technologies à apprendre ou à revoir, des notions étudiées,
                                de la progression et des expérimentations.
                            </p>
                        </article>
                        <article class="project__card">
                            <h3 class="project__subheading">Web Languages</h3>
                            <p class="project__text">
                                Base de connaissances sur les langages et technologies du Web :
                                HTML, CSS, JavaScript, PHP, SQL, HTTP…
                            </p>
                        </article>
                    </div>
                    SECTION,
            ],
            [
                'title' => 'Évolution prévue',
                'content' => <<<'SECTION'
                    <p class="project__text">
                        Ces fonctionnalités seront ajoutées progressivement, en fonction des besoins réels :
                    </p>
                    <ul class="project__tags">
                        <li class="project__tag">Authentification</li>
                        <li class="project__tag">Gestion des utilisateurs</li>
                        <li class="project__tag">Espace personnel</li>
                        <li class="project__tag">Gestion de projets</li>
                        <li class="project__tag">CRM</li>
                        <li class="project__tag">Espace client</li>
                        <li class="project__tag">Centre de formation</li>
                        <li class="project__tag">Outils métier</li>
                        <li class="project__tag">APIs</li>
                        <li class="project__tag">Services Python</li>
                        <li class="project__tag">Fonctionnalités IA</li>
                        <li class="project__tag">Outils de cybersécurité</li>
                        <li class="project__tag">Automatisations</li>
                        <li class="project__tag">Monitoring</li>
                        <li class="project__tag">Administration</li>
                        <li class="project__tag">Produits SaaS</li>
                    </ul>
                    SECTION,
            ],
            [
                'title' => 'Règle générale',
                'content' => <<<'SECTION'
                    <p class="project__highlight">
                        Le projet doit rester <strong>simple au départ, solide techniquement, documenté
                        et capable d'évoluer progressivement</strong>.
                    </p>
                    SECTION,
            ],
        ];
    }
}
