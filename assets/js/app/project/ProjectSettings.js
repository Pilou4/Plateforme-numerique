import { apiDelete, apiPatch, apiPost, apiPut } from '../../fonctions/api.js';
import { createElement } from '../../fonctions/createElement.js';
import { getModal } from '../../composants/Modal.js';

const MODAL_NAME = 'project-settings';
const CATEGORIES = 'categories';
const STATES = 'states';

/**
 * Textes propres à chaque liste.
 */
const KINDS = {
    [CATEGORIES]: {
        title: 'Catégories',
        icon: 'filter-category.svg',
        intro: 'Le type de projet : application web, site vitrine, jeu…',
        placeholder: 'Nouvelle catégorie',
        requiredMessage: 'Le nom de la catégorie est obligatoire.',
    },
    [STATES]: {
        title: 'Statuts',
        icon: 'filter-status.svg',
        intro: 'L\'avancement d\'un projet. Un nouveau projet prend le premier de la liste.',
        placeholder: 'Nouveau statut',
        requiredMessage: 'Le nom du statut est obligatoire.',
    },
};

/**
 * Catégories et statuts des projets, gérés dans la modal depuis la page Projets
 * (roue dentée de l'en-tête : [data-project-settings="categories|states"]).
 *
 * Une seule modal peut être ouverte : tout se fait dans la liste.
 *   - clic sur un nom : il devient un champ (Entrée enregistre, Échap annule) ;
 *   - statut : clic sur sa pastille → les couleurs ;
 *   - flèches : ordre ; corbeille : suppression (après confirmation) ;
 *   - champ en bas : ajout.
 * Chaque action passe par l'API (Api\ProjectCategoryController, Api\ProjectStateController).
 * À la fermeture de la modal, s'il y a eu un changement, la page est rechargée (filtres et cartes à jour).
 *
 * Données lues sur [data-project-settings-data] (templates/app/project/index.html.twig) :
 *   data-categories, data-states (JSON, avec le nombre de projets), data-colors (couleurs d'un statut),
 *   data-category-api, data-state-api, data-icons-url
 */
export class ProjectSettings {
    constructor(dataElement) {
        this.lists = {
            [CATEGORIES]: JSON.parse(dataElement.dataset.categories),
            [STATES]: JSON.parse(dataElement.dataset.states),
        };
        this.apiUrls = {
            [CATEGORIES]: dataElement.dataset.categoryApi,
            [STATES]: dataElement.dataset.stateApi,
        };
        this.colors = JSON.parse(dataElement.dataset.colors);
        this.iconsUrl = dataElement.dataset.iconsUrl;
        this.kind = null;
        this.editing = null;
        this.coloring = null;
        this.error = '';
        this.busy = false;
        this.changed = false;
        this.content = null;

        this.handleOpenClick = this.handleOpenClick.bind(this);
        this.handleModalClose = this.handleModalClose.bind(this);
        this.handleClick = this.handleClick.bind(this);
        this.handleKeyDown = this.handleKeyDown.bind(this);
        this.handleSubmit = this.handleSubmit.bind(this);
    }

    init() {
        document.addEventListener('click', this.handleOpenClick);
        document.addEventListener('modal:close', this.handleModalClose);
    }

    handleOpenClick(event) {
        const button = event.target.closest('[data-project-settings]');

        if (button && KINDS[button.dataset.projectSettings]) {
            this.open(button.dataset.projectSettings);
        }
    }

    open(kind) {
        this.kind = kind;
        this.editing = null;
        this.coloring = null;
        this.error = '';

        this.content = createElement('div', { className: 'project-settings' });
        this.content.addEventListener('click', this.handleClick);
        this.content.addEventListener('keydown', this.handleKeyDown);
        this.content.addEventListener('submit', this.handleSubmit);
        this.render();

        getModal().open({
            title: KINDS[kind].title,
            content: this.content,
            size: 'medium',
            name: MODAL_NAME,
            icon: this.iconsUrl + KINDS[kind].icon,
        });

        this.content.querySelector('[data-settings-new]')?.focus();
    }

    /**
     * Fermeture de la modal : la page est rechargée si une liste a changé (filtres, cartes, formulaires).
     */
    handleModalClose(event) {
        if (event.detail.name !== MODAL_NAME) {
            return;
        }

        this.content = null;

        if (this.changed) {
            window.location.reload();
        }
    }

