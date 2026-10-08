<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Le contenu des tables est en français : les statuts et les priorités des tâches
 * et des sous-tâches passent des valeurs anglaises aux valeurs françaises.
 *
 *   statut   : todo → pas_commencee, in_progress → en_cours, done → faite
 *   priorité : low → basse, normal → normale, high → haute
 *
 * Seules les données changent : la structure des tables reste la même.
 */
final class Version20261008150000 extends AbstractMigration
{
    private const array TABLES = ['project_step', 'project_sub_step'];

    private const array STATUSES = [
        'todo' => 'pas_commencee',
        'in_progress' => 'en_cours',
        'done' => 'faite',
    ];

    private const array PRIORITIES = [
        'low' => 'basse',
        'normal' => 'normale',
        'high' => 'haute',
    ];

    public function getDescription(): string
    {
        return 'Statuts et priorités des tâches et sous-tâches en français';
    }

    public function up(Schema $schema): void
    {
        $this->convert(self::STATUSES, self::PRIORITIES);
    }

    public function down(Schema $schema): void
    {
        $this->convert(array_flip(self::STATUSES), array_flip(self::PRIORITIES));
    }

    /**
     * @param array<string, string> $statuses   ancienne valeur => nouvelle valeur
     * @param array<string, string> $priorities ancienne valeur => nouvelle valeur
     */
    private function convert(array $statuses, array $priorities): void
    {
        foreach (self::TABLES as $table) {
            foreach ($statuses as $from => $to) {
                $this->addSql(\sprintf('UPDATE %s SET status = ? WHERE status = ?', $table), [$to, $from]);
            }

            foreach ($priorities as $from => $to) {
                $this->addSql(\sprintf('UPDATE %s SET priority = ? WHERE priority = ?', $table), [$to, $from]);
            }
        }
    }
}
