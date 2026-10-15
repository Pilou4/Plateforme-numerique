<?php

namespace App\Service;

use App\Dto\ProjectStatePayload;
use App\Entity\ProjectState;
use App\Enum\ProjectStateColor;
use App\Repository\ProjectRepository;
use App\Repository\ProjectStateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Ajout, modification, suppression et ordre des statuts de projet (page Paramètres).
 *
 * Règles :
 * - le code est calculé à partir du nom à la création (« En pause » → en_pause), puis ne change plus ;
 * - un statut utilisé par des projets ne peut pas être supprimé : il faut d'abord changer leur statut ;
 * - il reste toujours au moins un statut ;
 * - un nouveau projet prend le premier statut de la liste : l'ordre se change avec monter / descendre.
 */
final class ProjectStateManager
{
    private const int CODE_MAX_LENGTH = 30;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectStateRepository $projectStateRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly SluggerInterface $slugger,
        private readonly PositionMover $positionMover,
    ) {
    }

    /**
     * Le nouveau statut est ajouté à la fin de la liste.
     *
     * @throws \DomainException si un statut porte déjà ce nom
     */
    public function create(ProjectStatePayload $payload): ProjectState
    {
        $label = trim((string) $payload->label);
        $this->assertLabelIsFree($label, null);

        $state = new ProjectState(
            $this->generateUniqueCode($label),
            $label,
            $this->cleanPluralLabel($payload->pluralLabel, $label),
            $this->projectStateRepository->getNextPosition(),
            ProjectStateColor::from((string) $payload->color),
        );

        $this->entityManager->persist($state);
        $this->entityManager->flush();

        return $state;
    }

    /**
     * Le code ne change pas : seuls le nom, le nom au pluriel et la couleur sont modifiés.
     *
     * @throws \DomainException si un autre statut porte déjà ce nom
     */
    public function update(ProjectState $state, ProjectStatePayload $payload): void
    {
        $label = trim((string) $payload->label);
        $this->assertLabelIsFree($label, $state);

        $state->setLabel($label);
        $state->setPluralLabel($this->cleanPluralLabel($payload->pluralLabel, $label));
        $state->setColor(ProjectStateColor::from((string) $payload->color));

        $this->entityManager->flush();
    }

    /**
     * @throws \DomainException si des projets utilisent ce statut, ou si c'est le dernier statut
     */
    public function delete(ProjectState $state): void
    {
        $projectCount = $this->projectRepository->count(['state' => $state]);

        if ($projectCount > 0) {
            throw new \DomainException(\sprintf(
                '« %s » est utilisé par %d projet%s : change d\'abord %s statut.',
                $state->getLabel(),
                $projectCount,
                $projectCount > 1 ? 's' : '',
                $projectCount > 1 ? 'leur' : 'son',
            ));
        }

        if ($this->projectStateRepository->count([]) <= 1) {
            throw new \DomainException('Il faut garder au moins un statut : un nouveau projet prend le premier de la liste.');
        }

        $this->entityManager->remove($state);
        $this->entityManager->flush();
    }

    /**
     * Monte ou descend le statut d'un cran dans la liste (déjà en haut ou en bas : rien ne change).
     * Le premier statut de la liste est celui des nouveaux projets.
     *
     * @param string $direction SectionMovePayload::UP ou SectionMovePayload::DOWN
     */
    public function move(ProjectState $state, string $direction): void
    {
        if ($this->positionMover->move($this->projectStateRepository->findAllOrdered(), $state, $direction)) {
            $this->entityManager->flush();
        }
    }

    /**
     * Comme pour la base de données, « en pause » et « En pause » sont considérés comme le même nom.
     */
    private function assertLabelIsFree(string $label, ?ProjectState $state): void
    {
        $existing = $this->projectStateRepository->findOneBy(['label' => $label]);

        if (null !== $existing && $existing !== $state) {
            throw new \DomainException(\sprintf('Le statut « %s » existe déjà.', $existing->getLabel()));
        }
    }

    /**
     * « En pause » → en_pause, puis en_pause_2, en_pause_3… si le code est déjà pris.
     */
    private function generateUniqueCode(string $label): string
    {
        // Place gardée pour le suffixe (_2, _3…) dans les 30 caractères de la colonne
        $baseCode = mb_substr($this->slugger->slug($label, '_')->lower()->toString(), 0, self::CODE_MAX_LENGTH - 4);
        $baseCode = '' === $baseCode ? 'statut' : $baseCode;
        $code = $baseCode;
        $suffix = 2;

        while (null !== $this->projectStateRepository->findOneBy(['code' => $code])) {
            $code = \sprintf('%s_%d', $baseCode, $suffix);
            ++$suffix;
        }

        return $code;
    }

    private function cleanPluralLabel(?string $pluralLabel, string $label): string
    {
        $pluralLabel = trim((string) $pluralLabel);

        return '' === $pluralLabel ? $label : $pluralLabel;
    }
}