    get items() {
        return this.lists[this.kind];
    }

    /* ------------------------------------------------------------------
       Affichage
       ------------------------------------------------------------------ */

    render() {
        const rows = this.items.flatMap((item, index) => this.renderRow(item, index));

        this.content.replaceChildren(
            createElement('p', { className: 'project-settings__intro', text: KINDS[this.kind].intro }),
            rows.length > 0
                ? createElement('ul', { className: 'project-settings__list', children: rows })
                : createElement('p', { className: 'project-settings__empty', text: 'La liste est vide pour l\'instant.' }),
            createElement('form', {
                className: 'project-settings__add',
                attributes: { novalidate: '' },
                children: [
                    createElement('input', {
                        className: 'project-settings__new',
                        attributes: { type: 'text', maxlength: '50', placeholder: KINDS[this.kind].placeholder, 'aria-label': KINDS[this.kind].placeholder, 'data-settings-new': '' },
                    }),
                    createElement('button', { className: 'projects__button', text: 'Ajouter', attributes: { type: 'submit' } }),
                ],
            }),
            createElement('p', { className: 'project-settings__error', text: this.error, attributes: { role: 'alert' } }),
            createElement('div', {
                className: 'modal__actions',
                children: [createElement('button', { className: 'projects__button projects__button--ghost', text: 'Fermer', attributes: { type: 'button', 'data-modal-close': '' } })],
            }),
        );
    }

    /**
     * Une ligne (et, pour un statut dont on change la couleur, la ligne des couleurs dessous).
     *
     * @returns {HTMLElement[]}
     */
    renderRow(item, index) {
        const name = this.kind === STATES ? item.label : item.name;
        const isEditing = this.editing === index;
        const children = [];

        if (this.kind === STATES) {
            children.push(this.renderStatePill(item, index));
        }

        if (isEditing) {
            children.push(...this.renderEditFields(item));
        } else if (this.kind === STATES) {
            children.push(this.actionButton('Renommer', 'project-settings__rename', { 'data-action': 'edit', 'data-index': index }, `Renommer « ${name} »`));
        } else {
            children.push(this.actionButton(name, 'project-settings__name', { 'data-action': 'edit', 'data-index': index, title: 'Cliquer pour renommer' }, `Renommer « ${name} »`));
        }

        children.push(
            createElement('span', { className: 'project-settings__meta', text: projectCountText(item.projectCount) }),
            this.iconButton('arrow-up.svg', `Monter « ${name} »`, { 'data-action': 'move', 'data-direction': 'up', 'data-index': index }, index === 0),
            this.iconButton('arrow-down.svg', `Descendre « ${name} »`, { 'data-action': 'move', 'data-direction': 'down', 'data-index': index }, index === this.items.length - 1),
            this.iconButton('delete.svg', `Supprimer « ${name} »`, { 'data-action': 'delete', 'data-index': index }, false, 'project-settings__icon--delete'),
        );

        const rows = [createElement('li', { className: `project-settings__row${isEditing ? ' project-settings__row--editing' : ''}`, children })];

        if (this.coloring === index) {
            rows.push(createElement('li', {
                className: 'project-settings__colors',
                attributes: { role: 'group', 'aria-label': `Couleur de « ${name} »` },
                children: this.colors.map((color) => createElement('button', {
                    className: `project-settings__pill project-state--${color.modifier}`,
                    attributes: {
                        type: 'button',
                        'aria-pressed': color.value === item.color ? 'true' : 'false',
                        'data-action': 'set-color',
                        'data-color': color.value,
                        'data-index': index,
                    },
                    children: [createElement('span', { className: 'project-settings__dot', attributes: { 'aria-hidden': 'true' } }), document.createTextNode(color.label)],
                })),
            }));
        }

        return rows;
    }

