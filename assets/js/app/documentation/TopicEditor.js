import { apiDelete, apiPatch, apiPost } from '../../fonctions/api.js';
import { showNotice } from '../../composants/Notice.js';

/**
 * Tutoriels de la documentation : ajout, modification (titre, résumé) et suppression.
 *
 * - formulaires [data-topic-form data-api-url data-method] dans la modal ;
 * - bouton [data-topic-delete="url de l'API"] dans l'en-tête de la page d'un tutoriel.
 *
 * Après chaque action, on va à l'adresse renvoyée par l'API (elle change si le titre change)
 * et le message de confirmation s'affiche en bandeau (message flash).
 */
export class TopicEditor {
    constructor() {
        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleClick = this.handleClick.bind(this);
    }

    init() {
        // Les formulaires sont dans la modal, le bouton dans l'en-tête : écoute sur document
        document.addEventListener('submit', this.handleSubmit);
        document.addEventListener('click', this.handleClick);
    }

    handleSubmit(event) {
        const form = event.target;

        if (!form.matches('[data-topic-form]')) {
            return;
        }

        event.preventDefault();
        this.submitForm(form);
    }

    async submitForm(form) {
        const data = {
            title: form.elements.title.value.trim(),
            summary: form.elements.summary.value.trim(),
        };
        const error = form.querySelector('[data-form-error]');
        const submitButton = form.querySelector('[type="submit"]');

        if (data.title === '') {
            error.textContent = 'Le titre est obligatoire.';
            form.elements.title.focus();

            return;
        }

        error.textContent = '';
        submitButton.disabled = true;

        try {
            const send = form.dataset.method === 'PATCH' ? apiPatch : apiPost;
            const result = await send(form.dataset.apiUrl, data);
            window.location.assign(result.url);
        } catch (apiError) {
            error.textContent = apiError.message;
            submitButton.disabled = false;
        }
    }

    handleClick(event) {
        const button = event.target.closest('[data-topic-delete]');

        if (button) {
            this.deleteTopic(button.dataset.topicDelete, button.dataset.topicTitle ?? '');
        }
    }

    async deleteTopic(url, title) {
        if (!window.confirm(`Supprimer le tutoriel « ${title} » et toutes ses sections ?`)) {
            return;
        }

        try {
            const result = await apiDelete(url);
            window.location.assign(result.url);
        } catch (error) {
            showNotice('error', `Suppression impossible : ${error.message}`);
        }
    }
}
