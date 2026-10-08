import { apiDelete, apiGet, apiPatch, apiPost } from '../../fonctions/api.js';
import { formatFileSize } from '../../fonctions/fileSize.js';
import { getModal, openModalFromTemplate } from '../../composants/Modal.js';
import { showNotice } from '../../composants/Notice.js';
import { matchesSearch, PAGE_SEARCH_EVENT } from '../../composants/PageSearch.js';

const TYPE_IMAGE = 'image';
const TYPE_DOCUMENT = 'document';
const PREVIEW_IMAGE = 'image';
const PREVIEW_PDF = 'pdf';
const PREVIEW_TEXT = 'text';
const ADD_FORM_TEMPLATE_ID = 'project-file-add';
const EDIT_FORM_TEMPLATE_ID = 'project-file-edit';

// Icône d'un document selon son extension (clé = data-icon-… de la page)
const DOCUMENT_ICONS = {
    pdf: 'pdf',
    doc: 'word',
    docx: 'word',
    xls: 'excel',
    xlsx: 'excel',
    ppt: 'powerpoint',
    pptx: 'powerpoint',
    txt: 'text',
    md: 'text',
};

const EMPTY_MESSAGES = {
    [TYPE_IMAGE]: 'Aucune image pour l\'instant.',
    [TYPE_DOCUMENT]: 'Aucun document pour l\'instant.',
};

const NO_MATCH_MESSAGES = {
    [TYPE_IMAGE]: 'Aucune image ne correspond à la recherche.',
    [TYPE_DOCUMENT]: 'Aucun document ne correspond à la recherche.',
};

const DATE_FORMAT = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });

/**
 * Page « Fichiers du projet » : chargement, ajout (modal), aperçu (modal),
 * modification du titre et de la description, suppression, recherche.
 *
 * Les images sont affichées en galerie, les documents en liste.
 */
export class ProjectFileList {
    constructor(root) {
        this.root = root;
        this.apiUrl = root.dataset.apiUrl;
        this.templates = {
            [TYPE_IMAGE]: root.querySelector('[data-template="image"]'),
            [TYPE_DOCUMENT]: root.querySelector('[data-template="document"]'),
            viewer: root.querySelector('[data-template="viewer"]'),
        };

        this.files = [];
        // Fichier en cours de modification dans la modal
        this.editedFile = null;
        // Recherche de l'en-tête de la page (texte normalisé, '' = pas de recherche)
        this.searchQuery = '';

        this.handleClick = this.handleClick.bind(this);
        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleSearch = this.handleSearch.bind(this);
    }

    async init() {
        this.root.addEventListener('click', this.handleClick);
        // Les formulaires sont dans la modal, en dehors de la page : écoute sur document
        document.addEventListener('submit', this.handleSubmit);
        // La barre de recherche est dans l'en-tête de la page, hors de this.root
        document.addEventListener(PAGE_SEARCH_EVENT, this.handleSearch);

        await this.load();
    }

    async load() {
        try {
            this.files = await apiGet(this.apiUrl);
        } catch (error) {
            showNotice('error', `Impossible de charger les fichiers : ${error.message}`);
        }

        this.render();
    }

    /* ------------------------------------------------------------------
       Affichage
       ------------------------------------------------------------------ */

    render() {
        for (const type of [TYPE_IMAGE, TYPE_DOCUMENT]) {
            const allFiles = this.files.filter((file) => file.type === type);
            const files = allFiles.filter(this.matchesSearch, this);
            const list = this.root.querySelector(`[data-list="${type}"]`);
            const empty = this.root.querySelector(`[data-empty="${type}"]`);

            list.replaceChildren(...files.map((file) => this.createItem(file)));
            this.root.querySelector(`[data-count="${type}"]`).textContent = String(files.length);
            empty.textContent = allFiles.length > 0 ? NO_MATCH_MESSAGES[type] : EMPTY_MESSAGES[type];
            empty.hidden = files.length > 0;
        }
    }

    handleSearch(event) {
        this.searchQuery = event.detail.normalizedQuery;
        this.render();
    }

    matchesSearch(file) {
        return matchesSearch(`${file.title} ${file.description ?? ''} ${file.originalName}`, this.searchQuery);
    }

