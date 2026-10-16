import { apiDelete, apiPut } from '../../../fonctions/api.js';
import { createElement } from '../../../fonctions/createElement.js';
import { reloadAt } from '../../../fonctions/reloadAt.js';
import { getModal } from '../../../composants/Modal.js';
import { showNotice } from '../../../composants/Notice.js';
import { SectionBlockEditor, toPayloadBlock } from './SectionBlockEditor.js';

const EDITOR_NAME = 'section-editor';
const EDITOR_SIZE = 'large';

/**
 * Sections de la présentation d'un projet (templates/app/project/show.html.twig).
 *
 *   [data-project-sections data-api-url="…/sections" data-files-url="…/fichiers"]
 *     [data-section data-section-id data-section-title data-section-data="{…}"]
 *   [data-toc-action="up|down|edit|delete" data-section-target="{id}"]   boutons du sommaire (_project_identity.html.twig)
 *       [data-image-zoom data-image-title]             image d'un bloc, ouverte en grand
 *       [data-document-open data-document-preview data-document-title]   document ouvert en grand
 *       [data-block-file-remove data-block-index data-file-id data-file-title]
 *                                                       corbeille d'une image ou d'un document (au survol)
 *   [data-section-add]                                  bouton « Ajouter une section » (en-tête de la page)
 *
 * Ajouter ou modifier une section ouvre l'éditeur de blocs (SectionBlockEditor) dans la modal.
 * Son bouton « Aperçu » (EditorPreview.js) agrandit la modal pour afficher l'aperçu à côté du formulaire.
 * Fermer la modal (Annuler, croix, Échap) demande confirmation s'il y a des modifications.
 */
export class ProjectSections {
    constructor(root) {
        this.root = root;
        this.apiUrl = root.dataset.apiUrl;
        this.filesUrl = root.dataset.filesUrl;
        this.editor = null;

        this.handleClick = this.handleClick.bind(this);
        this.handleAddClick = this.handleAddClick.bind(this);
        this.handleTocClick = this.handleTocClick.bind(this);
        this.handleModalClose = this.handleModalClose.bind(this);
        this.closeModal = this.closeModal.bind(this);
        this.confirmEditorClose = this.confirmEditorClose.bind(this);
    }

    init() {
        this.root.addEventListener('click', this.handleClick);
        // Le bouton « Ajouter une section » est dans l'en-tête, et les boutons des sections dans le sommaire :
        // en dehors des sections, écoute sur document
        document.addEventListener('click', this.handleAddClick);
        document.addEventListener('click', this.handleTocClick);
        // L'éditeur est oublié quand sa modal se ferme
        document.addEventListener('modal:close', this.handleModalClose);
    }

    handleAddClick(event) {
        if (!event.target.closest('[data-section-add]')) {
            return;
        }

        event.preventDefault();
        this.openEditor(null);
    }

    /**
     * Bouton d'une ligne du sommaire : il agit sur la section dont l'id est dans data-section-target.
     */
    handleTocClick(event) {
        const button = event.target.closest('[data-toc-action]');
        const element = button ? document.getElementById(`section-${button.dataset.sectionTarget}`) : null;

        if (!element || button.disabled) {
            return;
        }

        this.runSectionAction(button.dataset.tocAction, element);
    }

    handleClick(event) {
        const remove = event.target.closest('[data-block-file-remove]');

        if (remove) {
            this.removeBlockFile(remove);

            return;
        }

        const zoom = event.target.closest('[data-image-zoom]');

        if (zoom) {
            this.zoomImage(zoom);

            return;
        }

        const documentButton = event.target.closest('[data-document-open]');

        if (documentButton) {
            this.openDocument(documentButton);

            return;
        }

        const button = event.target.closest('[data-section-action]');
        const element = button?.closest('[data-section]');

        if (!element || button.disabled) {
            return;
        }

        this.runSectionAction(button.dataset.sectionAction, element);
    }

    runSectionAction(action, element) {
        switch (action) {
            case 'up':
            case 'down':
                this.move(element, action);
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
        const isNew = sectionElement === null;

        this.editor = new SectionBlockEditor({
            section: isNew ? null : JSON.parse(sectionElement.dataset.sectionData),
            saveUrl: isNew ? this.apiUrl : this.getSectionUrl(sectionElement),
            filesUrl: this.filesUrl,
            onCancel: this.closeModal,
        });

        getModal().open({
            title: isNew ? 'Nouvelle section' : `Modifier « ${sectionElement.dataset.sectionTitle} »`,
            content: this.editor.getElement(),
            size: EDITOR_SIZE,
            name: EDITOR_NAME,
            beforeClose: this.confirmEditorClose,
        });

        this.editor.focus();
    }

    confirmEditorClose() {
        return this.editor?.confirmClose() ?? true;
    }

    closeModal() {
        getModal().close();
    }

    handleModalClose(event) {
        if (event.detail.name === EDITOR_NAME) {
            this.editor = null;
        }
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
     * Retire une image ou un document de son bloc, directement depuis la page : la section est
     * réenregistrée sans ce fichier (un bloc qui n'a plus de fichier disparaît).
     * Le fichier reste dans la page Fichiers du projet.
     */
    async removeBlockFile(button) {
        if (!window.confirm(`Retirer « ${button.dataset.fileTitle} » de la section ?\nLe fichier reste dans la page Fichiers.`)) {
            return;
        }

        const element = button.closest('[data-section]');
        const section = JSON.parse(element.dataset.sectionData);
        const block = section.blocks[Number(button.dataset.blockIndex)];
        const fileId = Number(button.dataset.fileId);

        block.files = block.files.filter((file) => file.id !== fileId);

        const blocks = section.blocks.filter((item) => !Array.isArray(item.files) || item.files.length > 0);

        button.disabled = true;

        try {
            const result = await apiPut(this.getSectionUrl(element), { title: section.title, blocks: blocks.map(toPayloadBlock) });
            reloadAt(result.url);
        } catch (error) {
            button.disabled = false;
            showNotice('error', `Impossible de retirer le fichier : ${error.message}`);
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

    /**
     * Un document d'un bloc, ouvert en grand dans la modal : PDF ou texte dans une iframe, image en grand.
     */
    openDocument(button) {
        const title = button.dataset.documentTitle ?? '';
        // PDF : volet latéral fermé au départ (Chrome : navpanes ; Firefox : pagemode), la barre d'outils reste
        const url = button.dataset.documentPreview === 'pdf' ? `${button.dataset.documentOpen}#navpanes=0&pagemode=none` : button.dataset.documentOpen;
        const content = button.dataset.documentPreview === 'image'
            ? createElement('img', { className: 'section-block__zoomed-image', attributes: { src: url, alt: title } })
            : createElement('iframe', { className: 'section-block__document-viewer', attributes: { src: url, title } });

        getModal().open({ title, size: 'large', content });
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
