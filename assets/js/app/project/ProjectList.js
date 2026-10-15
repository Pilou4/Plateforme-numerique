import { apiDelete, apiPut } from '../../fonctions/api.js';
import { showNotice } from '../../composants/Notice.js';
import { PAGE_FILTER_EVENT } from '../../composants/PageFilters.js';
import { matchesSearch, PAGE_SEARCH_EVENT } from '../../composants/PageSearch.js';
import { CARD_REPLACED_EVENT } from './ProjectCardEditor.js';

/** Valeur du filtre « Favoris » qui ne garde que les favoris (les autres filtres : '' = tous) */
const FAVORITES_ONLY = 'oui';
const STAR_ICON_ON = 'iconOn';
const STAR_ICON_OFF = 'iconOff';

/**
 * Liste des projets (templates/app/project/index.html.twig) :
 * filtres de l'en-tête (Statut, Catégorie, Favoris : événement page:filter), recherche, étoile « favori » de chaque carte
 * et suppression d'un projet (corbeille de la carte → bouton [data-project-delete="url de l'API"]).
 * Une carte modifiée directement est remplacée (ProjectCardEditor.js) : les filtres sont réappliqués.
 *
 * Chaque carte porte :
 *      data-project-card
 *      data-state="en_cours"           code de l'état (table project_state)
 *      data-category="3"               identifiant de la catégorie ('' : aucune)
 *      data-favorite="true|false"
 *      data-search-text="…"            texte cherché par la barre de recherche
 */
export class ProjectList {
    constructor(root) {
        this.root = root;
        this.empty = root.querySelector('[data-projects-empty]');
        // Valeur des filtres de l'en-tête ('' = pas de filtre)
        this.filters = { state: '', category: '', favorite: '' };
        this.query = '';

        this.handleFilter = this.handleFilter.bind(this);
        this.handleSearch = this.handleSearch.bind(this);
        this.handleFavoriteClick = this.handleFavoriteClick.bind(this);
        this.handleDeleteClick = this.handleDeleteClick.bind(this);
        this.render = this.render.bind(this);

        root.addEventListener('click', this.handleFavoriteClick);
        root.addEventListener('click', this.handleDeleteClick);
        root.addEventListener(CARD_REPLACED_EVENT, this.render);
        document.addEventListener(PAGE_SEARCH_EVENT, this.handleSearch);
        document.addEventListener(PAGE_FILTER_EVENT, this.handleFilter);
    }

    handleFilter(event) {
        this.filters = { ...this.filters, ...event.detail.values };
        this.render();
    }

    handleSearch(event) {
        this.query = event.detail.normalizedQuery;
        this.render();
    }

    /**
     * Affiche les cartes qui correspondent à tous les filtres ET à la recherche.
     */
    render() {
        let visibleCount = 0;

        for (const card of this.root.querySelectorAll('[data-project-card]')) {
            const isVisible = this.matchesFilter(card) && matchesSearch(card.dataset.searchText, this.query);

            card.closest('[data-project-item]').hidden = !isVisible;
            visibleCount += isVisible ? 1 : 0;
        }

        if (this.empty) {
            this.empty.hidden = visibleCount > 0;
        }
    }

    matchesFilter(card) {
        const { state, category, favorite } = this.filters;

        return (state === '' || card.dataset.state === state)
            && (category === '' || card.dataset.category === category)
            && (favorite !== FAVORITES_ONLY || card.dataset.favorite === 'true');
    }

    async handleFavoriteClick(event) {
        const button = event.target.closest('[data-favorite-toggle]');

        if (!button) {
            return;
        }

        const card = button.closest('[data-project-card]');
        const favorite = card.dataset.favorite !== 'true';

        button.disabled = true;

        try {
            const result = await apiPut(button.dataset.apiUrl, { favorite });
            this.showFavorite(card, button, result.favorite);
        } catch (error) {
            showNotice('error', error.message);
        } finally {
            button.disabled = false;
        }
    }

    handleDeleteClick(event) {
        const button = event.target.closest('[data-project-delete]');

        if (button) {
            this.deleteProject(button);
        }
    }

    /**
     * Après confirmation, supprime le projet puis recharge la liste :
     * le message « Le projet … a été supprimé. » s'affiche alors en bandeau (message flash).
     */
    async deleteProject(button) {
        const name = button.dataset.projectName;
        const message = `Supprimer le projet « ${name} » ?\n\n`
            + 'Sa présentation, ses tâches, ses sous-tâches et ses fichiers seront définitivement effacés.';

        if (!window.confirm(message)) {
            return;
        }

        button.disabled = true;

        try {
            const result = await apiDelete(button.dataset.projectDelete);
            window.location.assign(result.url);
        } catch (error) {
            showNotice('error', `Suppression impossible : ${error.message}`);
            button.disabled = false;
        }
    }

    showFavorite(card, button, favorite) {
        const name = button.dataset.projectName;

        card.dataset.favorite = String(favorite);
        button.setAttribute('aria-pressed', String(favorite));
        button.setAttribute('aria-label', favorite ? `Retirer « ${name} » des favoris` : `Ajouter « ${name} » aux favoris`);
        button.querySelector('img').src = button.dataset[favorite ? STAR_ICON_ON : STAR_ICON_OFF];

        // Avec le filtre « Favoris », un projet retiré disparaît de la liste
        this.render();
    }
}

export function initProjectList() {
    const root = document.querySelector('[data-project-list]');

    if (root) {
        new ProjectList(root);
    }
}
