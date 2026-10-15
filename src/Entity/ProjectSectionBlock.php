<?php

namespace App\Entity;

use App\Enum\ProjectSectionBlockType;
use App\Enum\ProjectSectionTableStyle;
use App\Enum\ProjectSectionTagColor;
use App\Repository\ProjectSectionBlockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un bloc d'une section de projet (table project_section_block) : paragraphe, sous-titre, liste,
 * tableau, tags, définitions, images ou documents. Les blocs sont affichés dans l'ordre de leur position.
 *
 * Le contenu est rangé dans la table qui correspond au type :
 *   paragraphe  → project_section_paragraph        (une ligne = un <p>)
 *   sous_titre  → colonne subtitle                 (un <h3>)
 *   liste       → project_section_list_item        (colonne ordered : <ol> ou <ul>)
 *   tableau     → project_section_table_column / _row / _cell (colonne table_style : son style)
 *   tags        → project_section_tag
 *   definitions → project_section_definition       (<dt> / <dd>)
 *   images      → project_section_block_file
 *   documents   → project_section_block_file
 */
#[ORM\Entity(repositoryClass: ProjectSectionBlockRepository::class)]
class ProjectSectionBlock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'blocks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ProjectSection $section = null;

    #[ORM\Column(length: 20, enumType: ProjectSectionBlockType::class)]
    private ProjectSectionBlockType $type;

    /**
     * Ordre du bloc dans la section : 0, 1, 2…
     */
    #[ORM\Column]
    private int $position = 0;

    /**
     * Texte du sous-titre (type sous_titre uniquement).
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subtitle = null;

    /**
     * Liste numérotée (<ol>) ou à puces (<ul>) (type liste uniquement).
     */
    #[ORM\Column(nullable: true)]
    private ?bool $ordered = null;

    /**
     * Style du tableau (type tableau uniquement) ; vide : le style par défaut.
     */
    #[ORM\Column(length: 20, nullable: true, enumType: ProjectSectionTableStyle::class)]
    private ?ProjectSectionTableStyle $tableStyle = null;

    /**
     * @var Collection<int, ProjectSectionParagraph>
     */
    #[ORM\OneToMany(targetEntity: ProjectSectionParagraph::class, mappedBy: 'block', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $paragraphs;

    /**
     * @var Collection<int, ProjectSectionListItem>
     */
    #[ORM\OneToMany(targetEntity: ProjectSectionListItem::class, mappedBy: 'block', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $listItems;

    /**
     * @var Collection<int, ProjectSectionTableColumn>
     */
    #[ORM\OneToMany(targetEntity: ProjectSectionTableColumn::class, mappedBy: 'block', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $tableColumns;

    /**
     * @var Collection<int, ProjectSectionTableRow>
     */
    #[ORM\OneToMany(targetEntity: ProjectSectionTableRow::class, mappedBy: 'block', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $tableRows;

    /**
     * @var Collection<int, ProjectSectionTag>
     */
    #[ORM\OneToMany(targetEntity: ProjectSectionTag::class, mappedBy: 'block', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $tags;

    /**
     * @var Collection<int, ProjectSectionDefinition>
     */
    #[ORM\OneToMany(targetEntity: ProjectSectionDefinition::class, mappedBy: 'block', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $definitions;

    /**
     * @var Collection<int, ProjectSectionBlockFile>
     */
    #[ORM\OneToMany(targetEntity: ProjectSectionBlockFile::class, mappedBy: 'block', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $files;

    public function __construct(ProjectSectionBlockType $type)
    {
        $this->type = $type;
        $this->paragraphs = new ArrayCollection();
        $this->listItems = new ArrayCollection();
        $this->tableColumns = new ArrayCollection();
        $this->tableRows = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->definitions = new ArrayCollection();
        $this->files = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSection(): ?ProjectSection
    {
        return $this->section;
    }

    public function setSection(?ProjectSection $section): static
    {
        $this->section = $section;

        return $this;
    }

    public function getType(): ProjectSectionBlockType
    {
        return $this->type;
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

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): static
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function isOrdered(): bool
    {
        return true === $this->ordered;
    }

    public function setOrdered(?bool $ordered): static
    {
        $this->ordered = $ordered;

        return $this;
    }

    public function getTableStyle(): ProjectSectionTableStyle
    {
        return $this->tableStyle ?? ProjectSectionTableStyle::default();
    }

    public function setTableStyle(?ProjectSectionTableStyle $tableStyle): static
    {
        $this->tableStyle = $tableStyle;

        return $this;
    }

    /**
     * @return Collection<int, ProjectSectionParagraph>
     */
    public function getParagraphs(): Collection
    {
        return $this->paragraphs;
    }

    public function addParagraph(string $text): static
    {
        $this->paragraphs->add(new ProjectSectionParagraph($this, $this->paragraphs->count(), $text));

        return $this;
    }

    /**
     * @return Collection<int, ProjectSectionListItem>
     */
    public function getListItems(): Collection
    {
        return $this->listItems;
    }

    public function addListItem(string $text): static
    {
        $this->listItems->add(new ProjectSectionListItem($this, $this->listItems->count(), $text));

        return $this;
    }

    /**
     * @return Collection<int, ProjectSectionTableColumn>
     */
    public function getTableColumns(): Collection
    {
        return $this->tableColumns;
    }

    public function addTableColumn(string $label, bool $sortable = true): ProjectSectionTableColumn
    {
        $column = new ProjectSectionTableColumn($this, $this->tableColumns->count(), $label, $sortable);
        $this->tableColumns->add($column);

        return $column;
    }

    /**
     * @return Collection<int, ProjectSectionTableRow>
     */
    public function getTableRows(): Collection
    {
        return $this->tableRows;
    }

    /**
     * Ajoute une ligne au tableau : une case par colonne, dans l'ordre des colonnes.
     *
     * @param list<string> $texts le texte de chaque case
     *
     * @throws \LogicException si le nombre de cases ne correspond pas au nombre de colonnes
     */
    public function addTableRow(array $texts): ProjectSectionTableRow
    {
        $columns = array_values($this->tableColumns->toArray());

        if (\count($texts) !== \count($columns)) {
            throw new \LogicException('Une ligne du tableau doit avoir une case par colonne.');
        }

        $row = new ProjectSectionTableRow($this, $this->tableRows->count());

        foreach ($columns as $index => $column) {
            $row->addCell($column, $texts[$index]);
        }

        $this->tableRows->add($row);

        return $row;
    }

    /**
     * @return Collection<int, ProjectSectionTag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(string $label, ?ProjectSectionTagColor $color = null): static
    {
        $this->tags->add(new ProjectSectionTag($this, $this->tags->count(), $label, $color));

        return $this;
    }

    /**
     * @return Collection<int, ProjectSectionDefinition>
     */
    public function getDefinitions(): Collection
    {
        return $this->definitions;
    }

    public function addDefinition(string $term, string $definition): static
    {
        $this->definitions->add(new ProjectSectionDefinition($this, $this->definitions->count(), $term, $definition));

        return $this;
    }

    /**
     * @return Collection<int, ProjectSectionBlockFile>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(ProjectFile $file, ?string $caption = null): static
    {
        $this->files->add(new ProjectSectionBlockFile($this, $file, $this->files->count(), $caption));

        return $this;
    }
}
