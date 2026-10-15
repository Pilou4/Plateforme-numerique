<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Projets : état d'avancement, catégorie et favori.
 *
 * 1. Table project_state et ses 3 états (en_preparation, en_cours, termine).
 * 2. Table project_category et sa liste de départ.
 * 3. Table project : colonnes state_id (obligatoire), category_id (facultative) et favorite.
 *    Les projets existants passent « En cours ».
 */
final class Version20261009120000 extends AbstractMigration
{
    /**
     * code => [libellé, libellé au pluriel (filtres), position]
     */
    private const array STATES = [
        'en_preparation' => ['En préparation', 'En préparation', 0],
        'en_cours' => ['En cours', 'En cours', 1],
        'termine' => ['Terminé', 'Terminés', 2],
    ];

    private const array CATEGORIES = ['Application web', 'Site vitrine', 'Jeu', 'Outil interne', 'Formation'];

    public function getDescription(): string
    {
        return 'Projets : tables project_state et project_category, colonnes state_id, category_id et favorite';
    }

    public function up(Schema $schema): void
    {
        // 1. États
        $this->addSql('CREATE TABLE project_state (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(30) NOT NULL, label VARCHAR(50) NOT NULL, plural_label VARCHAR(50) NOT NULL, position INT NOT NULL, UNIQUE INDEX UNIQ_B0C6DB6577153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        foreach (self::STATES as $code => [$label, $pluralLabel, $position]) {
            $this->addSql(
                'INSERT INTO project_state (code, label, plural_label, position) VALUES (?, ?, ?, ?)',
                [$code, $label, $pluralLabel, $position],
            );
        }

        // 2. Catégories
        $this->addSql('CREATE TABLE project_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, position INT NOT NULL, UNIQUE INDEX UNIQ_3B02921A5E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        foreach (self::CATEGORIES as $position => $name) {
            $this->addSql('INSERT INTO project_category (name, position) VALUES (?, ?)', [$name, $position]);
        }

        // 3. Projets
        $this->addSql('ALTER TABLE project ADD state_id INT DEFAULT NULL, ADD category_id INT DEFAULT NULL, ADD favorite TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('UPDATE project SET state_id = (SELECT id FROM project_state WHERE code = ?)', ['en_cours']);
        $this->addSql('ALTER TABLE project MODIFY state_id INT NOT NULL');
        // La valeur par défaut n'a servi qu'à remplir les projets existants (l'entité met false elle-même)
        $this->addSql('ALTER TABLE project ALTER favorite DROP DEFAULT');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE5D83CC1 FOREIGN KEY (state_id) REFERENCES project_state (id)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE12469DE2 FOREIGN KEY (category_id) REFERENCES project_category (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE5D83CC1 ON project (state_id)');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE12469DE2 ON project (category_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE5D83CC1');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE12469DE2');
        $this->addSql('DROP INDEX IDX_2FB3D0EE5D83CC1 ON project');
        $this->addSql('DROP INDEX IDX_2FB3D0EE12469DE2 ON project');
        $this->addSql('ALTER TABLE project DROP state_id, DROP category_id, DROP favorite');
        $this->addSql('DROP TABLE project_category');
        $this->addSql('DROP TABLE project_state');
    }
}
