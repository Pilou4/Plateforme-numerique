<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sections de projet en blocs : plus aucun HTML enregistré.
 *
 * 1. project_section perd sa colonne content (le contenu est ressaisi en blocs).
 * 2. project_section_block : les blocs d'une section, rangés par position.
 * 3. Une table par type de contenu :
 *      project_section_paragraph   lignes d'un paragraphe (<p>)
 *      project_section_list_item   éléments d'une liste (<li>)
 *      project_section_table_column / _row / _cell   tableau (<th>, <tr>, <td>)
 *      project_section_tag         tags
 *      project_section_definition  paires terme / définition (<dt>, <dd>)
 *      project_section_block_file  fichiers d'un bloc images ou documents (lien vers project_file)
 *
 * Toutes les clés étrangères sont en ON DELETE CASCADE : supprimer un projet, une section ou un bloc
 * supprime tout ce qu'il contient ; supprimer un fichier le retire des blocs qui l'affichaient.
 */
final class Version20261015120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sections de projet en blocs : tables project_section_block, _paragraph, _list_item, _table_column, _table_row, _table_cell, _tag, _definition, _block_file';
    }

    public function up(Schema $schema): void
    {
        // 1. Le contenu HTML des sections disparaît
        $this->addSql('ALTER TABLE project_section DROP content');

        // 2. Blocs
        $this->addSql('CREATE TABLE project_section_block (id INT AUTO_INCREMENT NOT NULL, section_id INT NOT NULL, type VARCHAR(20) NOT NULL, position INT NOT NULL, subtitle VARCHAR(255) DEFAULT NULL, ordered TINYINT(1) DEFAULT NULL, INDEX IDX_C0DD6740D823E37A (section_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE project_section_block ADD CONSTRAINT FK_C0DD6740D823E37A FOREIGN KEY (section_id) REFERENCES project_section (id) ON DELETE CASCADE');

        // 3. Contenu des blocs
        $this->addSql('CREATE TABLE project_section_paragraph (id INT AUTO_INCREMENT NOT NULL, block_id INT NOT NULL, position INT NOT NULL, text LONGTEXT NOT NULL, INDEX IDX_2B4DB4D8E9ED820C (block_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE project_section_paragraph ADD CONSTRAINT FK_2B4DB4D8E9ED820C FOREIGN KEY (block_id) REFERENCES project_section_block (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE project_section_list_item (id INT AUTO_INCREMENT NOT NULL, block_id INT NOT NULL, position INT NOT NULL, text LONGTEXT NOT NULL, INDEX IDX_C4BD64DE9ED820C (block_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE project_section_list_item ADD CONSTRAINT FK_C4BD64DE9ED820C FOREIGN KEY (block_id) REFERENCES project_section_block (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE project_section_table_column (id INT AUTO_INCREMENT NOT NULL, block_id INT NOT NULL, position INT NOT NULL, label VARCHAR(255) NOT NULL, INDEX IDX_C5CE2CC0E9ED820C (block_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE project_section_table_column ADD CONSTRAINT FK_C5CE2CC0E9ED820C FOREIGN KEY (block_id) REFERENCES project_section_block (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE project_section_table_row (id INT AUTO_INCREMENT NOT NULL, block_id INT NOT NULL, position INT NOT NULL, INDEX IDX_DB4B5005E9ED820C (block_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE project_section_table_row ADD CONSTRAINT FK_DB4B5005E9ED820C FOREIGN KEY (block_id) REFERENCES project_section_block (id) ON DELETE CASCADE');

        // Une seule case par couple ligne + colonne (cet index sert aussi aux recherches par ligne)
        $this->addSql('CREATE TABLE project_section_table_cell (id INT AUTO_INCREMENT NOT NULL, row_id INT NOT NULL, column_id INT NOT NULL, text LONGTEXT NOT NULL, INDEX IDX_AAB30397BE8E8ED5 (column_id), UNIQUE INDEX project_section_table_cell_row_column (row_id, column_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE project_section_table_cell ADD CONSTRAINT FK_AAB3039783A269F2 FOREIGN KEY (row_id) REFERENCES project_section_table_row (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_section_table_cell ADD CONSTRAINT FK_AAB30397BE8E8ED5 FOREIGN KEY (column_id) REFERENCES project_section_table_column (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE project_section_tag (id INT AUTO_INCREMENT NOT NULL, block_id INT NOT NULL, position INT NOT NULL, label VARCHAR(100) NOT NULL, INDEX IDX_109A932AE9ED820C (block_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE project_section_tag ADD CONSTRAINT FK_109A932AE9ED820C FOREIGN KEY (block_id) REFERENCES project_section_block (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE project_section_definition (id INT AUTO_INCREMENT NOT NULL, block_id INT NOT NULL, position INT NOT NULL, term VARCHAR(255) NOT NULL, definition LONGTEXT NOT NULL, INDEX IDX_43D2EB66E9ED820C (block_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE project_section_definition ADD CONSTRAINT FK_43D2EB66E9ED820C FOREIGN KEY (block_id) REFERENCES project_section_block (id) ON DELETE CASCADE');

        // Un même fichier une seule fois par bloc (cet index sert aussi aux recherches par bloc)
        $this->addSql('CREATE TABLE project_section_block_file (id INT AUTO_INCREMENT NOT NULL, block_id INT NOT NULL, file_id INT NOT NULL, position INT NOT NULL, caption VARCHAR(255) DEFAULT NULL, INDEX IDX_6113A0D593CB796C (file_id), UNIQUE INDEX project_section_block_file_block_file (block_id, file_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE project_section_block_file ADD CONSTRAINT FK_6113A0D5E9ED820C FOREIGN KEY (block_id) REFERENCES project_section_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_section_block_file ADD CONSTRAINT FK_6113A0D593CB796C FOREIGN KEY (file_id) REFERENCES project_file (id) ON DELETE CASCADE');
    }

    /**
     * Retour en arrière : les tables des blocs sont supprimées et la colonne content revient vide
     * (le contenu saisi en blocs est perdu).
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE project_section_block_file');
        $this->addSql('DROP TABLE project_section_definition');
        $this->addSql('DROP TABLE project_section_tag');
        $this->addSql('DROP TABLE project_section_table_cell');
        $this->addSql('DROP TABLE project_section_table_row');
        $this->addSql('DROP TABLE project_section_table_column');
        $this->addSql('DROP TABLE project_section_list_item');
        $this->addSql('DROP TABLE project_section_paragraph');
        $this->addSql('DROP TABLE project_section_block');
        // MySQL refuse une valeur par défaut sur un LONGTEXT : colonne ajoutée vide, remplie, puis rendue obligatoire
        $this->addSql('ALTER TABLE project_section ADD content LONGTEXT DEFAULT NULL');
        $this->addSql("UPDATE project_section SET content = ''");
        $this->addSql('ALTER TABLE project_section MODIFY content LONGTEXT NOT NULL');
    }
}
