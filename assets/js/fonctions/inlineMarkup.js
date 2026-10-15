/**
 * Mise en forme des textes des sections, côté navigateur.
 *
 * En base, le texte garde des marques simples (comme App\Service\InlineFormatter côté serveur) :
 *   **gras**   *italique*   `code`   [texte](https://…)
 * Dans l'éditeur, on ne voit jamais ces marques : le texte est affiché mis en forme (champ « riche »).
 *
 *   markupToHtml(texte)    texte en base → HTML affiché (tout le reste est échappé)
 *   htmlToMarkup(élément)  contenu d'un champ riche → texte en base
 */

const ESCAPES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
// Seules ces adresses deviennent des liens (« javascript:… » reste du texte), comme côté serveur
const ALLOWED_LINK = /^(https?:\/\/|mailto:|\/(?!\/)|#)/i;
const EXTERNAL_LINK = /^https?:\/\//i;

export function escapeHtml(text) {
    return String(text).replace(/[&<>"']/g, (character) => ESCAPES[character]);
}

export function isAllowedLink(url) {
    return ALLOWED_LINK.test(url.trim());
}

/**
 * Texte en base → HTML (mêmes règles et mêmes classes que App\Service\InlineFormatter).
 */
export function markupToHtml(text) {
    const kept = [];
    const keep = (html) => {
        kept.push(html);

        return `\u{E000}${kept.length - 1}\u{E001}`;
    };

    let html = escapeHtml(String(text ?? '').replace(/[\u{E000}\u{E001}]/gu, ''));

    // 1. Le code d'abord : rien n'est mis en forme à l'intérieur
    html = html.replace(/`([^`]+)`/g, (match, code) => keep(`<code class="inline-format__code">${code}</code>`));

    // 2. Les liens (adresses autorisées seulement)
    html = html.replace(/\[([^\]\n]+)\]\(([^)\s]+)\)/g, (match, label, escapedUrl) => {
        const url = decodeEntities(escapedUrl);

        if (!isAllowedLink(url)) {
            return match;
        }

        const external = EXTERNAL_LINK.test(url) ? ' target="_blank" rel="noopener noreferrer"' : '';

        return keep(`<a class="inline-format__link" href="${escapeHtml(url)}"${external}>${formatEmphasis(label)}</a>`);
    });

    // 3. Gras, puis italique
    html = formatEmphasis(html);

    return html.replace(/\u{E000}(\d+)\u{E001}/gu, (match, index) => kept[Number(index)]);
}

function formatEmphasis(html) {
    return html
        .replace(/\*\*(?=\S)(.+?)(?<=\S)\*\*/g, '<strong class="inline-format__bold">$1</strong>')
        .replace(/(?<![*\w])\*(?=[^\s*])(.+?)(?<=[^\s*])\*(?![*\w])/g, '<em class="inline-format__italic">$1</em>');
}

function decodeEntities(text) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = text;

    return textarea.value;
}

/**
 * Contenu d'un champ riche → texte en base.
 * Une ligne de paragraphe (Entrée) devient un retour à la ligne : chaque ligne sera un <p>.
 */
export function htmlToMarkup(element) {
    return nodesToMarkup(element.childNodes).replace(/ /g, ' ').replace(/\n+$/, '');
}

function nodesToMarkup(nodes) {
    let markup = '';

    for (const node of nodes) {
        markup += nodeToMarkup(node, markup);
    }

    return markup;
}

function nodeToMarkup(node, before) {
    if (node.nodeType === Node.TEXT_NODE) {
        return node.textContent;
    }

    if (node.nodeType !== Node.ELEMENT_NODE) {
        return '';
    }

    const inner = () => nodesToMarkup(node.childNodes);

    switch (node.tagName) {
        case 'BR':
            return '\n';
        case 'B':
        case 'STRONG':
            return wrap(inner(), '**');
        case 'I':
        case 'EM':
            return wrap(inner(), '*');
        case 'CODE':
            return node.textContent.trim() === '' ? node.textContent : `\`${node.textContent.replace(/`/g, '')}\``;
        case 'A': {
            const href = node.getAttribute('href') ?? '';

            return isAllowedLink(href) && inner().trim() !== '' ? `[${inner()}](${href})` : inner();
        }
        case 'DIV':
        case 'P':
            // Une ligne créée par Entrée : retour à la ligne avant (sauf tout au début)
            return (before !== '' && !before.endsWith('\n') ? '\n' : '') + inner() + '\n';
        default:
            return inner();
    }
}

/**
 * Les espaces au bord restent hors des marques (« **mot** » et non « ** mot** »).
 */
function wrap(text, mark) {
    const match = text.match(/^(\s*)([\s\S]*?)(\s*)$/);

    return match[2] === '' ? text : `${match[1]}${mark}${match[2]}${mark}${match[3]}`;
}
