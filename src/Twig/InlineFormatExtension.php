<?php

namespace App\Twig;

use App\Service\InlineFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Filtre Twig « inline_format » : {{ paragraph.text|inline_format }}
 * Applique la mise en forme simple des sections (voir InlineFormatter). Le résultat est du HTML sûr :
 * le texte est échappé avant que les balises <strong>, <em>, <code> et <a> soient ajoutées.
 */
final class InlineFormatExtension extends AbstractExtension
{
    public function __construct(
        private readonly InlineFormatter $inlineFormatter,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('inline_format', $this->inlineFormatter->format(...), ['is_safe' => ['html']]),
        ];
    }
}
