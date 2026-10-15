/**
 * Images du formulaire d'un projet (logo, logo long, avatar), dans la modal.
 *
 * HTML attendu (templates/app/project/_project_form.html.twig, macro image_field) :
 *   [data-image-field]                     un champ image (classe --filled quand il a une image)
 *     [data-image-input]                   <input type="file"> caché dans l'image cliquable
 *     [data-image-preview]                 aperçu de l'image choisie ou actuelle
 *     [data-image-remove]                  croix : retire l'image
 *     [data-image-remove-flag]             champ caché removeLogo… (modification seulement)
 *
 * Choisir un fichier affiche son aperçu à la place de l'image vide ; rien n'est envoyé avant
 * « Créer le projet » / « Enregistrer ». La croix vide le champ et, pour une image déjà enregistrée,
 * active le champ caché qui demande au serveur de la supprimer.
 *
 * Le formulaire est copié dans la modal à chaque ouverture : les événements sont écoutés sur document.
 */
const FILLED_CLASS = 'project-form__image--filled';

export class ProjectImageFields {
    constructor() {
        this.handleChange = this.handleChange.bind(this);
        this.handleClick = this.handleClick.bind(this);
    }

    init() {
        document.addEventListener('change', this.handleChange);
        document.addEventListener('click', this.handleClick);
    }

    handleChange(event) {
        const input = event.target.closest('[data-image-input]');
        const file = input?.files[0];

        if (!file) {
            return;
        }

        const field = input.closest('[data-image-field]');
        this.showPreview(field, URL.createObjectURL(file));
        // Le nouveau fichier remplace l'image actuelle : plus besoin de la supprimer
        this.setRemoveFlag(field, false);
    }

    handleClick(event) {
        const button = event.target.closest('[data-image-remove]');

        if (!button) {
            return;
        }

        const field = button.closest('[data-image-field]');
        const input = field.querySelector('[data-image-input]');
        const hadSavedImage = field.querySelector('[data-image-remove-flag]') !== null;

        input.value = '';
        this.showPreview(field, null);
        this.setRemoveFlag(field, hadSavedImage);
    }

    /**
     * @param {HTMLElement} field
     * @param {string|null} url adresse de l'aperçu, null pour revenir à l'image vide
     */
    showPreview(field, url) {
        const preview = field.querySelector('[data-image-preview]');

        // L'aperçu d'un fichier choisi est une adresse temporaire : on la libère avant de la remplacer
        if (preview.src.startsWith('blob:')) {
            URL.revokeObjectURL(preview.src);
        }

        if (url) {
            preview.src = url;
        } else {
            preview.removeAttribute('src');
        }

        preview.hidden = !url;
        field.classList.toggle(FILLED_CLASS, Boolean(url));
    }

    setRemoveFlag(field, remove) {
        const flag = field.querySelector('[data-image-remove-flag]');

        if (flag) {
            flag.disabled = !remove;
        }
    }
}

/**
 * Active les champs images des formulaires de projet (pages Projets et Projet).
 */
export function initProjectImageFields() {
    new ProjectImageFields().init();
}
