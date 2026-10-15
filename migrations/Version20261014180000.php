<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Projets : deux nouvelles images et couleur des statuts.
 *
 * 1. Table project : colonnes logo_wide (logo long) et avatar, facultatives.
 * 2. Table project_state : colonne color (couleur de la pastille, voir App\Enum\ProjectStateColor).
 *    Les trois statuts de départ gardent les couleurs qu'ils avaient jusqu'ici dans le CSS.
 */
final class Version20261014180000 extends AbstractMigration
{
    /**
     * code du statut => couleur
     */
    private const array STATE_COLORS = [
        'en_preparation' => 'violet',
        'en_cours' => 'bleu',
        'termine' => 'vert',
    ];

    public function getDescription(): string
    {
        return 'Projets : colonnes logo_wide et avatar, couleur des statuts (project_state.color)';
    }

    public function up(Schema $schema): void
    {
        // 1. Images du projet
        $this->addSql('ALTER TABLE project ADD logo_wide VARCHAR(255) DEFAULT NULL, ADD avatar VARCHAR(255) DEFAULT NULL');

        // 2. Couleur des statuts : « gris » pour remplir les lignes existantes, puis les couleurs de départ
        $this->addSql("ALTER TABLE project_state ADD color VARCHAR(20) NOT NULL DEFAULT 'gris'");

        foreach (self::STATE_COLORS as $code => $color) {
            $this->addSql('UPDATE project_state SET color = ? WHERE code = ?', [$color, $code]);
        }

        // La valeur par défaut n'a servi qu'à remplir les statuts existants (l'entité donne la couleur)
        $this->addSql('ALTER TABLE project_state ALTER color DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_state DROP color');
        $this->addSql('ALTER TABLE project DROP logo_wide, DROP avatar');
    }
}
