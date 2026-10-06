// Point d'entrée des pages « Projets » (liste) et « Projet » (présentation).
// Gère les formulaires d'ajout et de modification d'un projet, affichés dans la modal.
import { apiPost } from '../../fonctions/api.js';

/**
 * Envoi d'un formulaire de projet (création ou modification).
 * Le formulaire peut contenir un fichier (le logo) : il est envoyé en FormData.
 */
async function handleProjectFormSubmit(event) {
    const form = event.target;

    if (!form.matches('[data-project-form]')) {
        return;
    }

    event.preventDefault();

    const error = form.querySelector('[data-form-error]');
    const submitButton = form.querySelector('[type="submit"]');

    if (form.elements.name.value.trim() === '') {
        error.textContent = 'Le nom du projet est obligatoire.';
        form.elements.name.focus();

        return;
    }

    error.textContent = '';
    submitButton.disabled = true;

    try {
        const project = await apiPost(form.dataset.apiUrl, new FormData(form));
        showSavedProject(form, project);
    } catch (apiError) {
        error.textContent = apiError.message;
        submitButton.disabled = false;
    }
}

/**
 * Sur la page d'un projet, on va à sa nouvelle adresse (elle change si le nom a changé).
 * Sur la liste, on la recharge : elle est triée par nom côté serveur.
 */
function showSavedProject(form, project) {
    if (form.hasAttribute('data-redirect-to-project')) {
        window.location.assign(project.url);
    } else {
        window.location.reload();
    }
}

document.addEventListener('submit', handleProjectFormSubmit);
