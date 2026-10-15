import { apiPost, apiPut } from '../../../fonctions/api.js';
import { EditorPreview } from '../../../composants/EditorPreview.js';
import { htmlToMarkup } from '../../../fonctions/inlineMarkup.js';
import { reloadAt } from '../../../fonctions/reloadAt.js';
import { BlockFieldsRenderer } from './BlockFieldsRenderer.js';
import { FormatToolbar } from './FormatToolbar.js';
import { ProjectFilePicker } from './ProjectFilePicker.js';
import { SectionPreview } from './SectionPreview.js';

const TEMPLATE_ID = 'section-editor-template';
const TITLE_FIELD_ID = 'section-editor-title';

/**
 * Éditeur d'une section de projet : un titre (le <h2>) et une suite de blocs.
 *
 * L'éditeur est copié depuis <template id="section-editor-template"> (_section_editor.html.twig)
 * et affiché dans la modal par ProjectSections.js. Le bouton « Aperçu » (EditorPreview.js) affiche à côté
 * l'aperçu de la section (SectionPreview.js) : le formulaire n'est pas redessiné, rien n'est perdu.
 * Son état ({ title, blocks }) a le même format que ce qu'attend l'API (ProjectSectionPayload) :
 *   paragraphe  { text }                    sous_titre { text }
 *   liste       { ordered, items: [] }      tableau    { style, columns: [{ label, sortable }], rows: [[…]] }
 *   tags        { tags: [{ label, color }] } definitions { items: [{ term, definition }] }
 *   images / documents { files: [{ id, caption, title, extension, size, viewUrl }] }
 *
 * Saisir dans un champ met l'état à jour sans redessiner ; ajouter, retirer ou déplacer redessine les blocs.
 * Les textes mis en forme (champs riches) sont convertis en marques simples (**gras**…) : voir inlineMarkup.js.
 * À l'enregistrement : POST (nouvelle section) ou PUT (section existante), puis la page est rechargée.
 */
export class SectionBlockEditor {
    /**
     * @param {object} options
     * @param {object|null} options.section données de la section (ProjectSectionEditorData), null pour une nouvelle
     * @param {string} options.saveUrl POST pour une nouvelle section, PUT pour une section existante
     * @param {string} options.filesUrl API des fichiers du projet
     * @param {function(): void} options.onCancel appelé quand on clique sur « Annuler » (ferme la modal)
     */
    constructor({ section, saveUrl, filesUrl, onCancel }) {
        this.isNew = section === null;
        this.saveUrl = saveUrl;
        this.onCancel = onCancel;
        this.state = {
            title: section?.title ?? '',
            blocks: structuredClone(section?.blocks ?? []),
        };
        this.isDirty = false;
        // Position où le menu insère le prochain bloc (null : à la fin)
        this.insertAt = null;
        // Bloc images ou documents qui a ouvert le choix des fichiers
        this.fileBlock = null;

        this.form = this.cloneTemplate();
        this.blocksArea = this.form.querySelector('[data-editor-blocks]');
        this.menu = this.form.querySelector('[data-editor-menu]');
        this.menuHome = this.menu.parentElement;
        this.error = this.form.querySelector('[data-editor-error]');
        // Styles d'un tableau (ProjectSectionTableStyle) : [{ value, label, modifier }], le premier par défaut
        this.tableStyles = JSON.parse(this.form.dataset.tableStyles);
        // Couleurs d'un tag (ProjectSectionTagColor) : [{ value, label, modifier }], la première par défaut
        this.tagColors = JSON.parse(this.form.dataset.tagColors);
        // Tag dont le choix de couleur est ouvert : { block, index } ou null
        this.openTagColor = null;
        this.renderer = new BlockFieldsRenderer(JSON.parse(this.form.dataset.blockTypes), this.form.dataset.iconsUrl, this.tableStyles, this.tagColors);
        this.filePicker = new ProjectFilePicker(filesUrl, this.form);
        this.preview = new SectionPreview(this.form.querySelector('[data-preview-content]'), this.form.dataset.iconsUrl, this.tableStyles, this.tagColors);

        this.handleClick = this.handleClick.bind(this);
        this.handleInput = this.handleInput.bind(this);
        this.handleKeyDown = this.handleKeyDown.bind(this);
        this.handleSubmit = this.handleSubmit.bind(this);
        this.updateField = this.updateField.bind(this);
        this.addFiles = this.addFiles.bind(this);
        this.handlePaste = this.handlePaste.bind(this);
        this.renderPreview = this.renderPreview.bind(this);

        this.form.addEventListener('click', this.handleClick);
        this.form.addEventListener('input', this.handleInput);
        this.form.addEventListener('keydown', this.handleKeyDown);
        this.form.addEventListener('submit', this.handleSubmit);
        this.form.addEventListener('paste', this.handlePaste);
        new FormatToolbar(this.form, this.updateField);
        this.previewer = new EditorPreview(this.form, { render: this.renderPreview });

        this.form.elements.title.value = this.state.title;
        this.render();
    }

