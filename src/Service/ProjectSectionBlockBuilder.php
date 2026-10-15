<?php

namespace App\Service;

use App\Entity\ProjectFile;
use App\Entity\ProjectSection;
use App\Entity\ProjectSectionBlock;
use App\Enum\ProjectSectionBlockType;
use App\Repository\ProjectFileRepository;

/**
 * Transforme les blocs envoyés par l'éditeur (voir ProjectSectionPayload) en entités,
 * après avoir vérifié leur contenu.
 *
 * Tous les blocs sont vérifiés avant de toucher à la section : si l'un d'eux est refusé,
 * la section garde ses anciens blocs. Les textes sont nettoyés (espaces autour retirés,
 * lignes et éléments vides ignorés). Un bloc vide est refusé avec un message qui dit lequel.
 */
final class ProjectSectionBlockBuilder
{
    public const int MAX_TEXT_LENGTH = 5000;
    public const int MAX_LABEL_LENGTH = 255;
    public const int MAX_TAG_LENGTH = 100;
    public const int MAX_ITEMS = 200;
    public const int MAX_TABLE_COLUMNS = 20;

    public function __construct(
        private readonly ProjectFileRepository $projectFileRepository,
    ) {
    }

    /**
     * Remplace tous les blocs de la section par ceux envoyés, dans l'ordre.
     *
     * @param list<mixed> $blocksData
     *
     * @throws \DomainException si un bloc est vide ou mal rempli (le message dit lequel)
     */
    public function replaceBlocks(ProjectSection $section, array $blocksData): void
    {
        $blocks = [];

        foreach (array_values($blocksData) as $index => $data) {
            $blocks[] = $this->buildBlock($section, \is_array($data) ? $data : [], $index + 1);
        }

        $section->clearBlocks();

        foreach ($blocks as $block) {
            $section->addBlock($block);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildBlock(ProjectSection $section, array $data, int $number): ProjectSectionBlock
    {
        $type = ProjectSectionBlockType::tryFrom(\is_string($data['type'] ?? null) ? $data['type'] : '')
            ?? throw new \DomainException(\sprintf('Bloc %d : ce type de bloc n\'existe pas.', $number));

        $block = new ProjectSectionBlock($type);
        $context = \sprintf('Bloc %d (%s)', $number, $type->label());

        match ($type) {
            ProjectSectionBlockType::Paragraph => $this->fillParagraph($block, $data, $context),
            ProjectSectionBlockType::Subtitle => $this->fillSubtitle($block, $data, $context),
            ProjectSectionBlockType::List => $this->fillList($block, $data, $context),
            ProjectSectionBlockType::Table => $this->fillTable($block, $data, $context),
            ProjectSectionBlockType::Tags => $this->fillTags($block, $data, $context),
            ProjectSectionBlockType::Definitions => $this->fillDefinitions($block, $data, $context),
            ProjectSectionBlockType::Images, ProjectSectionBlockType::Documents => $this->fillFiles($block, $section, $data, $context),
        };

        return $block;
    }

    /**
     * Chaque ligne non vide du texte devient un paragraphe.
     *
     * @param array<string, mixed> $data
     */
    private function fillParagraph(ProjectSectionBlock $block, array $data, string $context): void
    {
        $lines = $this->cleanList(preg_split('/\R/u', $this->readString($data, 'text')) ?: []);
        $this->assertNotEmpty($lines, $context);

        foreach ($lines as $line) {
            $block->addParagraph($this->limit($line, self::MAX_TEXT_LENGTH, $context));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function fillSubtitle(ProjectSectionBlock $block, array $data, string $context): void
    {
        $text = trim($this->readString($data, 'text'));
        $this->assertNotEmpty('' === $text ? [] : [$text], $context);

        $block->setSubtitle($this->limit($text, self::MAX_LABEL_LENGTH, $context));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function fillList(ProjectSectionBlock $block, array $data, string $context): void
    {
        $items = $this->cleanList($this->readList($data, 'items'));
        $this->assertNotEmpty($items, $context);
        $this->assertMaxCount($items, self::MAX_ITEMS, $context);

        $block->setOrdered(true === ($data['ordered'] ?? false));

        foreach ($items as $item) {
            $block->addListItem($this->limit($item, self::MAX_TEXT_LENGTH, $context));
        }
    }

    /**
     * Les lignes entièrement vides sont ignorées ; il faut au moins une colonne et une ligne.
     *
     * @param array<string, mixed> $data
     */
    private function fillTable(ProjectSectionBlock $block, array $data, string $context): void
    {
        $columns = array_map(static fn (mixed $label): string => trim(\is_scalar($label) ? (string) $label : ''), $this->readList($data, 'columns'));

        if ([] === $columns) {
            throw new \DomainException($context.' : le tableau doit avoir au moins une colonne.');
        }

        $this->assertMaxCount($columns, self::MAX_TABLE_COLUMNS, $context);

        $rows = [];
        foreach ($this->readList($data, 'rows') as $row) {
            $cells = array_map(static fn (mixed $text): string => trim(\is_scalar($text) ? (string) $text : ''), \is_array($row) ? array_values($row) : []);

            if (\count($cells) !== \count($columns)) {
                throw new \DomainException($context.' : chaque ligne doit avoir une case par colonne.');
            }

            if ('' !== implode('', $cells)) {
                $rows[] = $cells;
            }
        }

        if ([] === $rows) {
            throw new \DomainException($context.' : remplis au moins une ligne du tableau, ou supprime le bloc.');
        }

        $this->assertMaxCount($rows, self::MAX_ITEMS, $context);

        foreach ($columns as $label) {
            $block->addTableColumn($this->limit($label, self::MAX_LABEL_LENGTH, $context));
        }

        foreach ($rows as $cells) {
            $block->addTableRow(array_map(fn (string $text): string => $this->limit($text, self::MAX_TEXT_LENGTH, $context), $cells));
        }
    }

    /**
     * Un tag en double (même mot, majuscules ou non) n'est gardé qu'une fois.
     *
     * @param array<string, mixed> $data
     */
    private function fillTags(ProjectSectionBlock $block, array $data, string $context): void
    {
        $tags = [];
        foreach ($this->cleanList($this->readList($data, 'tags')) as $tag) {
            $tags[mb_strtolower($tag)] ??= $tag;
        }

        $this->assertNotEmpty($tags, $context);
        $this->assertMaxCount($tags, self::MAX_ITEMS, $context);

        foreach ($tags as $tag) {
            $block->addTag($this->limit($tag, self::MAX_TAG_LENGTH, $context));
        }
    }

    /**
     * Les paires entièrement vides sont ignorées ; une paire à moitié remplie est refusée.
     *
     * @param array<string, mixed> $data
     */
    private function fillDefinitions(ProjectSectionBlock $block, array $data, string $context): void
    {
        $pairs = [];
        foreach ($this->readList($data, 'items') as $item) {
            $term = trim(\is_array($item) && \is_scalar($item['term'] ?? null) ? (string) $item['term'] : '');
            $definition = trim(\is_array($item) && \is_scalar($item['definition'] ?? null) ? (string) $item['definition'] : '');

            if ('' === $term && '' === $definition) {
                continue;
            }

            if ('' === $term || '' === $definition) {
                throw new \DomainException($context.' : chaque terme doit avoir sa définition (et inversement).');
            }

            $pairs[] = [$term, $definition];
        }

        $this->assertNotEmpty($pairs, $context);
        $this->assertMaxCount($pairs, self::MAX_ITEMS, $context);

        foreach ($pairs as [$term, $definition]) {
            $block->addDefinition($this->limit($term, self::MAX_LABEL_LENGTH, $context), $this->limit($definition, self::MAX_TEXT_LENGTH, $context));
        }
    }

    /**
     * Les fichiers doivent appartenir au projet de la section ; un bloc images n'accepte que des images.
     * Un même fichier n'est gardé qu'une fois dans le bloc.
     *
     * @param array<string, mixed> $data
     */
    private function fillFiles(ProjectSectionBlock $block, ProjectSection $section, array $data, string $context): void
    {
        $files = [];
        foreach ($this->readList($data, 'files') as $item) {
            $id = \is_array($item) && is_numeric($item['id'] ?? null) ? (int) $item['id'] : 0;
            $file = $this->findProjectFile($section, $id) ?? throw new \DomainException($context.' : un des fichiers n\'existe plus dans ce projet.');

            if (ProjectSectionBlockType::Images === $block->getType() && !$file->isImage()) {
                throw new \DomainException(\sprintf('%s : « %s » n\'est pas une image (utilise un bloc Documents).', $context, $file->getTitle()));
            }

            $caption = trim(\is_array($item) && \is_scalar($item['caption'] ?? null) ? (string) $item['caption'] : '');
            $files[$id] ??= [$file, '' === $caption ? null : $this->limit($caption, self::MAX_LABEL_LENGTH, $context)];
        }

        $this->assertNotEmpty($files, $context);
        $this->assertMaxCount($files, self::MAX_ITEMS, $context);

        foreach ($files as [$file, $caption]) {
            $block->addFile($file, $caption);
        }
    }

    private function findProjectFile(ProjectSection $section, int $id): ?ProjectFile
    {
        $file = $id > 0 ? $this->projectFileRepository->find($id) : null;

        return null !== $file && $file->getProject() === $section->getProject() ? $file : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function readString(array $data, string $key): string
    {
        return \is_scalar($data[$key] ?? null) ? (string) $data[$key] : '';
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<mixed>
     */
    private function readList(array $data, string $key): array
    {
        return \is_array($data[$key] ?? null) ? array_values($data[$key]) : [];
    }

    /**
     * Textes nettoyés, sans les vides.
     *
     * @param list<mixed> $values
     *
     * @return list<string>
     */
    private function cleanList(array $values): array
    {
        $texts = array_map(static fn (mixed $value): string => trim(\is_scalar($value) ? (string) $value : ''), $values);

        return array_values(array_filter($texts, static fn (string $text): bool => '' !== $text));
    }

    /**
     * @param array<mixed> $values
     */
    private function assertNotEmpty(array $values, string $context): void
    {
        if ([] === $values) {
            throw new \DomainException($context.' : le bloc est vide, remplis-le ou supprime-le.');
        }
    }

    /**
     * @param array<mixed> $values
     */
    private function assertMaxCount(array $values, int $max, string $context): void
    {
        if (\count($values) > $max) {
            throw new \DomainException(\sprintf('%s : %d éléments au maximum.', $context, $max));
        }
    }

    private function limit(string $text, int $maxLength, string $context): string
    {
        if (mb_strlen($text) > $maxLength) {
            throw new \DomainException(\sprintf('%s : un texte dépasse %d caractères.', $context, $maxLength));
        }

        return $text;
    }
}
