<?php

namespace App\Entity;

/**
 * Une section de contenu : un titre, du HTML et une position dans la page.
 *
 * Utilisée par les sections d'un tutoriel (DocumentationSection), que SectionManager ajoute,
 * modifie, supprime et range. Les sections d'un projet n'en font plus partie : elles sont faites
 * de blocs (ProjectSection, ProjectSectionManager).
 */
interface ContentSectionInterface
{
    public function getId(): ?int;

    public function getTitle(): ?string;

    public function setTitle(string $title): static;

    public function getContent(): ?string;

    public function setContent(string $content): static;

    public function getPosition(): ?int;

    public function setPosition(int $position): static;
}
