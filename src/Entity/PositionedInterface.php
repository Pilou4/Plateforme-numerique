<?php

namespace App\Entity;

/**
 * Un élément d'une liste dont l'ordre se change depuis l'app (monter / descendre) :
 * catégories et statuts des projets. Voir App\Service\PositionMover.
 */
interface PositionedInterface
{
    public function getPosition(): int;

    public function setPosition(int $position): static;
}
