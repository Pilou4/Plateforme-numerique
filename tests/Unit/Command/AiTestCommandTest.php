<?php

namespace App\Tests\Unit\Command;

use App\Command\AiTestCommand;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Agent\Execution\Execution;
use Symfony\AI\Agent\Execution\Update\Result;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Metadata\Metadata;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * La commande est testée avec un faux agent : pas besoin d'Ollama pour lancer les tests.
 * L'agent renvoie une vraie Execution (classe final) qui contient une fausse réponse.
 */
final class AiTestCommandTest extends TestCase
{
    public function testTheAnswerOfTheModelIsDisplayed(): void
    {
        $agent = $this->createMock(AgentInterface::class);
        $agent->expects($this->once())
            ->method('call')
            ->with($this->callback(static fn (MessageBag $messages): bool => 1 === \count($messages->getMessages())))
            ->willReturn($this->createExecution('Bonjour, je suis prêt.'));

        $tester = $this->createTester($agent);

        $this->assertSame(Command::SUCCESS, $tester->execute(['question' => 'Dis bonjour.']));
        $this->assertStringContainsString('Question : Dis bonjour.', $tester->getDisplay());
        $this->assertStringContainsString('Bonjour, je suis prêt.', $tester->getDisplay());
    }

    public function testAnUnreachableModelGivesAHelpfulError(): void
    {
        $agent = $this->createStub(AgentInterface::class);
        $agent->method('call')->willThrowException(new \RuntimeException('Connexion refusée'));

        $tester = $this->createTester($agent);

        $this->assertSame(Command::FAILURE, $tester->execute(['question' => 'Dis bonjour.']));
        $this->assertStringContainsString('Connexion refusée', $tester->getDisplay());
        $this->assertStringContainsString('docker compose up -d ollama', $tester->getDisplay());
    }

    /**
     * Une exécution d'agent terminée, dont la réponse est le texte donné.
     */
    private function createExecution(string $answer): Execution
    {
        $result = $this->createStub(ResultInterface::class);
        $result->method('getContent')->willReturn($answer);
        $result->method('getMetadata')->willReturn(new Metadata());

        return new Execution(static function () use ($result): \Generator {
            yield new Result($result);
        });
    }

    private function createTester(AgentInterface $agent): CommandTester
    {
        $application = new Application();
        $application->addCommand(new AiTestCommand($agent));

        return new CommandTester($application->find('app:ia:tester'));
    }
}