    createItem(file) {
        const item = this.templates[file.type].content.firstElementChild.cloneNode(true);

        item.dataset.id = String(file.id);
        this.fillItem(item, file);

        return item;
    }

    fillItem(item, file) {
        item.querySelector('[data-field="title"]').textContent = file.title;
        item.querySelector('[data-field="meta"]').textContent = this.getMeta(file);
        item.querySelector('[data-action="download"]').href = file.downloadUrl;
        this.setActionLabels(item, file);

        if (file.type === TYPE_IMAGE) {
            const thumbnail = item.querySelector('[data-field="thumbnail"]');
            thumbnail.src = file.viewUrl;
            thumbnail.alt = file.title;
            item.title = file.description ?? '';

            return;
        }

        const description = item.querySelector('[data-field="description"]');
        description.textContent = file.description ?? '';
        description.hidden = !file.description;
        item.querySelector('[data-field="icon"]').src = this.getDocumentIcon(file);
        // Word, Excel, PowerPoint : pas d'aperçu possible dans le navigateur
        item.querySelector('[data-action="view"]').hidden = file.preview === null;
    }

    setActionLabels(item, file) {
        const labels = {
            view: `Afficher « ${file.title} »`,
            download: `Télécharger « ${file.title} »`,
            edit: `Modifier « ${file.title} »`,
            delete: `Supprimer « ${file.title} »`,
        };

        for (const [action, label] of Object.entries(labels)) {
            const element = item.querySelector(`[data-action="${action}"]`);

            if (element) {
                element.setAttribute('aria-label', label);
                element.title = label;
            }
        }
    }

    /**
     * « PDF · 2,4 Mo · 7 oct. 2026 »
     */
    getMeta(file) {
        const parts = [formatFileSize(file.size), DATE_FORMAT.format(new Date(file.createdAt))];

        if (file.extension) {
            parts.unshift(file.extension.toUpperCase());
        }

        return parts.join(' · ');
    }

    getDocumentIcon(file) {
        const icon = DOCUMENT_ICONS[file.extension] ?? 'other';

        return this.root.dataset[`icon${icon.charAt(0).toUpperCase()}${icon.slice(1)}`] ?? '';
    }

    /* ------------------------------------------------------------------
       Clics (délégation : un seul écouteur pour toute la page)
       ------------------------------------------------------------------ */

    handleClick(event) {
        const actionElement = event.target.closest('[data-action]');

        if (!actionElement || !this.root.contains(actionElement)) {
            return;
        }

        const file = this.findFile(Number(actionElement.closest('[data-file]')?.dataset.id));

        if (!file) {
            return;
        }

        switch (actionElement.dataset.action) {
            case 'view':
                this.openViewer(file);
                break;
            case 'edit':
                this.openEditForm(file);
                break;
            case 'delete':
                this.deleteFile(file);
                break;
            // download : lien classique, le navigateur s'en charge
        }
    }

    /* ------------------------------------------------------------------
       Aperçu dans la modal : image, PDF ou texte
       ------------------------------------------------------------------ */

    openViewer(file) {
        const viewer = this.templates.viewer.content.firstElementChild.cloneNode(true);
        const content = viewer.querySelector('[data-viewer-content]');
        const description = viewer.querySelector('[data-viewer-description]');

        description.textContent = file.description ?? '';
        description.hidden = !file.description;
        viewer.querySelector('[data-viewer-download]').href = file.downloadUrl;

        switch (file.preview) {
            case PREVIEW_IMAGE:
                content.append(this.createImagePreview(file));
                break;
            case PREVIEW_PDF:
                content.append(this.createPdfPreview(file));
                break;
            case PREVIEW_TEXT:
                content.append(this.createTextPreview(file));
                break;
            default:
                content.textContent = 'Ce type de fichier ne peut pas être affiché dans le navigateur : télécharge-le pour l\'ouvrir.';
        }

        getModal().open({ title: file.title, content: viewer, size: 'large' });
    }

    createImagePreview(file) {
        const image = document.createElement('img');

        image.className = 'file-viewer__image';
        image.src = file.viewUrl;
        image.alt = file.title;

        return image;
    }

