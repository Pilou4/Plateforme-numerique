<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ProjectCategory;
use App\Entity\ProjectState;
use App\Enum\ProjectStateColor;
use PHPUnit\Framework\TestCase;

/**
 * État d'avancement et catégorie d'un projet.
 */
final class ProjectStateTest extends TestCase
{
    /**
     * Les codes des trois statuts de départ sont enregistrés dans la table project_state
     * et utilisés par les filtres : les changer casserait les données existantes.
     */
    public function testCodesInProgressOrder(): void
    {
        $this->assertSame(['pas_commence', 'en_cours', 'termine'], ProjectState::CODES);
    }

    public function testStateConstructorFillsEverything(): void
    {
        $state = new ProjectState(ProjectState::CODE_DONE, 'Terminé', 'Terminés', 2);

        $this->assertSame('termine', $state->getCode());
        $this->assertSame('Terminé', $state->getLabel());
        $this->assertSame('Terminés', $state->getPluralLabel());
        $this->assertSame(2, $state->getPosition());
    }

    public function testStateIsGreyByDefault(): void
    {
        $this->assertSame(ProjectStateColor::Grey, (new ProjectState('en_pause', 'En pause', 'En pause', 3))->getColor());
    }

    public function testStateCanBeRenamedAndRecoloured(): void
    {
        $state = new ProjectState('en_pause', 'En pause', 'En pause', 3, ProjectStateColor::Orange);

        $state->setLabel('Suspendu')->setPluralLabel('Suspendus')->setColor(ProjectStateColor::Red);

        $this->assertSame('Suspendu', $state->getLabel());
        $this->assertSame('Suspendus', $state->getPluralLabel());
        $this->assertSame(ProjectStateColor::Red, $state->getColor());
        // Le code ne change jamais
        $this->assertSame('en_pause', $state->getCode());
    }

    public function testCategoryCanBeRenamed(): void
    {
        $category = (new ProjectCategory('Jeu', 2))->setName('Jeu vidéo');

        $this->assertSame('Jeu vidéo', $category->getName());
        $this->assertSame(2, $category->getPosition());
    }

    public function testCategoryConstructorFillsEverything(): void
    {
        $category = new ProjectCategory('Jeu', 2);

        $this->assertSame('Jeu', $category->getName());
        $this->assertSame(2, $category->getPosition());
    }
}
