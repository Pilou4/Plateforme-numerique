<?php

namespace App\Service;

use App\Dto\SectionMovePayload;
use App\Entity\PositionedInterface;

/**
 * Monte ou descend un élément d'une liste ordonnée (catégories, statuts…) d'un cran :
 * il échange sa place avec son voisin, puis les positions sont renumérotées 0, 1, 2…
 */
final class PositionMover
{
    /**
     * @param list<PositionedInterface> $ordered la liste, dans l'ordre actuel
     * @param string                    $direction SectionMovePayload::UP ou SectionMovePayload::DOWN
     *
     * @return bool false si rien n'a changé (déjà en haut ou en bas, ou absent de la liste)
     */
    public function move(array $ordered, PositionedInterface $item, string $direction): bool
    {
        $ordered = array_values($ordered);
        $index = array_search($item, $ordered, true);

        if (false === $index) {
            return false;
        }

        $targetIndex = SectionMovePayload::UP === $direction ? $index - 1 : $index + 1;

        if (!isset($ordered[$targetIndex])) {
            return false;
        }

        [$ordered[$index], $ordered[$targetIndex]] = [$ordered[$targetIndex], $ordered[$index]];

        foreach ($ordered as $position => $element) {
            $element->setPosition($position);
        }

        return true;
    }
}
