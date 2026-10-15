import { apiPost } from '../../fonctions/api.js';
import { createElement } from '../../fonctions/createElement.js';
import { showNotice } from '../../composants/Notice.js';

/**
 * Modification directe des cartes de la page Projets (templates/app/project/_project_card.html.twig).
 *
 *   [data-card-edit="name"]       nom : un clic ouvre un champ (Entrée ou clic ailleurs = enregistré, Échap = annulé)
 *   [data-card-edit="summary"]    description : pareil, dans une zone de texte
 *   [data-card-edit="category"]   catégorie : un clic ouvre la liste des catégories
 *   [data-card-edit="state"]      statut : un clic ouvre la liste des statuts
 *   [data-card-image="logo"]      image : choisir un fichier l'enregistre, la croix la retire
 *
 * Chaque modification est envoyée à l'API de modification du projet (data-update-url), avec les autres
 * valeurs de la carte (data-name, data-summary, data-state, data-category). L'API renvoie la carte
 * à jour (HTML), qui remplace l'ancienne : l'événement CARD_REPLACED_EVENT prévient la liste (filtres).
 *
 * Les listes proposées viennent de la liste des projets : data-categories et data-states (JSON).
 */
export const CARD_REPLACED_EVENT = 'project-card:replaced';

const TEXT_FIELDS = {
    name: { tag: 'input', maxLength: 255, label: 'Nom du projet' },
    summary: { tag: 'textarea', maxLength: 255, label: 'Description' },
};

/** Nom du champ qui demande la suppression d'une image (voir App\Enum\ProjectImage::removeField()) */
const REMOVE_FIELDS = { logo: 'removeLogo', logoWide: 'removeLogoWide', avatar: 'removeAvatar' };

export class ProjectCardEditor {
    constructor(root) {
        this.root = root;
        this.categories = JSON.parse(root.dataset.categories ?? '[]');
        this.states = JSON.parse(root.dataset.states ?? '[]');
        // Champ de texte en cours de modification : { card, element, field, input, originalHtml }
        this.editing = null;
        // Liste de choix ouverte (catégorie ou statut)
        this.choices = null;

        this.handleClick = this.handleClick.bind(this);
        this.handleKeyDown = this.handleKeyDown.bind(this);
        this.handleChange = this.handleChange.bind(this);
        this.handleDocumentClick = this.handleDocumentClick.bind(this);
        this.handleFieldKeyDown = this.handleFieldKeyDown.bind(this);
        this.handleFieldBlur = this.handleFieldBlur.bind(this);
    }

    init() {
        this.root.addEventListener('click', this.handleClick);
        this.root.addEventListener('keydown', this.handleKeyDown);
        this.root.addEventListener('change', this.handleChange);
        document.addEventListener('click', this.handleDocumentClick);
    }

    handleClick(event) {
        const choice = event.target.closest('[data-card-choice]');

        if (choice) {
            this.chooseValue(choice);

            return;
        }

        const remove = event.target.closest('[data-card-image-remove]');

        if (remove) {
            this.removeImage(remove.closest('[data-card-image]'));

            return;
        }

        const editable = event.target.closest('[data-card-edit]');

        if (editable && !this.isEditing(editable)) {
            event.stopPropagation();
            this.startEdit(editable);
        }
    }

    /**
     * Au clavier : Entrée sur le nom ou la description ouvre le champ.
     */
    handleKeyDown(event) {
        const editable = event.target.closest('[data-card-edit]');

        if (event.key === 'Enter' && editable && event.target === editable && editable.tagName !== 'BUTTON') {
            event.preventDefault();
            this.startEdit(editable);
        }

        if (event.key === 'Escape') {
            this.closeChoices();
        }
    }

    handleChange(event) {
        const input = event.target.closest('[data-card-image-input]');
        const file = input?.files[0];

        if (file) {
            const image = input.closest('[data-card-image]');
            this.save(image.closest('[data-project-card]'), { [image.dataset.cardImage]: file });
        }
    }

    /**
     * Un clic en dehors de la liste de choix la ferme.
     */
    handleDocumentClick(event) {
        if (this.choices && !this.choices.element.contains(event.target)) {
            this.closeChoices();
        }
    }

    isEditing(element) {
        return this.editing?.element === element;
    }

    startEdit(element) {
        const field = element.dataset.cardEdit;

        this.closeChoices();

        if (TEXT_FIELDS[field]) {
            this.startTextEdit(element, field);
        } else {
            this.openChoices(element, field);
        }
    }

    // --- Nom et description ---------------------------------------------------

    startTextEdit(element, field) {
        const card = element.closest('[data-project-card]');
        const config = TEXT_FIELDS[field];
        const input = createElement(config.tag, {
            className: `project-card__field project-card__field--${field}`,
            attributes: { maxlength: String(config.maxLength), 'aria-label': config.label, rows: '3' },
        });

        input.value = card.dataset[field] ?? '';
        this.editing = { card, element, field, input, originalHtml: element.innerHTML };

        element.replaceChildren(input);
        input.addEventListener('keydown', this.handleFieldKeyDown);
        input.addEventListener('blur', this.handleFieldBlur);
        input.focus();
        input.select();
    }

