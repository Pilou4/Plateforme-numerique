<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tableaux des sections de projet : un style par tableau et le tri par colonne.
 *
 * 1. project_section_block.table_style : style du tableau (App\Enum\ProjectSectionTableStyle) ;
 *    les tableaux déjà enregistrés prennent le style par défaut « Liste Finder » (liste_finder).
 * 2. project_section_table_column.sortable : on peut trier sur la colonne ; les colonnes existantes le sont.
 */
final class Version20261015190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tableaux des sections : style du tableau (table_style) et colonnes triables (sortable)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_section_block ADD table_style VARCHAR(20) DEFAULT NULL');
        $this->addSql("UPDATE project_section_block SET table_style = 'liste_finder' WHERE type = 'tableau'");
        $this->addSql('ALTER TABLE project_section_table_column ADD sortable TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_section_table_column DROP sortable');
        $this->addSql('ALTER TABLE project_section_block DROP table_style');
    }
}