    createPdfPreview(file) {
        const frame = document.createElement('iframe');

        frame.className = 'file-viewer__frame';
        frame.src = file.viewUrl;
        frame.title = file.title;

        return frame;
    }

    /**
     * Le texte est lu puis inséré avec textContent : jamais interprété comme du HTML.
     */
    createTextPreview(file) {
        const text = document.createElement('pre');

        text.className = 'file-viewer__text';
        text.textContent = 'Chargement…';
        this.loadText(file, text);

        return text;
    }

    async loadText(file, element) {
        try {
            const response = await fetch(file.viewUrl);

            if (!response.ok) {
                throw new Error(`Erreur ${response.status}`);
            }

            element.textContent = await response.text();
        } catch (error) {
            element.textContent = `Impossible d'afficher le fichier : ${error.message}`;
        }
    }

    /* ------------------------------------------------------------------
       Ajout et modification (formulaires dans la modal)
       ------------------------------------------------------------------ */

    openEditForm(file) {
        const modal = openModalFromTemplate(EDIT_FORM_TEMPLATE_ID, { title: 'Modifier le fichier' });
        const form = modal?.getBody().querySelector('[data-file-edit-form]');

        if (!form) {
            return;
        }

        this.editedFile = file;
        form.elements.title.value = file.title;
        form.elements.description.value = file.description ?? '';
    }

    handleSubmit(event) {
        const form = event.target;

        if (form.matches('[data-file-add-form]')) {
            event.preventDefault();
            this.submitAddForm(form);
        } else if (form.matches('[data-file-edit-form]')) {
            event.preventDefault();
            this.submitEditForm(form);
        }
    }

    async submitAddForm(form) {
        if (form.elements.file.files.length === 0) {
            this.showFormError(form, 'Choisis un fichier à envoyer.', form.elements.file);

            return;
        }

        await this.sendForm(form, async () => {
            const file = await apiPost(this.apiUrl, new FormData(form));

            // Le plus récent en premier, comme au chargement
            this.files.unshift(file);
            showNotice('success', `« ${file.title} » a été ajouté.`);
        });
    }

    async submitEditForm(form) {
        const file = this.editedFile;
        const title = form.elements.title.value.trim();

        if (!file) {
            return;
        }

        if (title === '') {
            this.showFormError(form, 'Le titre est obligatoire.', form.elements.title);

            return;
        }

        await this.sendForm(form, async () => {
            const updatedFile = await apiPatch(this.getFileUrl(file.id), {
                title,
                description: form.elements.description.value.trim(),
            });

            this.replaceFile(updatedFile);
            this.editedFile = null;
            showNotice('success', 'Modification enregistrée.');
        });
    }

    /**
     * Envoi commun : bouton désactivé pendant l'envoi, erreur affichée dans le formulaire,
     * fermeture de la modal et nouvel affichage en cas de succès.
     */
    async sendForm(form, send) {
        const submitButton = form.querySelector('[type="submit"]');

        form.querySelector('[data-form-error]').textContent = '';
        submitButton.disabled = true;

        try {
            await send();
            getModal().close();
            this.render();
        } catch (apiError) {
            this.showFormError(form, apiError.message);
            submitButton.disabled = false;
        }
    }

    showFormError(form, message, field = null) {
        form.querySelector('[data-form-error]').textContent = message;
        field?.focus();
    }

    /* ------------------------------------------------------------------
       Suppression
       ------------------------------------------------------------------ */

    async deleteFile(file) {
        if (!window.confirm(`Supprimer « ${file.title} » ? Le fichier sera définitivement effacé.`)) {
            return;
        }

        try {
            await apiDelete(this.getFileUrl(file.id));
            this.files = this.files.filter((item) => item.id !== file.id);
            this.render();
            showNotice('success', `« ${file.title} » a été supprimé.`);
        } catch (error) {
            showNotice('error', `Suppression impossible : ${error.message}`);
        }
    }

    /* ------------------------------------------------------------------
       Outils
       ------------------------------------------------------------------ */

    findFile(id) {
        return this.files.find((file) => file.id === id) ?? null;
    }

    replaceFile(updatedFile) {
        this.files = this.files.map((file) => (file.id === updatedFile.id ? updatedFile : file));
    }

    getFileUrl(id) {
        return `${this.apiUrl}/${id}`;
    }
}
