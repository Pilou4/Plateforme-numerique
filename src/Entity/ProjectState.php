<?php

namespace App\Entity;

use App\Enum\ProjectStateColor;
use App\Repository\ProjectStateRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Statut (état d'avancement) d'un projet (table project_state) : pas commencé, en cours, terminé…
 * À ne pas confondre avec ProjectStatus, le statut d'une tâche.
 *
 * Les statuts se gèrent depuis la page Paramètres de l'app (ProjectStateManager) :
 * - le code est calculé à partir du nom à la création, puis ne change plus (il sert aux filtres) ;
 * - le nom, le nom au pluriel, la couleur et l'ordre peuvent être modifiés.
 * Les trois statuts de départ sont créés par la migration Version20261009120000.
 */
#[ORM\Entity(repositoryClass: ProjectStateRepository::class)]
class ProjectState implements PositionedInterface
{
    public const string CODE_NOT_STARTED = 'pas_commence';
    public const string CODE_IN_PROGRESS = 'en_cours';
    public const string CODE_DONE = 'termine';

    /**
     * Les codes des trois statuts de départ, dans l'ordre d'avancement.
     */
    public const array CODES = [self::CODE_NOT_STARTED, self::CODE_IN_PROGRESS, self::CODE_DONE];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    private string $code;

    /**
     * Libellé affiché sur la carte d'un projet : « Pas commencé », « En cours », « Terminé ».
     */
    #[ORM\Column(length: 50)]
    private string $label;

    /**
     * Libellé des filtres de la liste des projets : « Pas commencés », « En cours », « Terminés ».
     */
    #[ORM\Column(length: 50)]
    private string $pluralLabel;

    #[ORM\Column]
    private int $position;

    /**
     * Couleur de la pastille du statut sur la carte d'un projet.
     */
    #[ORM\Column(length: 20, enumType: ProjectStateColor::class)]
    private ProjectStateColor $color;

    public function __construct(string $code, string $label, string $pluralLabel, int $position, ProjectStateColor $color = ProjectStateColor::Grey)
    {
        $this->code = $code;
        $this->label = $label;
        $this->pluralLabel = $pluralLabel;
        $this->position = $position;
        $this->color = $color;
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

    public function getPluralLabel(): string
    {
        return $this->pluralLabel;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getColor(): ProjectStateColor
    {
        return $this->color;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function setPluralLabel(string $pluralLabel): static
    {
        $this->pluralLabel = $pluralLabel;

        return $this;
    }

    public function setColor(ProjectStateColor $color): static
    {
        $this->color = $color;

        return $this;
    }
}
