import { apiDelete, apiPut } from '../../../fonctions/api.js';
import { createElement } from '../../../fonctions/createElement.js';
import { reloadAt } from '../../../fonctions/reloadAt.js';
import { getModal } from '../../../composants/Modal.js';
import { showNotice } from '../../../composants/Notice.js';
import { SectionBlockEditor } from './SectionBlockEditor.js';

// Sur <html> pendant qu'un éditeur est ouvert : les champs ne passent pas sous les barres collantes (app.css)
const EDITOR_OPEN_CLASS = 'section-editor-open';

/**
 * Sections de la présentation d'un projet (templates/app/project/show.html.twig).
 *
 *   [data-project-sections data-api-url="…/sections" data-files-url="…/fichiers"]
 *     [data-section data-section-id data-section-title data-section-data="{…}"]
 *       [data-section-action="up|down|edit|delete"]   (_section_tools.html.twig)
 *       [data-image-zoom data-image-title]             image d'un bloc, ouverte en grand
 *     [data-new-section-slot]                           place de l'éditeur d'une nouvelle section
 *   [data-section-add]                                  bouton « Ajouter une section » (en-tête de la page)
 *
 * Modifier une section remplace son affichage par l'éditeur de blocs (SectionBlockEditor) ;
 * un seul éditeur est ouvert à la fois.
 */
export class ProjectSections {
    constructor(root) {
        this.root = root;
        this.apiUrl = root.dataset.apiUrl;
        this.filesUrl = root.dataset.filesUrl;
        this.newSectionSlot = root.querySelector('[data-new-section-slot]');
        this.emptyMessage = root.querySelector('[data-sections-empty]');
        this.editor = null;
        // Section cachée pendant sa modification
        this.editedSection = null;

        this.handleClick = this.handleClick.bind(this);
        this.handleAddClick = this.handleAddClick.bind(this);
        this.closeEditor = this.closeEditor.bind(this);
    }

    init() {
        this.root.addEventListener('click', this.handleClick);
        // Le bouton « Ajouter une section » est dans l'en-tête, en dehors des sections
        document.addEventListener('click', this.handleAddClick);
    }

    handleAddClick(event) {
        if (!event.target.closest('[data-section-add]')) {
            return;
        }

        event.preventDefault();
        this.openEditor(null);
    }

    handleClick(event) {
        const zoom = event.target.closest('[data-image-zoom]');

        if (zoom) {
            this.zoomImage(zoom);

            return;
        }

        const button = event.target.closest('[data-section-action]');
        const element = button?.closest('[data-section]');

        if (!element || button.disabled) {
            return;
        }

        switch (button.dataset.sectionAction) {
            case 'up':
            case 'down':
                this.move(element, button.dataset.sectionAction);
                break;
            case 'edit':
                this.openEditor(element);
                break;
            case 'delete':
                this.delete(element);
                break;
        }
    }

    /**
     * @param {HTMLElement|null} sectionElement la section à modifier, null pour une nouvelle section
     */
    openEditor(sectionElement) {
        if (this.editor && !this.closeOpenEditor()) {
            return;
        }

        const isNew = sectionElement === null;

        this.editor = new SectionBlockEditor({
            section: isNew ? null : JSON.parse(sectionElement.dataset.sectionData),
            saveUrl: isNew ? this.apiUrl : this.getSectionUrl(sectionElement),
            filesUrl: this.filesUrl,
            onCancel: this.closeEditor,
        });

        if (isNew) {
            this.newSectionSlot.append(this.editor.getElement());

            if (this.emptyMessage) {
                this.emptyMessage.hidden = true;
            }
        } else {
            this.editedSection = sectionElement;
            sectionElement.after(this.editor.getElement());
            sectionElement.hidden = true;
        }

        document.documentElement.classList.add(EDITOR_OPEN_CLASS);
        this.editor.getElement().scrollIntoView({ block: 'start', behavior: 'smooth' });
        this.editor.focus();
    }

    /**
     * Ferme l'éditeur déjà ouvert avant d'en ouvrir un autre (après confirmation s'il a été modifié).
     *
     * @returns {boolean} false si la personne veut garder ses modifications
     */
    closeOpenEditor() {
        if (this.editor.hasChanges() && !window.confirm('Une section est en cours de modification. Abandonner ses modifications ?')) {
            this.editor.getElement().scrollIntoView({ block: 'start', behavior: 'smooth' });

            return false;
        }

        this.editor.destroy();
        this.closeEditor();

        return true;
    }

    /**
     * L'éditeur est retiré : la section modifiée réapparaît.
     */
    closeEditor() {
        if (this.editedSection) {
            this.editedSection.hidden = false;
            this.editedSection.scrollIntoView({ block: 'nearest' });
        }

        if (this.emptyMessage) {
            this.emptyMessage.hidden = false;
        }

        document.documentElement.classList.remove(EDITOR_OPEN_CLASS);
        this.editor = null;
        this.editedSection = null;
    }

    async move(element, direction) {
        try {
            const result = await apiPut(`${this.getSectionUrl(element)}/position`, { direction });
            reloadAt(result.url);
        } catch (error) {
            showNotice('error', `Déplacement impossible : ${error.message}`);
        }
    }

    async delete(element) {
        if (!window.confirm(`Supprimer la section « ${element.dataset.sectionTitle} » et tout son contenu ?\nLes fichiers restent dans la page Fichiers.`)) {
            return;
        }

        try {
            const result = await apiDelete(this.getSectionUrl(element));
            reloadAt(result.url);
        } catch (error) {
            showNotice('error', `Suppression impossible : ${error.message}`);
        }
    }

    /**
     * Une image d'un bloc, ouverte en grand dans la modal.
     */
    zoomImage(button) {
        const title = button.dataset.imageTitle ?? '';

        getModal().open({
            title,
            size: 'large',
            content: createElement('img', {
                className: 'section-block__zoomed-image',
                attributes: { src: button.dataset.imageZoom, alt: title },
            }),
        });
    }

    getSectionUrl(element) {
        return `${this.apiUrl}/${element.dataset.sectionId}`;
    }
}

/**
 * Active les sections de la présentation, sur la page d'un projet.
 */
export function initProjectSections() {
    const root = document.querySelector('[data-project-sections]');

    if (root) {
        new ProjectSections(root).init();
    }
}
