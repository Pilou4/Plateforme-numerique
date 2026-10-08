<?php

namespace App\Form;

use App\Dto\ContactMessage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de contact de l'accueil.
 *
 * Le HTML est écrit à la main dans templates/home/_contact_form.html.twig (accessibilité, style) :
 * ce type décrit seulement les champs, leurs libellés et la protection CSRF (activée par défaut).
 *
 * @extends AbstractType<ContactMessage>
 */
final class ContactType extends AbstractType
{
    /**
     * Champ piège invisible : un humain le laisse vide, un robot qui remplit tout le remplit.
     */
    public const string HONEYPOT_FIELD = 'website';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, ['label' => 'Prénom'])
            ->add('lastName', TextType::class, ['label' => 'Nom'])
            ->add('email', EmailType::class, ['label' => 'E-mail'])
            ->add('phone', TelType::class, ['label' => 'Téléphone', 'required' => false])
            ->add('subject', TextType::class, ['label' => 'Sujet', 'required' => false])
            ->add('message', TextareaType::class, ['label' => 'Message'])
            ->add(self::HONEYPOT_FIELD, TextType::class, [
                'label' => 'Ne pas remplir ce champ',
                'mapped' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactMessage::class,
            // Message affiché si le jeton CSRF est invalide (page restée ouverte trop longtemps, envoi depuis un autre site…)
            'csrf_message' => 'Le formulaire a expiré. Merci de le renvoyer.',
        ]);
    }

    /**
     * Préfixe des champs dans le HTML : contact[firstName], contact[email]…
     */
    public function getBlockPrefix(): string
    {
        return 'contact';
    }
}
