<?php

namespace App\Entity;

/**
 * Une section de contenu : un titre, du HTML et une position dans la page.
 *
 * Partagée par les sections d'un tutoriel (DocumentationSection) et celles de la présentation
 * d'un projet (ProjectSection) : SectionManager les ajoute, modifie, supprime et range
 * de la même façon, quelle que soit la page.
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