    renderStatePill(item, index) {
        const color = this.colors.find((candidate) => candidate.value === item.color) ?? this.colors[0];

        return createElement('button', {
            className: `project-settings__pill project-state--${color.modifier}`,
            attributes: {
                type: 'button',
                title: 'Changer la couleur',
                'aria-label': `Couleur de « ${item.label} » : ${color.label} (changer)`,
                'aria-expanded': this.coloring === index ? 'true' : 'false',
                'data-action': 'colors',
                'data-index': index,
            },
            children: [createElement('span', { className: 'project-settings__dot', attributes: { 'aria-hidden': 'true' } }), document.createTextNode(item.label)],
        });
    }

    /**
     * Champs du renommage : le nom (et, pour un statut, le nom au pluriel utilisé par les filtres).
     */
    renderEditFields(item) {
        if (this.kind === CATEGORIES) {
            return [this.editInput('name', item.name, 'Nom de la catégorie')];
        }

        return [
            this.editInput('label', item.label, 'Nom du statut'),
            this.editInput('pluralLabel', item.pluralLabel === item.label ? '' : item.pluralLabel, 'Au pluriel (facultatif)'),
        ];
    }

    editInput(field, value, label) {
        const input = createElement('input', {
            className: 'project-settings__input',
            attributes: { type: 'text', maxlength: '50', placeholder: label, 'aria-label': label, title: 'Entrée : enregistrer · Échap : annuler', 'data-edit-field': field },
        });
        input.value = value;

        return input;
    }

    actionButton(text, className, attributes, label) {
        return createElement('button', { className, text, attributes: { type: 'button', 'aria-label': label, ...attributes } });
    }

    iconButton(icon, label, attributes, disabled, modifier = '') {
        const button = createElement('button', {
            className: `project-settings__icon ${modifier}`.trim(),
            attributes: { type: 'button', title: label, 'aria-label': label, ...attributes },
            children: [createElement('img', { attributes: { src: this.iconsUrl + icon, alt: '', width: '16', height: '16' } })],
        });
        button.disabled = disabled;

        return button;
    }

    /* ------------------------------------------------------------------
       Actions
       ------------------------------------------------------------------ */

    handleClick(event) {
        const button = event.target.closest('[data-action]');

        if (!button || button.disabled || this.busy) {
            return;
        }

        const index = Number(button.dataset.index);

        switch (button.dataset.action) {
            case 'edit':
                this.startEditing(index);
                break;
            case 'colors':
                this.coloring = this.coloring === index ? null : index;
                this.editing = null;
                this.renderAndFocus(`[data-action="${this.coloring === null ? 'colors' : 'set-color'}"][data-index="${index}"]${this.coloring === null ? '' : '[aria-pressed="true"]'}`);
                break;
            case 'set-color':
                this.saveState(index, { color: button.dataset.color });
                break;
            case 'move':
                this.move(index, button.dataset.direction);
                break;
            case 'delete':
                this.delete(index);
                break;
        }
    }

