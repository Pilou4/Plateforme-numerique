<?php

namespace App\Tests\Unit\Service;

use App\Service\InlineFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InlineFormatterTest extends TestCase
{
    #[DataProvider('formatProvider')]
    public function testFormat(string $text, string $expected): void
    {
        $this->assertSame($expected, (new InlineFormatter())->format($text));
    }

    public static function formatProvider(): iterable
    {
        yield 'texte simple' => ['Bonjour', 'Bonjour'];
        yield 'gras' => ['Un **mot** important', 'Un <strong class="inline-format__bold">mot</strong> important'];
        yield 'italique' => ['Un *mot* penché', 'Un <em class="inline-format__italic">mot</em> penché'];
        yield 'gras et italique' => ['**a** et *b*', '<strong class="inline-format__bold">a</strong> et <em class="inline-format__italic">b</em>'];
        yield 'code' => ['Lance `composer install`', 'Lance <code class="inline-format__code">composer install</code>'];
        yield 'pas de mise en forme dans le code' => ['`**pas gras**`', '<code class="inline-format__code">**pas gras**</code>'];
        yield 'étoile seule' => ['5 * 3 = 15', '5 * 3 = 15'];
        yield 'étoiles collées à des lettres' => ['a*b*c', 'a*b*c'];
        yield 'lien vers une page du site' => ['Voir [la doc](/app/documentation)', 'Voir <a class="inline-format__link" href="/app/documentation">la doc</a>'];
        yield 'lien vers une ancre' => ['[haut](#section-3)', '<a class="inline-format__link" href="#section-3">haut</a>'];
        yield 'lien mailto' => ['[écrire](mailto:a@b.fr)', '<a class="inline-format__link" href="mailto:a@b.fr">écrire</a>'];
        yield 'lien en gras' => ['[**Doctrine**](/doc)', '<a class="inline-format__link" href="/doc"><strong class="inline-format__bold">Doctrine</strong></a>'];
    }

    public function testExternalLinkOpensInANewTab(): void
    {
        $html = (new InlineFormatter())->format('[Symfony](https://symfony.com/doc)');

        $this->assertSame('<a class="inline-format__link" href="https://symfony.com/doc" target="_blank" rel="noopener noreferrer">Symfony</a>', $html);
    }

    public function testHtmlTypedByTheUserIsEscaped(): void
    {
        $html = (new InlineFormatter())->format('<script>alert("x")</script> & <b>gras</b>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&amp;', $html);
    }

    #[DataProvider('refusedLinkProvider')]
    public function testDangerousLinksStayText(string $text): void
    {
        $html = (new InlineFormatter())->format($text);

        $this->assertStringNotContainsString('<a ', $html);
    }

    public static function refusedLinkProvider(): iterable
    {
        yield 'javascript' => ['[clic](javascript:alert(1))'];
        yield 'data' => ['[clic](data:text/html,x)'];
        yield 'adresse sans protocole (//)' => ['[clic](//pirate.example)'];
        yield 'protocole inconnu' => ['[clic](ftp://serveur)'];
    }

    public function testQuotesInALinkCannotBreakTheAttribute(): void
    {
        $html = (new InlineFormatter())->format('[x](https://a.fr/"onmouseover="alert(1))');

        $this->assertStringNotContainsString('" onmouseover', $html);
        $this->assertStringNotContainsString('"onmouseover="', $html);
    }

    public function testPrivateMarkersTypedByTheUserAreRemoved(): void
    {
        $html = (new InlineFormatter())->format("a\u{E000}0\u{E001}b `c`");

        $this->assertSame('a0b <code class="inline-format__code">c</code>', $html);
    }

    public function testTheFormatterCanBeReused(): void
    {
        $formatter = new InlineFormatter();
        $formatter->format('`un`');

        $this->assertSame('<code class="inline-format__code">deux</code>', $formatter->format('`deux`'));
    }
}
