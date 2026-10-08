<?php

namespace App\Entity;

use App\Repository\ProjectStatusRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Statut d'une tâche ou d'une sous-tâche (table project_status) : pas commencée, en cours, terminée.
 *
 * Les tâches (project_step) et les sous-tâches (project_sub_step) sont reliées à cette table.
 * Les trois statuts sont créés par la migration Version20261008170000.
 *
 * Le code est utilisé par le programme (règles de calcul, API, JavaScript) : il ne doit pas changer.
 * Le libellé est celui affiché sur les pages : il peut être modifié en base.
 */
#[ORM\Entity(repositoryClass: ProjectStatusRepository::class)]
class ProjectStatus
{
    public const string CODE_TODO = 'pas_commencer';
    public const string CODE_IN_PROGRESS = 'en_cours';
    public const string CODE_DONE = 'terminer';

    /**
     * Tous les codes, dans l'ordre d'avancement.
     */
    public const array CODES = [self::CODE_TODO, self::CODE_IN_PROGRESS, self::CODE_DONE];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    private string $code;

    /**
     * Libellé affiché : « Pas commencée », « En cours », « Terminée ».
     */
    #[ORM\Column(length: 50)]
    private string $label;

    /**
     * Libellé au pluriel, utilisé pour les filtres de la page des tâches.
     */
    #[ORM\Column(length: 50)]
    private string $pluralLabel;

    /**
     * Ordre d'affichage et de tri (0 = pas commencée, 1 = en cours, 2 = terminée).
     */
    #[ORM\Column]
    private int $position;

    public function __construct(string $code, string $label, string $pluralLabel, int $position)
    {
        $this->code = $code;
        $this->label = $label;
        $this->pluralLabel = $pluralLabel;
        $this->position = $position;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getPluralLabel(): string
    {
        return $this->pluralLabel;
    }

    public function setPluralLabel(string $pluralLabel): static
    {
        $this->pluralLabel = $pluralLabel;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function isTodo(): bool
    {
        return self::CODE_TODO === $this->code;
    }

    public function isDone(): bool
    {
        return self::CODE_DONE === $this->code;
    }
}