    /**
     * Copie du modèle, avec un id de champ titre unique (une seule copie à la fois, mais on reste prudent).
     */
    cloneTemplate() {
        const template = document.getElementById(TEMPLATE_ID);
        const form = template.content.firstElementChild.cloneNode(true);
        const uniqueId = `${TITLE_FIELD_ID}-${Date.now()}`;

        form.querySelector(`label[for="${TITLE_FIELD_ID}"]`)?.setAttribute('for', uniqueId);
        form.querySelector(`#${TITLE_FIELD_ID}`).id = uniqueId;

        return form;
    }

    getElement() {
        return this.form;
    }

    focus() {
        const field = this.isNew ? this.form.elements.title : this.blocksArea.querySelector('[data-rich], input') ?? this.form.elements.title;
        field.focus();
    }

    /**
     * Appelé avant la fermeture de la modal (Annuler, croix, Échap, clic sur le fond).
     * Le panneau de choix des fichiers se ferme d'abord ; des modifications non enregistrées sont confirmées.
     *
     * @returns {boolean} false : la modal reste ouverte
     */
    confirmClose() {
        if (this.filePicker.isOpen()) {
            this.filePicker.close();

            return false;
        }

        if (this.openTagColor !== null) {
            this.closeTagColor();

            return false;
        }

        return !this.isDirty || window.confirm('Abandonner les modifications de cette section ?');
    }

    /**
     * Dessine l'aperçu (appelé par EditorPreview quand l'aperçu est affiché).
     */
    renderPreview() {
        this.preview.render(this.state);
    }

    /**
     * Il y a des modifications non enregistrées ?
     */
    hasChanges() {
        return this.isDirty;
    }

    render() {
        // Le menu peut avoir été déplacé entre deux blocs : on le remet à sa place avant de redessiner
        this.closeMenu();

        const count = this.state.blocks.length;
        const elements = [];
        this.renderer.setOpenTagColor(this.openTagColor);

        this.state.blocks.forEach((block, index) => {
            if (index > 0) {
                elements.push(this.renderer.renderInsert(index));
            }

            elements.push(this.renderer.renderBlock(block, index, count));
        });

        this.blocksArea.replaceChildren(...elements);
        this.previewer.refresh();
    }

