<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Statut de départ des projets : « En préparation » devient « Pas commencé ».
 *
 * 1. Un statut « Pas commencé » ajouté à la main depuis la page Paramètres (code pas_commence
 *    ou pas_commencer) fait doublon : ses projets passent sur le statut de départ, puis il est supprimé.
 * 2. Le statut de départ (code en_preparation) est renommé : code pas_commence, « Pas commencé » / « Pas commencés ».
 *    Il reste le premier de la liste : un nouveau projet le prend automatiquement.
 */
final class Version20261015140000 extends AbstractMigration
{
    private const string OLD_CODE = 'en_preparation';
    private const string NEW_CODE = 'pas_commence';

    /**
     * Codes possibles du statut ajouté à la main (« Pas commencé » ou « Pas commencer »)
     */
    private const array DUPLICATE_CODES = ['pas_commence', 'pas_commencer'];

    public function getDescription(): string
    {
        return 'Statut de départ des projets : « En préparation » devient « Pas commencé » (code pas_commence)';
    }

    public function up(Schema $schema): void
    {
        $duplicates = "SELECT id FROM project_state WHERE code IN ('".implode("', '", self::DUPLICATE_CODES)."')";

        // 1. Doublon ajouté à la main : ses projets passent sur le statut de départ, puis il disparaît
        $this->addSql(
            "UPDATE project SET state_id = (SELECT id FROM project_state WHERE code = ?) WHERE state_id IN ($duplicates) AND EXISTS (SELECT 1 FROM project_state WHERE code = ?)",
            [self::OLD_CODE, self::OLD_CODE],
        );
        // (sans statut de départ, rien n'est supprimé : les projets du doublon n'auraient plus de statut)
        $this->addSql(
            "DELETE FROM project_state WHERE id IN (SELECT id FROM ($duplicates) AS duplicate) AND EXISTS (SELECT 1 FROM (SELECT id FROM project_state WHERE code = ?) AS start_state)",
            [self::OLD_CODE],
        );

        // 2. Le statut de départ est renommé
        $this->addSql(
            'UPDATE project_state SET code = ?, label = ?, plural_label = ? WHERE code = ?',
            [self::NEW_CODE, 'Pas commencé', 'Pas commencés', self::OLD_CODE],
        );
    }

    /**
     * Retour en arrière : le statut reprend son ancien nom (le doublon supprimé ne revient pas).
     */
    public function down(Schema $schema): void
    {
        $this->addSql(
            'UPDATE project_state SET code = ?, label = ?, plural_label = ? WHERE code = ?',
            [self::OLD_CODE, 'En préparation', 'En préparation', self::NEW_CODE],
        );
    }
}
