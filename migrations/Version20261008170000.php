<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Les statuts des tâches et sous-tâches deviennent une table : project_status.
 *
 * 1. Création de la table et de ses 3 statuts (pas_commencer, en_cours, terminer).
 * 2. project_step et project_sub_step reçoivent une colonne status_id (clé étrangère vers project_status),
 *    remplie à partir de l'ancienne colonne status (texte), qui est ensuite supprimée.
 *
 * Les anciennes valeurs reconnues : françaises (pas_commencee, en_cours, faite)
 * et anglaises (todo, in_progress, done), au cas où la migration précédente n'aurait pas tourné.
 */
final class Version20261008170000 extends AbstractMigration
{
    /**
     * code => [libellé, libellé au pluriel, position]
     */
    private const array STATUSES = [
        'pas_commencer' => ['Pas commencée', 'Pas commencées', 0],
        'en_cours' => ['En cours', 'En cours', 1],
        'terminer' => ['Terminée', 'Terminées', 2],
    ];

    /**
     * Ancienne valeur de la colonne status => nouveau code.
     */
    private const array OLD_VALUES = [
        'pas_commencee' => 'pas_commencer',
        'en_cours' => 'en_cours',
        'faite' => 'terminer',
        'todo' => 'pas_commencer',
        'in_progress' => 'en_cours',
        'done' => 'terminer',
    ];

    /**
     * Valeurs remises par down() (celles de la migration Version20261008150000).
     */
    private const array DOWN_VALUES = [
        'pas_commencer' => 'pas_commencee',
        'en_cours' => 'en_cours',
        'terminer' => 'faite',
    ];

    /**
     * Table => [nom de la clé étrangère, nom de l'index] (noms calculés comme le fait Doctrine).
     */
    private const array TABLES = [
        'project_step' => ['FK_7A2836246BF700BD', 'IDX_7A2836246BF700BD'],
        'project_sub_step' => ['FK_5961EA266BF700BD', 'IDX_5961EA266BF700BD'],
    ];

    public function getDescription(): string
    {
        return 'Table project_status, reliée aux tâches et aux sous-tâches';
    }

    public function up(Schema $schema): void
    {
        // 1. La table des statuts et ses 3 lignes
        $this->addSql('CREATE TABLE project_status (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(30) NOT NULL, label VARCHAR(50) NOT NULL, plural_label VARCHAR(50) NOT NULL, position INT NOT NULL, UNIQUE INDEX UNIQ_6CA48E5677153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        foreach (self::STATUSES as $code => [$label, $pluralLabel, $position]) {
            $this->addSql(
                'INSERT INTO project_status (code, label, plural_label, position) VALUES (?, ?, ?, ?)',
                [$code, $label, $pluralLabel, $position],
            );
        }

        // 2. Tâches et sous-tâches : status (texte) → status_id (relation)
        foreach (self::TABLES as $table => [$foreignKey, $index]) {
            $this->addSql(\sprintf('ALTER TABLE %s ADD status_id INT DEFAULT NULL', $table));

            foreach (self::OLD_VALUES as $oldValue => $code) {
                $this->addSql(
                    \sprintf('UPDATE %s SET status_id = (SELECT id FROM project_status WHERE code = ?) WHERE status = ?', $table),
                    [$code, $oldValue],
                );
            }

            // Valeur inconnue (ne devrait pas arriver) : pas commencée
            $this->addSql(
                \sprintf('UPDATE %s SET status_id = (SELECT id FROM project_status WHERE code = ?) WHERE status_id IS NULL', $table),
                ['pas_commencer'],
            );

            $this->addSql(\sprintf('ALTER TABLE %s MODIFY status_id INT NOT NULL', $table));
            $this->addSql(\sprintf('ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (status_id) REFERENCES project_status (id)', $table, $foreignKey));
            $this->addSql(\sprintf('CREATE INDEX %s ON %s (status_id)', $index, $table));
            $this->addSql(\sprintf('ALTER TABLE %s DROP status', $table));
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::TABLES as $table => [$foreignKey, $index]) {
            $this->addSql(\sprintf('ALTER TABLE %s ADD status VARCHAR(20) DEFAULT NULL', $table));

            foreach (self::DOWN_VALUES as $code => $oldValue) {
                $this->addSql(
                    \sprintf('UPDATE %s SET status = ? WHERE status_id = (SELECT id FROM project_status WHERE code = ?)', $table),
                    [$oldValue, $code],
                );
            }

            $this->addSql(\sprintf('ALTER TABLE %s MODIFY status VARCHAR(20) NOT NULL', $table));
            $this->addSql(\sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $table, $foreignKey));
            $this->addSql(\sprintf('DROP INDEX %s ON %s', $index, $table));
            $this->addSql(\sprintf('ALTER TABLE %s DROP status_id', $table));
        }

        $this->addSql('DROP TABLE project_status');
    }
}