    handleClick(event) {
        // Clic en dehors des pastilles de couleur d'un tag : elles se ferment
        if (this.openTagColor !== null && !event.target.closest('[data-tag-color-popover], [data-action="tag-color"]')) {
            this.closeTagColor(false);
        }

        const button = event.target.closest('[data-action]');

        if (!button || button.disabled) {
            return;
        }

        const blockIndex = Number(button.dataset.block);
        const block = this.state.blocks[blockIndex];
        const index = Number(button.dataset.index);

        switch (button.dataset.action) {
            case 'toggle-menu':
                this.toggleMenu();
                return;
            case 'insert-block':
                this.openMenuAt(blockIndex, button.closest('.section-editor__insert'));
                return;
            case 'add-block':
                this.addBlock(button.dataset.type);
                return;
            case 'cancel':
                this.onCancel();
                return;
            case 'add-files':
                this.chooseFiles(block);
                return;
            case 'move-block':
                this.moveBlock(blockIndex, button.dataset.direction === 'up' ? -1 : 1);
                break;
            case 'delete-block':
                this.state.blocks.splice(blockIndex, 1);
                break;
            case 'list-ordered':
                block.ordered = button.dataset.value === 'true';
                break;
            case 'add-item':
                block.items.push(block.type === 'definitions' ? { term: '', definition: '' } : '');
                this.renderAndFocus(blockIndex, `[data-index="${block.items.length - 1}"]`);
                return;
            case 'remove-item':
                block.items.splice(index, 1);
                break;
            case 'add-row':
                block.rows.push(block.columns.map(() => ''));
                this.renderAndFocus(blockIndex, `[data-row="${block.rows.length - 1}"]`);
                return;
            case 'remove-row':
                block.rows.splice(Number(button.dataset.row), 1);
                break;
            case 'add-column':
                block.columns.push({ label: '', sortable: true });
                block.rows.forEach((row) => row.push(''));
                this.renderAndFocus(blockIndex, `[data-field="column"][data-col="${block.columns.length - 1}"]`);
                return;
            case 'remove-column':
                this.removeColumn(block, Number(button.dataset.col));
                break;
            case 'remove-tag':
                block.tags.splice(index, 1);
                this.openTagColor = null;
                break;
            case 'tag-color':
                this.toggleTagColor(blockIndex, index);
                return;
            case 'set-tag-color':
                block.tags[index].color = button.dataset.color;
                this.openTagColor = null;
                break;
            case 'remove-file':
                block.files.splice(index, 1);
                break;
            default:
                return;
        }

        this.isDirty = true;
        this.render();
    }

    handleInput(event) {
        const field = event.target.closest('[data-rich]') ?? event.target;

        this.updateField(field);
    }

    /**
     * Coller dans un texte riche : le texte seul (pas les couleurs, polices… de la page d'origine).
     */
    handlePaste(event) {
        if (!event.target.closest('[data-rich]')) {
            return;
        }

        event.preventDefault();
        const text = event.clipboardData.getData('text/plain');
        const isSingleLine = event.target.closest('[data-rich]').dataset.rich === 'single';

        document.execCommand('insertText', false, isSingleLine ? text.replace(/\s*\n\s*/g, ' ') : text);
    }

    /**
     * Recopie la valeur d'un champ dans l'état (le champ dit quel bloc et quelle donnée il modifie).
     */
    updateField(field) {
        this.isDirty = true;
        this.previewer.refresh();

        if (field.name === 'title') {
            this.state.title = field.value;

            return;
        }

        const block = this.state.blocks[Number(field.dataset.block)];

        if (!block) {
            return;
        }

        const index = Number(field.dataset.index);
        const value = field.dataset.rich ? readRichField(field) : field.value;

        switch (field.dataset.field) {
            case 'text':
                block.text = value;
                break;
            case 'item':
                block.items[index] = value;
                break;
            case 'column':
                block.columns[Number(field.dataset.col)].label = value;
                break;
            case 'column-sortable':
                block.columns[Number(field.dataset.col)].sortable = field.checked;
                break;
            case 'table-style':
                block.style = value;
                break;
            case 'cell':
                block.rows[Number(field.dataset.row)][Number(field.dataset.col)] = value;
                break;
            case 'term':
                block.items[index].term = value;
                break;
            case 'definition':
                block.items[index].definition = value;
                break;
            case 'caption':
                block.files[index].caption = value;
                break;
        }
    }

