<?php

namespace App\Controller;

use App\Dto\ContactMessage;
use App\Form\ContactType;
use App\Service\ContactMailer;
use App\Service\ContactManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    private const string CONTACT_ANCHOR = 'contact';

    /**
     * Affiche l'accueil et traite le formulaire de contact (même adresse : /).
     *
     * - Formulaire valide : demande enregistrée dans la table contact, e-mail envoyé, puis redirection
     *   vers /#contact avec un bandeau de confirmation (recharger la page ne renvoie pas le message).
     *   Si l'e-mail ne part pas, la demande est quand même enregistrée : l'erreur est notée dans les logs.
     * - Formulaire invalide : la page est réaffichée avec les erreurs sous les champs (code 422).
     *
     * @param RateLimiterFactoryInterface $contactFormLimiter limite « contact_form » (config/packages/rate_limiter.yaml)
     */
    #[Route('/', name: 'home', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ContactManager $contactManager,
        ContactMailer $contactMailer,
        RateLimiterFactoryInterface $contactFormLimiter,
        LoggerInterface $logger,
    ): Response {
        $form = $this->createForm(ContactType::class, new ContactMessage());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Champ piège rempli : c'est un robot. On fait comme si tout allait bien, sans rien envoyer.
            if ($this->isHoneypotFilled($form)) {
                return $this->redirectToContact();
            }

            $limit = $contactFormLimiter->create($request->getClientIp() ?? 'inconnu')->consume();

            if (!$limit->isAccepted()) {
                $form->addError(new FormError('Trop de messages envoyés en peu de temps. Merci de réessayer dans quelques minutes.'));
            } else {
                $contact = $contactManager->create($form->getData());

                try {
                    $contactMailer->send($contact);
                } catch (TransportExceptionInterface $exception) {
                    // La demande est enregistrée en base : elle n'est pas perdue, on le signale seulement dans les logs
                    $logger->error('Formulaire de contact : l\'e-mail de la demande n°{id} n\'a pas pu être envoyé.', [
                        'id' => $contact->getId(),
                        'exception' => $exception,
                    ]);
                }

                return $this->redirectToContact();
            }
        }

        // Un formulaire envoyé avec des erreurs renvoie automatiquement le code 422
        return $this->render('home/index.html.twig', [
            'contact_form' => $form,
        ]);
    }

    private function isHoneypotFilled(FormInterface $form): bool
    {
        return '' !== trim((string) $form->get(ContactType::HONEYPOT_FIELD)->getData());
    }

    private function redirectToContact(): Response
    {
        $this->addFlash('success', 'Merci, votre message a bien été envoyé. Je vous réponds rapidement.');

        return $this->redirectToRoute('home', ['_fragment' => self::CONTACT_ANCHOR]);
    }
}
