/**
 * Barre de recherche de l'en-tête des pages de l'app (templates/app/_partials/_page_header.html.twig).
 *
 * À chaque frappe, l'événement « page:search » est envoyé sur document :
 *      event.detail = { query: 'texte tapé', normalizedQuery: 'texte tape' }
 *
 * ── Listes affichées par Twig : rien à écrire en JavaScript ─────────────
 *      data-search-scope   sur le conteneur de la liste
 *      data-search-item    sur chaque élément à filtrer (texte cherché : data-search-text, sinon son contenu)
 *      data-search-group   (facultatif) groupe masqué quand aucun de ses éléments ne correspond
 *      data-search-empty   (facultatif) message affiché quand rien ne correspond
 *
 * ── Listes affichées par JavaScript (tâches, fichiers) ──────────────────
 *      document.addEventListener('page:search', …) puis réafficher la liste
 *      avec matchesSearch(texte, event.detail.normalizedQuery).
 */

export const PAGE_SEARCH_EVENT = 'page:search';

export class PageSearch {
    constructor(input) {
        this.input = input;
        this.scopes = document.querySelectorAll('[data-search-scope]');

        this.handleInput = this.handleInput.bind(this);
        this.handleKeyDown = this.handleKeyDown.bind(this);

        input.addEventListener('input', this.handleInput);
        input.addEventListener('keydown', this.handleKeyDown);
    }

    handleInput() {
        const query = this.input.value.trim();
        const normalizedQuery = normalizeSearchText(query);

        for (const scope of this.scopes) {
            filterScope(scope, normalizedQuery);
        }

        document.dispatchEvent(new CustomEvent(PAGE_SEARCH_EVENT, { detail: { query, normalizedQuery } }));
    }

    /**
     * Échap vide la recherche.
     */
    handleKeyDown(event) {
        if (event.key === 'Escape' && this.input.value !== '') {
            this.input.value = '';
            this.handleInput();
        }
    }
}

/**
 * Minuscules et sans accents : « Clé » est trouvé en tapant « cle ».
 */
export function normalizeSearchText(text) {
    return String(text ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase();
}

/**
 * Une recherche vide correspond à tout.
 */
export function matchesSearch(text, normalizedQuery) {
    return normalizedQuery === '' || normalizeSearchText(text).includes(normalizedQuery);
}

function filterScope(scope, normalizedQuery) {
    let visibleCount = 0;

    for (const item of scope.querySelectorAll('[data-search-item]')) {
        const isVisible = matchesSearch(item.dataset.searchText ?? item.textContent, normalizedQuery);

        item.hidden = !isVisible;
        visibleCount += isVisible ? 1 : 0;
    }

    for (const group of scope.querySelectorAll('[data-search-group]')) {
        group.hidden = group.querySelector('[data-search-item]:not([hidden])') === null;
    }

    const empty = scope.querySelector('[data-search-empty]');

    if (empty) {
        empty.hidden = visibleCount > 0;
    }
}

/**
 * Active la barre de recherche de la page, s'il y en a une.
 */
export function initPageSearch() {
    const input = document.querySelector('[data-page-search]');

    if (input) {
        new PageSearch(input);
    }
}