    /**
     * Entrée dans le champ des tags ajoute le tag (et n'envoie pas le formulaire).
     * Entrée dans un autre champ d'une ligne n'envoie pas non plus le formulaire.
     */
    handleKeyDown(event) {
        if (event.key !== 'Enter') {
            return;
        }

        // Texte riche d'une seule ligne (élément de liste, case de tableau) : pas de retour à la ligne
        if (event.target.closest('[data-rich="single"]')) {
            event.preventDefault();

            return;
        }

        if (event.target.tagName !== 'INPUT') {
            return;
        }

        event.preventDefault();

        if (event.target.dataset.field === 'new-tag') {
            this.addTag(event.target);
        }
    }

    addTag(input) {
        const blockIndex = Number(input.dataset.block);
        const block = this.state.blocks[blockIndex];
        const tag = input.value.trim();

        if (tag === '') {
            return;
        }

        const exists = block.tags.some((existing) => existing.label.toLowerCase() === tag.toLowerCase());

        if (!exists) {
            // Un nouveau tag prend la couleur du dernier tag ajouté
            block.tags.push({ label: tag, color: block.tags.at(-1)?.color ?? this.tagColors[0].value });
            this.isDirty = true;
        }

        this.renderAndFocus(blockIndex, '[data-field="new-tag"]');
    }

    /**
     * Ouvre (ou ferme) les pastilles de couleur sous un tag. Ouvrir ne compte pas comme une modification.
     */
    toggleTagColor(blockIndex, tagIndex) {
        const isOpen = this.openTagColor?.block === blockIndex && this.openTagColor?.index === tagIndex;

        if (isOpen) {
            this.closeTagColor();

            return;
        }

        const wasDirty = this.isDirty;
        this.openTagColor = { block: blockIndex, index: tagIndex };
        this.renderAndFocus(blockIndex, '[data-tag-color-popover] [aria-pressed="true"]');
        this.isDirty = wasDirty;
    }

    /**
     * Ferme les pastilles de couleur sans redessiner (le champ en cours de saisie garde le focus).
     *
     * @param {boolean} focusTag remet le focus sur le rond de couleur du tag (Échap)
     */
    closeTagColor(focusTag = true) {
        const { block, index } = this.openTagColor;
        const popover = this.blocksArea.querySelector('[data-tag-color-popover]');

        const colorButton = this.blocksArea.querySelector(`[data-editor-block="${block}"] [data-action="tag-color"][data-index="${index}"]`);

        this.openTagColor = null;
        this.renderer.setOpenTagColor(null);
        popover?.remove();
        colorButton?.setAttribute('aria-expanded', 'false');

        if (focusTag) {
            colorButton?.focus();
        }
    }

    removeColumn(block, columnIndex) {
        if (block.columns.length === 1) {
            this.showError('Un tableau garde au moins une colonne : supprime le bloc s\'il ne sert plus.');

            return;
        }

        block.columns.splice(columnIndex, 1);
        block.rows.forEach((row) => row.splice(columnIndex, 1));
    }

    moveBlock(index, offset) {
        const target = index + offset;

        if (target < 0 || target >= this.state.blocks.length) {
            return;
        }

        const [block] = this.state.blocks.splice(index, 1);
        this.state.blocks.splice(target, 0, block);
    }

    addBlock(type) {
        const position = this.insertAt ?? this.state.blocks.length;

        this.state.blocks.splice(position, 0, createEmptyBlock(type, this.tableStyles[0].value));
        this.isDirty = true;
        this.renderAndFocus(position, 'input, [data-rich], [data-action="add-files"]');
    }

