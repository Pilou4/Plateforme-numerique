import { apiDelete, apiPatch, apiPost, apiPut } from '../../fonctions/api.js';
import { openModalFromTemplate } from '../../composants/Modal.js';
import { showNotice } from '../../composants/Notice.js';

const MODE_ADD = 'add';
const TYPE_CATEGORY = 'category';
const TYPE_STATE = 'state';

/**
 * Textes et modèles de formulaire propres à chaque liste de la page.
 */
const TYPES = {
    [TYPE_CATEGORY]: {
        editTemplateId: 'settings-category-edit',
        editTitle: (name) => `Modifier la catégorie « ${name} »`,
        requiredMessage: 'Le nom de la catégorie est obligatoire.',
    },
    [TYPE_STATE]: {
        editTemplateId: 'settings-state-edit',
        editTitle: (name) => `Modifier le statut « ${name} »`,
        requiredMessage: 'Le nom du statut est obligatoire.',
    },
};

/**
 * Page « Paramètres » : ajout, modification, suppression et ordre (monter / descendre)
 * des catégories et des statuts de projet.
 *
 * Chaque ligne porte ses données : data-settings-item="category|state", data-url (adresse de l'API
 * pour la modifier ou la supprimer), data-name, data-project-count, et pour un statut
 * data-plural-label et data-color.
 *
 * Après chaque action réussie, la page est rechargée : le serveur affiche le message
 * de confirmation (message flash → bandeau).
 */
export class SettingsEditor {
    constructor(root) {
        this.root = root;

        this.handleClick = this.handleClick.bind(this);
        this.handleSubmit = this.handleSubmit.bind(this);
    }

    init() {
        this.root.addEventListener('click', this.handleClick);
        // Les formulaires sont dans la modal, en dehors de la page : écoute sur document
        document.addEventListener('submit', this.handleSubmit);
    }

    handleClick(event) {
        const button = event.target.closest('[data-action]');
        const row = button?.closest('[data-settings-item]');

        if (!row || button.disabled) {
            return;
        }

        const item = readItem(row);

        switch (button.dataset.action) {
            case 'edit':
                this.openEditForm(item);
                break;
            case 'delete':
                this.deleteItem(item);
                break;
            case 'up':
            case 'down':
                this.moveItem(item, button.dataset.action);
                break;
        }
    }

    /**
     * Monte ou descend d'un cran, puis recharge la page (le nouvel ordre se voit, pas de message).
     */
    async moveItem(item, direction) {
        try {
            await apiPut(`${item.url}/position`, { direction });
            window.location.reload();
        } catch (error) {
            showNotice('error', `Déplacement impossible : ${error.message}`);
        }
    }

    openEditForm(item) {
        const type = TYPES[item.type];
        const modal = openModalFromTemplate(type.editTemplateId, { title: type.editTitle(item.name), size: 'small' });
        const form = modal?.getBody().querySelector('[data-settings-form]');

        if (!form) {
            return;
        }

        form.dataset.apiUrl = item.url;

        if (item.type === TYPE_CATEGORY) {
            form.elements.name.value = item.name;
        } else {
            form.elements.label.value = item.name;
            form.elements.pluralLabel.value = item.pluralLabel === item.name ? '' : item.pluralLabel;
            checkColor(form, item.color);
        }
    }

    handleSubmit(event) {
        const form = event.target;

        if (!form.matches('[data-settings-form]')) {
            return;
        }

        event.preventDefault();
        this.submitForm(form);
    }

    async submitForm(form) {
        const type = form.dataset.settingsForm;
        const data = readFormData(form, type);
        const requiredField = type === TYPE_CATEGORY ? form.elements.name : form.elements.label;
        const error = form.querySelector('[data-form-error]');
        const submitButton = form.querySelector('[type="submit"]');

        if (requiredField.value.trim() === '') {
            error.textContent = TYPES[type].requiredMessage;
            requiredField.focus();

            return;
        }

        error.textContent = '';
        submitButton.disabled = true;

        try {
            const send = form.dataset.mode === MODE_ADD ? apiPost : apiPatch;
            await send(form.dataset.apiUrl, data);
            window.location.reload();
        } catch (apiError) {
            error.textContent = apiError.message;
            submitButton.disabled = false;
        }
    }

    async deleteItem(item) {
        // Statut encore utilisé : le serveur refuserait la suppression, inutile de demander confirmation
        if (item.type === TYPE_STATE && item.projectCount > 0) {
            showNotice('warning', usedStateMessage(item));

            return;
        }

        if (!window.confirm(deleteQuestion(item))) {
            return;
        }

        try {
            await apiDelete(item.url);
            window.location.reload();
        } catch (error) {
            showNotice('error', `Suppression impossible : ${error.message}`);
        }
    }
}

/**
 * Les données d'une ligne, lues sur ses attributs data-*.
 */
function readItem(row) {
    return {
        type: row.dataset.settingsItem,
        url: row.dataset.url,
        name: row.dataset.name ?? '',
        pluralLabel: row.dataset.pluralLabel ?? '',
        color: row.dataset.color ?? '',
        projectCount: Number(row.dataset.projectCount ?? 0),
    };
}

function readFormData(form, type) {
    if (type === TYPE_CATEGORY) {
        return { name: form.elements.name.value.trim() };
    }

    return {
        label: form.elements.label.value.trim(),
        pluralLabel: form.elements.pluralLabel.value.trim(),
        color: form.elements.color.value,
    };
}

function checkColor(form, color) {
    const radio = form.querySelector(`[name="color"][value="${CSS.escape(color)}"]`);

    if (radio) {
        radio.checked = true;
    }
}

function deleteQuestion(item) {
    if (item.type === TYPE_STATE) {
        return `Supprimer le statut « ${item.name} » ?`;
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

    return `« ${item.name} » est utilisé par ${projects} : change d'abord ${their} statut.`;
}
