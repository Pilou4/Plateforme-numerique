import { apiDelete, apiPatch, apiPost, apiPut } from '../fonctions/api.js';
import { openModalFromTemplate } from './Modal.js';
import { showNotice } from './Notice.js';

const EDIT_FORM_TEMPLATE_ID = 'section-edit';
const MODE_ADD = 'add';
const MODE_EDIT = 'edit';

/**
 * Sections modifiables d'une page (tutoriel de la documentation, présentation d'un projet) :
 * ajout, modification, suppression, monter / descendre.
 *
 * HTML attendu (voir templates/app/_partials/_section_tools.html.twig et _section_forms.html.twig) :
 *   [data-section-editor data-api-url="…/sections"]       conteneur des sections
 *     [data-section data-section-id data-section-title data-section-content]
 *       [data-section-action="up|down|edit|delete"]
 *   <template id="section-add"> et <template id="section-edit"> : formulaires [data-section-form]
 *
 * La page est affichée par Twig : après chaque action réussie, on va à l'adresse renvoyée
 * par l'API (la même page) et le message de confirmation s'affiche en bandeau (message flash).
 */
export class SectionEditor {
    constructor(root) {
        this.root = root;
        this.apiUrl = root.dataset.apiUrl;
        // Section en cours de modification dans la modal
        this.editedSection = null;

        this.handleClick = this.handleClick.bind(this);
        this.handleSubmit = this.handleSubmit.bind(this);
    }

    init() {
        this.root.addEventListener('click', this.handleClick);
        // Les formulaires sont dans la modal, en dehors de la page : écoute sur document
        document.addEventListener('submit', this.handleSubmit);
    }

    handleClick(event) {
        const button = event.target.closest('[data-section-action]');
        const element = button?.closest('[data-section]');

        if (!element || button.disabled) {
            return;
        }

        const section = readSection(element);

        switch (button.dataset.sectionAction) {
            case 'up':
            case 'down':
                this.move(section, button.dataset.sectionAction);
                break;
            case 'edit':
                this.openEditForm(section);
                break;
            case 'delete':
                this.delete(section);
                break;
        }
    }

    openEditForm(section) {
        const modal = openModalFromTemplate(EDIT_FORM_TEMPLATE_ID, { title: `Modifier « ${section.title} »`, size: 'large' });
        const form = modal?.getBody().querySelector('[data-section-form]');

        if (!form) {
            return;
        }

        this.editedSection = section;
        form.elements.title.value = section.title;
        form.elements.content.value = section.content;
    }

    handleSubmit(event) {
        const form = event.target;

        if (!form.matches('[data-section-form]')) {
            return;
        }

        event.preventDefault();
        this.submitForm(form);
    }

    async submitForm(form) {
        const data = {
            title: form.elements.title.value.trim(),
            content: form.elements.content.value.trim(),
        };
        const error = form.querySelector('[data-form-error]');
        const submitButton = form.querySelector('[type="submit"]');

        if (data.title === '') {
            showFormError(error, 'Le titre est obligatoire.', form.elements.title);

            return;
        }

        if (data.content === '') {
            showFormError(error, 'Le contenu est obligatoire.', form.elements.content);

            return;
        }

        error.textContent = '';
        submitButton.disabled = true;

        try {
            if (form.dataset.sectionForm === MODE_EDIT && this.editedSection) {
                const result = await apiPatch(this.getSectionUrl(this.editedSection.id), data);
                goTo(result.url, this.editedSection.id);
            } else if (form.dataset.sectionForm === MODE_ADD) {
                const result = await apiPost(this.apiUrl, data);
                goTo(result.url);
            }
        } catch (apiError) {
            showFormError(error, apiError.message);
            submitButton.disabled = false;
        }
    }

    async move(section, direction) {
        try {
            const result = await apiPut(`${this.getSectionUrl(section.id)}/position`, { direction });
            goTo(result.url, section.id);
        } catch (error) {
            showNotice('error', `Déplacement impossible : ${error.message}`);
        }
    }

    async delete(section) {
        if (!window.confirm(`Supprimer la section « ${section.title} » ?`)) {
            return;
        }

        try {
            const result = await apiDelete(this.getSectionUrl(section.id));
            goTo(result.url);
        } catch (error) {
            showNotice('error', `Suppression impossible : ${error.message}`);
        }
    }

    getSectionUrl(id) {
        return `${this.apiUrl}/${id}`;
    }
}

/**
 * Les données d'une section, lues sur son élément (data-section-id, -title, -content).
 */
function readSection(element) {
    return {
        id: Number(element.dataset.sectionId),
        title: element.dataset.sectionTitle ?? '',
        content: element.dataset.sectionContent ?? '',
    };
}

/**
 * Recharge la page à l'adresse donnée, en revenant sur la section concernée s'il y en a une.
 */
function goTo(url, sectionId = null) {
    const target = new URL(url, window.location.href);
    target.hash = sectionId ? `section-${sectionId}` : '';

    if (target.pathname !== window.location.pathname) {
        window.location.assign(target.href);

        return;
    }

    // Même page : changer seulement le « #… » ne recharge pas, on remplace l'adresse puis on recharge
    window.history.replaceState(null, '', target.href);
    window.location.reload();
}

function showFormError(element, message, field = null) {
    element.textContent = message;
    field?.focus();
}

/**
 * Active l'édition des sections de la page, s'il y en a.
 */
export function initSectionEditor() {
    const root = document.querySelector('[data-section-editor]');

    if (root) {
        new SectionEditor(root).init();
    }
}