    /**
     * Redessine les blocs puis place le curseur dans un champ du bloc indiqué.
     */
    renderAndFocus(blockIndex, selector) {
        this.isDirty = true;
        this.render();

        const blockElement = this.blocksArea.querySelector(`[data-editor-block="${blockIndex}"]`);
        const fields = blockElement?.querySelectorAll(selector);
        // Le dernier champ correspondant : la ligne, l'élément ou la colonne qu'on vient d'ajouter
        const field = fields?.[fields.length - 1];

        field?.focus();
        blockElement?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    toggleMenu() {
        if (!this.menu.hidden && this.insertAt === null) {
            this.closeMenu();

            return;
        }

        this.closeMenu();
        this.insertAt = null;
        this.menu.hidden = false;
        this.menu.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    /**
     * Le menu s'ouvre juste sous le bouton « insérer un bloc ici » cliqué.
     */
    openMenuAt(position, insertElement) {
        this.closeMenu();
        this.insertAt = position;
        insertElement.after(this.menu);
        this.menu.hidden = false;
        this.menu.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    closeMenu() {
        this.menu.hidden = true;
        this.insertAt = null;

        if (this.menu.parentElement !== this.menuHome) {
            this.menuHome.append(this.menu);
        }
    }

    chooseFiles(block) {
        this.fileBlock = block;
        this.filePicker.open({
            imagesOnly: block.type === 'images',
            excludedIds: block.files.map((file) => file.id),
            onSelect: this.addFiles,
        });
    }

    /**
     * Fichiers choisis dans le panneau : ajoutés au bloc qui l'a ouvert.
     */
    addFiles(files) {
        const block = this.fileBlock;

        for (const file of files) {
            block.files.push({
                id: file.id,
                type: file.type,
                title: file.title,
                extension: file.extension,
                size: file.size,
                viewUrl: file.viewUrl,
                caption: '',
            });
        }

        this.isDirty = true;
        this.render();
    }

    async handleSubmit(event) {
        event.preventDefault();

        const title = this.state.title.trim();

        if (title === '') {
            this.showError('Le titre de la section est obligatoire.');
            this.form.elements.title.focus();

            return;
        }

        const submitButton = this.form.querySelector('[type="submit"]');
        const payload = { title, blocks: this.state.blocks.map(toPayloadBlock) };

        this.showError('');
        submitButton.disabled = true;

        try {
            const result = this.isNew ? await apiPost(this.saveUrl, payload) : await apiPut(this.saveUrl, payload);
            reloadAt(result.url);
        } catch (apiError) {
            this.showError(apiError.message);
            submitButton.disabled = false;
        }
    }

    showError(message) {
        this.error.textContent = message;

        if (message !== '') {
            this.error.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }
}

/**
 * Texte d'un champ riche, avec ses marques de mise en forme. Un champ vidé redevient vraiment vide
 * (le navigateur y laisse parfois un <br>) pour que son texte d'aide réapparaisse.
 */
function readRichField(field) {
    if (field.textContent.trim() === '') {
        field.replaceChildren();

        return '';
    }

    return htmlToMarkup(field);
}

/**
 * Un bloc vide, prêt à remplir, pour chaque type.
 *
 * @param {string} type
 * @param {string} tableStyle style d'un nouveau tableau (le style par défaut)
 */
function createEmptyBlock(type, tableStyle) {
    switch (type) {
        case 'paragraphe':
        case 'sous_titre':
            return { type, text: '' };
        case 'liste':
            return { type, ordered: false, items: [''] };
        case 'tableau':
            return {
                type,
                style: tableStyle,
                columns: [{ label: '', sortable: true }, { label: '', sortable: true }],
                rows: [['', '']],
            };
        case 'tags':
            return { type, tags: [] };
        case 'definitions':
            return { type, items: [{ term: '', definition: '' }] };
        default:
            // images, documents
            return { type, files: [] };
    }
}

/**
 * Ce qu'on envoie à l'API : pour les fichiers, seulement l'id et la légende.
 * Aussi utilisé par ProjectSections.js (retirer une image ou un document depuis la page).
 */
export function toPayloadBlock(block) {
    if (!Array.isArray(block.files)) {
        return block;
    }

    return {
        type: block.type,
        files: block.files.map((file) => ({ id: file.id, caption: file.caption ?? '' })),
    };
}
