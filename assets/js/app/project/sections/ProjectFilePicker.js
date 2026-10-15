import { apiGet, apiPost } from '../../../fonctions/api.js';
import { createElement } from '../../../fonctions/createElement.js';
import { formatFileSize } from '../../../fonctions/fileSize.js';
import { getModal } from '../../../composants/Modal.js';

const IMAGE_TYPE = 'image';

/**
 * Choix des fichiers d'un bloc images ou documents, dans la modal.
 *
 * Liste les fichiers du projet (page Fichiers) avec une case à cocher, et permet d'en envoyer
 * un nouveau : il est ajouté aux Fichiers du projet puis coché automatiquement.
 * Pour un bloc images, seules les images sont proposées.
 *
 *      new ProjectFilePicker(filesUrl).open({ imagesOnly: true, excludedIds: [3, 8], onSelect: (files) => … });
 */
export class ProjectFilePicker {
    /**
     * @param {string} filesUrl API des fichiers du projet (GET : liste, POST : envoi d'un fichier)
     */
    constructor(filesUrl) {
        this.filesUrl = filesUrl;
        this.files = [];
        this.selectedIds = new Set();
        this.options = null;
        this.element = null;

        this.handleClick = this.handleClick.bind(this);
        this.handleChange = this.handleChange.bind(this);
    }

    /**
     * @param {object} options
     * @param {boolean} options.imagesOnly bloc images : seules les images sont proposées
     * @param {number[]} options.excludedIds fichiers déjà dans le bloc (non proposés)
     * @param {function(object[]): void} options.onSelect reçoit les fichiers choisis
     */
    open(options) {
        this.options = options;
        this.selectedIds.clear();
        this.element = this.buildElement();

        getModal().open({
            title: options.imagesOnly ? 'Ajouter des images' : 'Ajouter des documents',
            content: this.element,
            size: 'large',
        });

        this.loadFiles();
    }

    buildElement() {
        const accept = this.options.imagesOnly ? 'image/*' : '';
        const element = createElement('div', {
            className: 'file-picker',
            children: [
                createElement('div', {
                    className: 'file-picker__upload',
                    children: [
                        createElement('label', { className: 'file-picker__upload-label', text: this.options.imagesOnly ? 'Envoyer une nouvelle image' : 'Envoyer un nouveau fichier', attributes: { for: 'file-picker-upload' } }),
                        createElement('input', { className: 'file-picker__upload-input', attributes: { type: 'file', id: 'file-picker-upload', accept, 'data-picker-upload': '' } }),
                        createElement('span', { className: 'file-picker__upload-help', text: 'Il est ajouté aux Fichiers du projet et coché ci-dessous.' }),
                    ],
                }),
                createElement('p', { className: 'file-picker__status', attributes: { 'data-picker-status': '', role: 'status' }, text: 'Chargement des fichiers…' }),
                createElement('div', { className: 'file-picker__list', attributes: { 'data-picker-list': '' } }),
                createElement('p', { className: 'file-picker__error', attributes: { 'data-picker-error': '', role: 'alert' } }),
                createElement('div', {
                    className: 'file-picker__footer',
                    children: [
                        createElement('button', { className: 'projects__button projects__button--ghost', text: 'Annuler', attributes: { type: 'button', 'data-modal-close': '' } }),
                        createElement('button', { className: 'projects__button', text: 'Ajouter la sélection', attributes: { type: 'button', 'data-picker-confirm': '' } }),
                    ],
                }),
            ],
        });

        element.addEventListener('click', this.handleClick);
        element.addEventListener('change', this.handleChange);

        return element;
    }

    async loadFiles() {
        try {
            this.files = await apiGet(this.filesUrl);
            this.renderList();
        } catch (error) {
            this.setStatus(`Impossible de charger les fichiers : ${error.message}`);
        }
    }

    /**
     * Les fichiers proposés : ceux du bon type, sans ceux déjà dans le bloc.
     */
    getAvailableFiles() {
        return this.files.filter((file) => (!this.options.imagesOnly || file.type === IMAGE_TYPE)
            && !this.options.excludedIds.includes(file.id));
    }

    renderList() {
        const files = this.getAvailableFiles();
        const list = this.element.querySelector('[data-picker-list]');

        list.replaceChildren(...files.map((file) => this.renderFile(file)));

        if (files.length === 0) {
            this.setStatus(this.options.imagesOnly
                ? 'Aucune autre image dans les Fichiers du projet : envoie-en une nouvelle.'
                : 'Aucun autre fichier dans les Fichiers du projet : envoie-en un nouveau.');
        } else {
            this.setStatus('');
        }
    }

    renderFile(file) {
        const checkbox = createElement('input', {
            className: 'file-picker__checkbox',
            attributes: { type: 'checkbox', value: String(file.id), 'data-picker-file': '' },
        });
        checkbox.checked = this.selectedIds.has(file.id);

        const preview = file.type === IMAGE_TYPE
            ? createElement('img', { className: 'file-picker__image', attributes: { src: file.viewUrl, alt: '', loading: 'lazy' } })
            : createElement('span', { className: 'file-picker__extension', text: (file.extension ?? '?').toUpperCase() });

        return createElement('label', {
            className: 'file-picker__item',
            children: [
                checkbox,
                preview,
                createElement('span', {
                    className: 'file-picker__text',
                    children: [
                        createElement('span', { className: 'file-picker__title', text: file.title }),
                        createElement('span', { className: 'file-picker__meta', text: `${(file.extension ?? '').toUpperCase()} · ${formatFileSize(file.size)}` }),
                    ],
                }),
            ],
        });
    }

    handleChange(event) {
        if (event.target.matches('[data-picker-file]')) {
            const id = Number(event.target.value);
            event.target.checked ? this.selectedIds.add(id) : this.selectedIds.delete(id);
        } else if (event.target.matches('[data-picker-upload]')) {
            this.upload(event.target);
        }
    }

    handleClick(event) {
        if (event.target.closest('[data-picker-confirm]')) {
            this.confirm();
        }
    }

    async upload(input) {
        const file = input.files[0];

        if (!file) {
            return;
        }

        if (this.options.imagesOnly && !file.type.startsWith('image/')) {
            this.setError('Ce fichier n\'est pas une image : ajoute-le dans un bloc Documents.');
            input.value = '';

            return;
        }

        const data = new FormData();
        data.append('file', file);
        // Titre vide : le serveur prend le nom du fichier. Le champ doit être envoyé quand même :
        // sans aucun champ texte, Symfony (MapRequestPayload) refuse la requête (« invalid "form" data »)
        data.append('title', '');
        data.append('description', '');
        this.setError('');
        this.setStatus('Envoi du fichier…');
        input.disabled = true;

        try {
            const uploaded = await apiPost(this.filesUrl, data);
            this.files.unshift(uploaded);
            this.selectedIds.add(uploaded.id);
            this.renderList();
        } catch (error) {
            this.setStatus('');
            this.setError(`Envoi impossible : ${error.message}`);
        } finally {
            input.disabled = false;
            input.value = '';
        }
    }

    confirm() {
        const selected = this.files.filter((file) => this.selectedIds.has(file.id));

        if (selected.length === 0) {
            this.setError('Coche au moins un fichier, ou envoie-en un nouveau.');

            return;
        }

        this.options.onSelect(selected);
        getModal().close();
    }

    setStatus(text) {
        const status = this.element.querySelector('[data-picker-status]');
        status.textContent = text;
        status.hidden = text === '';
    }

    setError(text) {
        this.element.querySelector('[data-picker-error]').textContent = text;
    }
}
