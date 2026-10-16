import { apiDelete } from '../../fonctions/api.js';
import { reloadAt } from '../../fonctions/reloadAt.js';
import { showNotice } from '../../composants/Notice.js';

/**
 * Fiche du projet, à droite de la page « Présentation » (templates/app/project/_project_identity.html.twig).
 *
 *   [data-project-identity]
 *     [data-project-image-delete="/api/projet/{slug}/images/logo-long" data-image-label="Logo long"]
 *
 * La corbeille d'une image la supprime du projet (le fichier est effacé), après confirmation,
 * puis la page est rechargée. Les cases vides ouvrent le formulaire du projet (data-modal-open, Modal.js).
 */
export class ProjectIdentity {
    constructor(root) {
        this.root = root;
        this.handleClick = this.handleClick.bind(this);
    }

    init() {
        this.root.addEventListener('click', this.handleClick);
    }

    handleClick(event) {
        const button = event.target.closest('[data-project-image-delete]');

        if (button && !button.disabled) {
            this.deleteImage(button);
        }
    }

    async deleteImage(button) {
        if (!window.confirm(`Supprimer l'image « ${button.dataset.imageLabel} » du projet ?\nElle est effacée : il faudra la renvoyer pour la remettre.`)) {
            return;
        }

        button.disabled = true;

        try {
            const result = await apiDelete(button.dataset.projectImageDelete);
            reloadAt(result.url);
        } catch (error) {
            button.disabled = false;
            showNotice('error', `Suppression impossible : ${error.message}`);
        }
    }
}

export function initProjectIdentity() {
    const root = document.querySelector('[data-project-identity]');

    if (root) {
        new ProjectIdentity(root).init();
    }
}