    /**
     * Entrée enregistre le renommage, Échap l'annule (sans fermer la modal).
     */
    handleKeyDown(event) {
        if (!event.target.matches('[data-edit-field]')) {
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            this.saveEditing();
        } else if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            const index = this.editing;
            this.editing = null;
            this.renderAndFocus(`[data-action="edit"][data-index="${index}"]`);
        }
    }

    handleSubmit(event) {
        event.preventDefault();
        this.add(this.content.querySelector('[data-settings-new]'));
    }

    startEditing(index) {
        this.editing = index;
        this.coloring = null;
        this.error = '';
        this.renderAndFocus('[data-edit-field]');
        this.content.querySelector('[data-edit-field]')?.select();
    }

    saveEditing() {
        const fields = Object.fromEntries([...this.content.querySelectorAll('[data-edit-field]')].map((input) => [input.dataset.editField, input.value.trim()]));

        if (this.kind === CATEGORIES) {
            this.saveCategory(this.editing, fields.name);
        } else {
            this.saveState(this.editing, { label: fields.label, pluralLabel: fields.pluralLabel });
        }
    }

    async saveCategory(index, name) {
        if (name === '') {
            this.showError(KINDS[CATEGORIES].requiredMessage);

            return;
        }

        const item = this.items[index];

        await this.call(async () => {
            const saved = await apiPatch(this.itemUrl(item), { name });
            item.name = saved.name;
            this.editing = null;
        }, `[data-action="edit"][data-index="${index}"]`);
    }

    /**
     * Statut : nom, nom au pluriel et couleur sont toujours envoyés ensemble (l'API attend les trois).
     */
    async saveState(index, changes) {
        const item = this.items[index];
        const data = {
            label: item.label,
            pluralLabel: item.pluralLabel === item.label ? '' : item.pluralLabel,
            color: item.color,
            ...changes,
        };

        if (data.label.trim() === '') {
            this.showError(KINDS[STATES].requiredMessage);

            return;
        }

        await this.call(async () => {
            const saved = await apiPatch(this.itemUrl(item), data);
            Object.assign(item, { label: saved.label, pluralLabel: saved.pluralLabel, color: saved.color });
            this.editing = null;
            this.coloring = null;
        }, `[data-action="colors"][data-index="${index}"], [data-action="edit"][data-index="${index}"]`);
    }

    async add(input) {
        const name = input.value.trim();

        if (name === '') {
            this.showError(KINDS[this.kind].requiredMessage);
            input.focus();

            return;
        }

        // Un nouveau statut prend la première couleur ; elle se change ensuite avec sa pastille
        const data = this.kind === CATEGORIES ? { name } : { label: name, pluralLabel: '', color: this.colors[0].value };

        await this.call(async () => {
            const saved = await apiPost(this.apiUrls[this.kind], data);
            this.items.push({ ...saved, projectCount: 0 });
        }, '[data-settings-new]');
    }

    async move(index, direction) {
        const target = direction === 'up' ? index - 1 : index + 1;

        await this.call(async () => {
            await apiPut(`${this.itemUrl(this.items[index])}/position`, { direction });
            [this.items[index], this.items[target]] = [this.items[target], this.items[index]];
        }, `[data-action="move"][data-direction="${direction}"][data-index="${target}"]`);
    }

    async delete(index) {
        const item = this.items[index];

        // Statut encore utilisé : le serveur refuserait la suppression
        if (this.kind === STATES && item.projectCount > 0) {
            this.showError(usedStateMessage(item));

            return;
        }

        if (!window.confirm(deleteQuestion(this.kind, item))) {
            return;
        }

        await this.call(async () => {
            await apiDelete(this.itemUrl(item));
            this.items.splice(index, 1);
            this.editing = null;
            this.coloring = null;
        }, '[data-settings-new]');
    }

    /**
     * Appelle l'API (une action à la fois), puis redessine la liste et remet le focus ; en cas d'erreur,
     * la liste reste telle quelle et le message s'affiche sous la liste.
     */
    async call(action, focusSelector) {
        this.busy = true;
        this.error = '';

        try {
            await action();
            this.changed = true;
            this.renderAndFocus(focusSelector);
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.busy = false;
        }
    }

    itemUrl(item) {
        return `${this.apiUrls[this.kind]}/${item.id}`;
    }

    showError(message) {
        this.error = message;
        const element = this.content?.querySelector('.project-settings__error');

        if (element) {
            element.textContent = message;
        }
    }

    renderAndFocus(selector) {
        if (!this.content) {
            return;
        }

        this.render();
        this.content.querySelector(selector)?.focus();
    }
}

function projectCountText(count) {
    if (count === 0) {
        return 'Aucun projet';
    }

    return count === 1 ? '1 projet' : `${count} projets`;
}

function deleteQuestion(kind, item) {
    if (kind === STATES) {
        return `Supprimer le statut « ${item.label} » ?`;
    }

    const question = `Supprimer la catégorie « ${item.name} » ?`;

    if (item.projectCount === 0) {
        return question;
    }

    const projects = item.projectCount === 1 ? '1 projet n\'aura plus de catégorie.' : `${item.projectCount} projets n'auront plus de catégorie.`;

    return `${question}\n\n${projects}`;
}

function usedStateMessage(item) {
    const projects = item.projectCount === 1 ? '1 projet' : `${item.projectCount} projets`;
    const their = item.projectCount === 1 ? 'son' : 'leur';

    return `« ${item.label} » est utilisé par ${projects} : change d'abord ${their} statut.`;
}

/**
 * Active la gestion des catégories et des statuts (page Projets uniquement).
 */
export function initProjectSettings() {
    const dataElement = document.querySelector('[data-project-settings-data]');

    if (dataElement) {
        new ProjectSettings(dataElement).init();
    }
}
