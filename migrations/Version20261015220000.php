<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tags des sections de projet : une couleur par tag (App\Enum\ProjectSectionTagColor).
 * Les tags existants prennent « bleu », leur couleur d'avant.
 */
final class Version20261015220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tags des sections : couleur de chaque tag (color)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE project_section_tag ADD color VARCHAR(20) DEFAULT 'bleu' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_section_tag DROP color');
    }
}
