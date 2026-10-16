<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Projets : liens facultatifs vers le dépôt de code (repository_url) et vers le site (website_url).
 */
final class Version20261015230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Projets : adresse du dépôt (repository_url) et du site (website_url)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD repository_url VARCHAR(255) DEFAULT NULL, ADD website_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP repository_url, DROP website_url');
    }
}
