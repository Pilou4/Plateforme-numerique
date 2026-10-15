<?php

namespace App\Service;

/**
 * Mise en forme simple des textes des sections, sans HTML saisi par l'utilisateur :
 *
 *   **gras**              → <strong class="inline-format__bold">gras</strong>
 *   *italique*            → <em class="inline-format__italic">italique</em>
 *   `code`                → <code class="inline-format__code">code</code>
 *   [texte](https://…)    → <a class="inline-format__link" href="https://…">texte</a>   (nouvel onglet pour un autre site)
 *
 * Chaque balise a sa classe (styles dans app.css : aucun style sur les balises seules).
 *
 * Le texte est d'abord échappé (htmlspecialchars) : une balise tapée par l'utilisateur s'affiche
 * telle quelle et ne peut rien casser. Seules les balises ci-dessus sont ajoutées par ce service.
 * Un lien n'est accepté que vers http(s), mailto, une adresse du site (/…) ou une ancre (#…) :
 * « javascript:… » reste du texte.
 */
final class InlineFormatter
{
    /**
     * Caractères réservés à l'usage privé (Unicode) : ils servent de repères pendant la mise en forme
     * et sont retirés du texte d'origine.
     */
    private const string MARK_START = "\u{E000}";
    private const string MARK_END = "\u{E001}";

    /** @var list<string> morceaux déjà transformés en HTML (code, liens), remis à la fin */
    private array $protected = [];

    public function format(string $text): string
    {
        $this->protected = [];
        $html = htmlspecialchars(str_replace([self::MARK_START, self::MARK_END], '', $text), \ENT_QUOTES | \ENT_HTML5, 'UTF-8');

        // 1. Le code d'abord : rien n'est mis en forme à l'intérieur (**a** reste **a**)
        $html = preg_replace_callback('/`([^`]+)`/u', fn (array $match): string => $this->protect('<code class="inline-format__code">'.$match[1].'</code>'), $html);

        // 2. Les liens : leur adresse ne doit pas être touchée par le gras ou l'italique
        $html = preg_replace_callback('/\[([^\]\n]+)\]\(([^)\s]+)\)/u', $this->formatLink(...), $html);

        // 3. Gras, puis italique
        $html = $this->formatEmphasis($html);

        return $this->restore($html);
    }

    /**
     * @param array<int, string> $match [0] : tout le lien, [1] : le texte (échappé), [2] : l'adresse (échappée)
     */
    private function formatLink(array $match): string
    {
        $url = html_entity_decode($match[2], \ENT_QUOTES | \ENT_HTML5, 'UTF-8');

        if (1 !== preg_match('#^(https?://|mailto:|/(?!/)|\#)#i', $url)) {
            return $match[0];
        }

        $attributes = 'class="inline-format__link" href="'.htmlspecialchars($url, \ENT_QUOTES | \ENT_HTML5, 'UTF-8').'"';

        // Un autre site s'ouvre dans un nouvel onglet : le visiteur ne quitte pas la plateforme
        if (1 === preg_match('#^https?://#i', $url)) {
            $attributes .= ' target="_blank" rel="noopener noreferrer"';
        }

        return $this->protect('<a '.$attributes.'>'.$this->formatEmphasis($match[1]).'</a>');
    }

    private function formatEmphasis(string $html): string
    {
        $html = preg_replace('/\*\*(?=\S)(.+?)(?<=\S)\*\*/u', '<strong class="inline-format__bold">$1</strong>', $html);

        return preg_replace('/(?<![*\w])\*(?=[^\s*])(.+?)(?<=[^\s*])\*(?![*\w])/u', '<em class="inline-format__italic">$1</em>', $html);
    }

    private function protect(string $html): string
    {
        $this->protected[] = $html;

        return self::MARK_START.(\count($this->protected) - 1).self::MARK_END;
    }

    private function restore(string $html): string
    {
        return preg_replace_callback(
            '/'.self::MARK_START.'(\d+)'.self::MARK_END.'/u',
            fn (array $match): string => $this->protected[(int) $match[1]] ?? '',
            $html,
        );
    }
}
