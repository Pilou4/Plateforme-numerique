<?php

namespace App\Command;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Vérifie que le modèle d'IA répond (Symfony AI + Ollama) :
 *
 *     php bin/console app:ia:tester "Dis bonjour en une phrase."
 *
 * Affiche la réponse et le temps mis. Utilise l'agent « assistant » (config/packages/ai.yaml).
 */
#[AsCommand(name: 'app:ia:tester', description: 'Envoie une question au modèle d\'IA et affiche sa réponse')]
final class AiTestCommand
{
    public function __construct(
        #[Autowire(service: 'ai.agent.assistant')]
        private readonly AgentInterface $agent,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'La question à poser au modèle')]
        string $question,
    ): int {
        $io->text(\sprintf('Question : %s', $question));
        $io->text('Réponse en cours… (la première peut prendre quelques dizaines de secondes, le temps de charger le modèle)');

        $start = microtime(true);

        try {
            $answer = (string) $this->agent->call(new MessageBag(Message::ofUser($question)))->getContent();
        } catch (\Throwable $exception) {
            $io->error([
                'Le modèle n\'a pas répondu : '.$exception->getMessage(),
                'Ollama est-il lancé ? docker compose up -d ollama',
                'Le modèle est-il téléchargé ? docker compose exec ollama ollama list (et OLLAMA_MODEL dans .env)',
            ]);

            return Command::FAILURE;
        }

        $io->section('Réponse');
        $io->writeln($answer);
        $io->newLine();
        $io->success(\sprintf('Réponse reçue en %.1f s.', microtime(true) - $start));

        return Command::SUCCESS;
    }
}
