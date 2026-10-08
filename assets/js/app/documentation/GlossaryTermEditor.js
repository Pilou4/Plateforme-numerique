import { apiDelete, apiPatch, apiPost } from '../../fonctions/api.js';
import { openModalFromTemplate } from '../../composants/Modal.js';
import { showNotice } from '../../composants/Notice.js';

const EDIT_FORM_TEMPLATE_ID = 'glossary-term-edit';
const MODE_ADD = 'add';
const MODE_EDIT = 'edit';

/**
 * Glossaire : ajout, modification et suppression des termes.
 *
 * Les termes sont affichés et classés par lettre côté serveur (Twig) :
 * après chaque action réussie, la page est rechargée et le serveur affiche
 * le message de confirmation (message flash → bandeau).
 */
export class GlossaryTermEditor {
    constructor(root) {
        this.root = root;
        this.apiUrl = root.dataset.apiUrl;
        // Terme en cours de modification dans la modal
        this.editedTerm = null;

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
        const entry = button?.closest('[data-glossary-term]');

        if (!entry) {
            return;
        }

        const term = readTerm(entry);

        if (button.dataset.action === 'edit') {
            this.openEditForm(term);
        } else if (button.dataset.action === 'delete') {
            this.deleteTerm(term);
        }
    }

    openEditForm(term) {
        const modal = openModalFromTemplate(EDIT_FORM_TEMPLATE_ID, { title: `Modifier « ${term.term} »` });
        const form = modal?.getBody().querySelector('[data-glossary-form]');

        if (!form) {
            return;
        }

        this.editedTerm = term;
        form.elements.term.value = term.term;
        form.elements.definition.value = term.definition;
    }

    handleSubmit(event) {
        const form = event.target;

        if (!form.matches('[data-glossary-form]')) {
            return;
        }

        event.preventDefault();
        this.submitForm(form);
    }

    async submitForm(form) {
        const data = {
            term: form.elements.term.value.trim(),
            definition: form.elements.definition.value.trim(),
        };
        const error = form.querySelector('[data-form-error]');
        const submitButton = form.querySelector('[type="submit"]');

        if (data.term === '') {
            showFormError(error, 'Le terme est obligatoire.', form.elements.term);

            return;
        }

        if (data.definition === '') {
            showFormError(error, 'La définition est obligatoire.', form.elements.definition);

            return;
        }

        error.textContent = '';
        submitButton.disabled = true;

        try {
            if (form.dataset.glossaryForm === MODE_EDIT && this.editedTerm) {
                await apiPatch(this.getTermUrl(this.editedTerm.id), data);
            } else if (form.dataset.glossaryForm === MODE_ADD) {
                await apiPost(this.apiUrl, data);
            }

            window.location.reload();
        } catch (apiError) {
            showFormError(error, apiError.message);
            submitButton.disabled = false;
        }
    }

    async deleteTerm(term) {
        if (!window.confirm(`Supprimer « ${term.term} » du glossaire ?`)) {
            return;
        }

        try {
            await apiDelete(this.getTermUrl(term.id));
            window.location.reload();
        } catch (error) {
            showNotice('error', `Suppression impossible : ${error.message}`);
        }
    }

    getTermUrl(id) {
        return `${this.apiUrl}/${id}`;
    }
}

/**
 * Les données d'un terme, lues sur sa ligne (data-id, data-term, data-definition).
 */
function readTerm(entry) {
    return {
        id: Number(entry.dataset.id),
        term: entry.dataset.term ?? '',
        definition: entry.dataset.definition ?? '',
    };
}

function showFormError(element, message, field = null) {
    element.textContent = message;
    field?.focus();
}
