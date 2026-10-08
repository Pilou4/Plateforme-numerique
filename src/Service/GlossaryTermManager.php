<?php

namespace App\Service;

use App\Dto\GlossaryTermPayload;
use App\Entity\GlossaryTerm;
use App\Repository\GlossaryTermRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Ajout, modification et suppression des termes du glossaire.
 *
 * La définition est enregistrée en HTML tel quel : elle est nettoyée à l'affichage
 * (filtre sanitize_html dans le template), comme le reste de la documentation.
 */
final class GlossaryTermManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GlossaryTermRepository $glossaryTermRepository,
    ) {
    }

    public function create(GlossaryTermPayload $payload): GlossaryTerm
    {
        $term = new GlossaryTerm();
        $this->apply($term, $payload);

        $this->entityManager->persist($term);
        $this->entityManager->flush();

        return $term;
    }

    public function update(GlossaryTerm $term, GlossaryTermPayload $payload): void
    {
        $this->apply($term, $payload);
        $term->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }

    public function delete(GlossaryTerm $term): void
    {
        $this->entityManager->remove($term);
        $this->entityManager->flush();
    }

    private function apply(GlossaryTerm $term, GlossaryTermPayload $payload): void
    {
        $name = trim((string) $payload->term);

        $this->assertTermIsUnique($name, $term);

        $term->setTerm($name);
        $term->setDefinition(trim((string) $payload->definition));
    }

    /**
     * Un terme n'existe qu'une fois. La recherche suit la base de données :
     * « docker » et « Docker » sont considérés comme le même terme.
     */
    private function assertTermIsUnique(string $name, GlossaryTerm $term): void
    {
        $existing = $this->glossaryTermRepository->findOneBy(['term' => $name]);

        if (null !== $existing && $existing !== $term) {
            throw new \DomainException(\sprintf('Le terme « %s » existe déjà dans le glossaire.', $existing->getTerm()));
        }
    }
}