    /**
     * Entrée enregistre (Maj + Entrée : retour à la ligne dans la description), Échap annule.
     */
    handleFieldKeyDown(event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            this.cancelTextEdit();
        } else if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            event.target.blur();
        }
    }

    handleFieldBlur() {
        const { card, field, input } = this.editing;
        const value = input.value.trim();

        if (value === (card.dataset[field] ?? '')) {
            this.cancelTextEdit();

            return;
        }

        if (field === 'name' && value === '') {
            showNotice('warning', 'Le nom du projet ne peut pas être vide.');
            this.cancelTextEdit();

            return;
        }

        this.finishTextEdit();
        this.save(card, { [field]: value });
    }

    cancelTextEdit() {
        if (!this.editing) {
            return;
        }

        const { element, originalHtml } = this.editing;

        this.finishTextEdit();
        element.innerHTML = originalHtml;
    }

    finishTextEdit() {
        const { input } = this.editing;

        input.removeEventListener('keydown', this.handleFieldKeyDown);
        input.removeEventListener('blur', this.handleFieldBlur);
        this.editing = null;
    }

    // --- Catégorie et statut : liste de choix --------------------------------

    openChoices(button, field) {
        const card = button.closest('[data-project-card]');
        const current = card.dataset[field] ?? '';
        const options = field === 'state'
            ? this.states.map((state) => ({ value: state.code, label: state.label }))
            : [{ value: '', label: 'Aucune catégorie' }, ...this.categories.map((category) => ({ value: String(category.id), label: category.name }))];

        const element = createElement('div', {
            className: 'project-card__choices',
            attributes: { role: 'menu' },
            children: options.map((option) => createElement('button', {
                className: `project-card__choice${option.value === current ? ' project-card__choice--current' : ''}`,
                text: option.label,
                attributes: { type: 'button', role: 'menuitem', 'data-card-choice': option.value },
            })),
        });

        // Sous le bouton, dans la carte (qui sert de repère pour la position)
        const buttonBox = button.getBoundingClientRect();
        const cardBox = card.getBoundingClientRect();
        element.style.top = `${buttonBox.bottom - cardBox.top + 6}px`;
        element.style[field === 'state' ? 'right' : 'left'] = field === 'state'
            ? `${cardBox.right - buttonBox.right}px`
            : `${buttonBox.left - cardBox.left}px`;

        card.append(element);
        this.choices = { card, field, element };
        element.querySelector('[data-card-choice]')?.focus();
    }

    chooseValue(choice) {
        const { card, field } = this.choices;
        const value = choice.dataset.cardChoice;

        this.closeChoices();

        if (value !== (card.dataset[field] ?? '')) {
            this.save(card, { [field]: value });
        }
    }

    closeChoices() {
        this.choices?.element.remove();
        this.choices = null;
    }

    // --- Images ---------------------------------------------------------------

    removeImage(image) {
        const card = image.closest('[data-project-card]');

        this.save(card, { [REMOVE_FIELDS[image.dataset.cardImage]]: '1' });
    }

    // --- Enregistrement -------------------------------------------------------

    /**
     * Envoie le projet entier (les valeurs de la carte + la modification), puis remplace la carte.
     *
     * @param {HTMLElement} card
     * @param {Object<string, string|File>} changes
     */
    async save(card, changes) {
        const values = {
            name: card.dataset.name ?? '',
            summary: card.dataset.summary ?? '',
            state: card.dataset.state ?? '',
            category: card.dataset.category ?? '',
            ...changes,
        };
        const data = new FormData();

        for (const [name, value] of Object.entries(values)) {
            data.append(name, value);
        }

        card.classList.add('project-card--saving');

        try {
            const result = await apiPost(card.dataset.updateUrl, data);
            this.replaceCard(card, result.card);
        } catch (error) {
            showNotice('error', `Modification impossible : ${error.message}`);
            this.replaceCard(card, null);
        }
    }

    /**
     * @param {string|null} html la carte renvoyée par l'API ; null : on garde l'ancienne (erreur)
     */
    replaceCard(card, html) {
        card.classList.remove('project-card--saving');

        if (!html) {
            // Remet l'affichage d'origine (image choisie, champ vidé…)
            card.querySelectorAll('[data-card-image-input]').forEach((input) => {
                input.value = '';
            });

            return;
        }

        const template = document.createElement('template');
        template.innerHTML = html.trim();
        const newCard = template.content.firstElementChild;

        card.replaceWith(newCard);
        this.root.dispatchEvent(new CustomEvent(CARD_REPLACED_EVENT, { bubbles: true, detail: { card: newCard } }));
    }
}

/**
 * Active la modification directe des cartes, sur la page Projets.
 */
export function initProjectCardEditor() {
    const root = document.querySelector('[data-project-list]');

    if (root) {
        new ProjectCardEditor(root).init();
    }
}
