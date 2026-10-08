<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Message envoyé avec le formulaire de contact de l'accueil (rempli par ContactType).
 *
 * Une fois valide, il est enregistré dans la table contact (ContactManager) puis envoyé par e-mail (ContactMailer).
 */
final class ContactMessage
{
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.', normalizer: 'trim')]
    #[Assert\Length(max: 100, maxMessage: 'Le prénom ne doit pas dépasser {{ limit }} caractères.')]
    public ?string $firstName = null;

    #[Assert\NotBlank(message: 'Le nom est obligatoire.', normalizer: 'trim')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères.')]
    public ?string $lastName = null;

    /**
     * Sequentially : une seule erreur à la fois (vide, puis format, puis longueur).
     */
    #[Assert\Sequentially([
        new Assert\NotBlank(message: 'L\'adresse e-mail est obligatoire.', normalizer: 'trim'),
        new Assert\Email(message: 'L\'adresse e-mail n\'est pas valide.'),
        new Assert\Length(max: 180, maxMessage: 'L\'adresse e-mail ne doit pas dépasser {{ limit }} caractères.'),
    ])]
    public ?string $email = null;

    /**
     * Facultatif : chiffres, espaces, points, tirets, parenthèses et + en tête (ex. : 06 12 34 56 78, +33 6 12 34 56 78).
     */
    #[Assert\Regex(pattern: '/^\+?[0-9 .()-]{6,30}$/', message: 'Le numéro de téléphone n\'est pas valide.')]
    public ?string $phone = null;

    #[Assert\Length(max: 150, maxMessage: 'Le sujet ne doit pas dépasser {{ limit }} caractères.')]
    public ?string $subject = null;

    /**
     * Sequentially : un message vide affiche seulement « obligatoire », pas aussi « trop court ».
     */
    #[Assert\Sequentially([
        new Assert\NotBlank(message: 'Le message est obligatoire.', normalizer: 'trim'),
        new Assert\Length(
            min: 10,
            max: 5000,
            normalizer: 'trim',
            minMessage: 'Le message doit contenir au moins {{ limit }} caractères.',
            maxMessage: 'Le message ne doit pas dépasser {{ limit }} caractères.',
        ),
    ])]
    public ?string $message = null;
}
