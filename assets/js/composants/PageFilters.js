/**
 * Filtres de l'en-tête des pages de l'app (templates/app/_partials/_page_header.html.twig, ligne 2).
 *
 * Chaque filtre est une tuile [data-page-filter="nom"] : un clic ouvre sa liste de choix
 * [data-filter-menu], un choix [data-filter-option="valeur"] la ferme et met à jour la tuile.
 * La valeur choisie est gardée dans data-value. Échap ou un clic ailleurs ferme la liste.
 *
 * À chaque changement, l'événement « page:filter » est envoyé sur document :
 *      event.detail = { name: 'state', value: 'en_cours', values: { state: 'en_cours', category: '' } }
 * values : la valeur de tous les filtres de la page. La page filtre sa liste elle-même.
 */
export const PAGE_FILTER_EVENT = 'page:filter';

const ACTIVE_CLASS = 'page-filter--active';
const OPEN_CLASS = 'page-filter--open';

export class PageFilters {
    constructor(filters) {
        this.filters = filters;
        this.openFilter = null;

        this.handleClick = this.handleClick.bind(this);
        this.handleDocumentClick = this.handleDocumentClick.bind(this);
        this.handleKeyDown = this.handleKeyDown.bind(this);

        for (const filter of filters) {
            filter.addEventListener('click', this.handleClick);
        }

        document.addEventListener('click', this.handleDocumentClick);
        document.addEventListener('keydown', this.handleKeyDown);
    }

    handleClick(event) {
        const filter = event.currentTarget;
        const option = event.target.closest('[data-filter-option]');

        if (option) {
            this.choose(filter, option);

            return;
        }

        if (event.target.closest('[data-filter-toggle]')) {
            this.isOpen(filter) ? this.close() : this.open(filter);
        }
    }

    /**
     * Un clic en dehors du filtre ouvert le ferme.
     */
    handleDocumentClick(event) {
        if (this.openFilter && !this.openFilter.contains(event.target)) {
            this.close();
        }
    }

    /**
     * Échap ferme la liste ; les flèches passent d'un choix à l'autre.
     */
    handleKeyDown(event) {
        if (!this.openFilter) {
            return;
        }

        if (event.key === 'Escape') {
            const toggle = this.openFilter.querySelector('[data-filter-toggle]');
            this.close();
            toggle.focus();
        } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            this.moveFocus(event.key === 'ArrowDown' ? 1 : -1);
        }
    }

    isOpen(filter) {
        return this.openFilter === filter;
    }

    open(filter) {
        this.close();
        this.openFilter = filter;
        filter.classList.add(OPEN_CLASS);
        filter.querySelector('[data-filter-menu]').hidden = false;
        filter.querySelector('[data-filter-toggle]').setAttribute('aria-expanded', 'true');
        filter.querySelector('[aria-selected="true"]')?.focus();
    }

    close() {
        if (!this.openFilter) {
            return;
        }

        this.openFilter.classList.remove(OPEN_CLASS);
        this.openFilter.querySelector('[data-filter-menu]').hidden = true;
        this.openFilter.querySelector('[data-filter-toggle]').setAttribute('aria-expanded', 'false');
        this.openFilter = null;
    }

    moveFocus(step) {
        const options = [...this.openFilter.querySelectorAll('[data-filter-option]')];
        const index = options.indexOf(document.activeElement);
        const next = options[(index + step + options.length) % options.length];

        next?.focus();
    }

    /**
     * Le choix est affiché dans la tuile (même largeur : le texte trop long se termine par « … »).
     */
    choose(filter, option) {
        const value = option.dataset.filterOption;

        for (const item of filter.querySelectorAll('[data-filter-option]')) {
            item.setAttribute('aria-selected', String(item === option));
        }

        // La valeur affichée : pastille de couleur éventuelle + texte du choix
        const display = filter.querySelector('[data-filter-value]');
        display.replaceChildren(...[...option.children].filter((child) => !child.matches('img')).map((child) => child.cloneNode(true)));

        filter.dataset.value = value;
        filter.classList.toggle(ACTIVE_CLASS, value !== filter.dataset.defaultValue);
        this.close();
        filter.querySelector('[data-filter-toggle]').focus();

        document.dispatchEvent(new CustomEvent(PAGE_FILTER_EVENT, {
            detail: { name: filter.dataset.pageFilter, value, values: this.getValues() },
        }));
    }

    /**
     * @returns {Object<string, string>} la valeur de chaque filtre, par nom
     */
    getValues() {
        const values = {};

        for (const filter of this.filters) {
            values[filter.dataset.pageFilter] = filter.dataset.value ?? '';
        }

        return values;
    }
}

/**
 * Active les filtres de l'en-tête, si la page en a.
 */
export function initPageFilters() {
    const filters = [...document.querySelectorAll('[data-page-filter]')];

    if (filters.length > 0) {
        new PageFilters(filters);
    }
}
