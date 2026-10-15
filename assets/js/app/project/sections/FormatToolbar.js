import { isAllowedLink } from '../../../fonctions/inlineMarkup.js';

/**
 * Barre de mise en forme de l'éditeur de section : gras, italique, code, lien.
 *
 * Elle agit sur le texte sélectionné dans les champs « riches » [data-rich] (paragraphes, éléments de liste,
 * cases de tableau, définitions) : on voit tout de suite le gras ou l'italique, comme dans un traitement
 * de texte. Les raccourcis Ctrl + B et Ctrl + I marchent aussi. Le texte est enregistré avec des marques
 * simples (voir assets/js/fonctions/inlineMarkup.js).
 */
const CODE_TAG = 'CODE';

export class FormatToolbar {
    /**
     * @param {HTMLElement} root l'éditeur : la barre (boutons [data-format]) et les champs sont dedans
     * @param {function(HTMLElement): void} onChange appelé avec le champ modifié
     */
    constructor(root, onChange) {
        this.root = root;
        this.onChange = onChange;
        this.lastField = null;

        this.handleFocusIn = this.handleFocusIn.bind(this);
        this.handleMouseDown = this.handleMouseDown.bind(this);
        this.handleClick = this.handleClick.bind(this);

        root.addEventListener('focusin', this.handleFocusIn);
        root.addEventListener('mousedown', this.handleMouseDown);
        root.addEventListener('click', this.handleClick);
    }

    handleFocusIn(event) {
        if (event.target.matches('[data-rich]')) {
            this.lastField = event.target;
        }
    }

    /**
     * Cliquer sur un bouton de la barre ne doit pas faire perdre la sélection du champ.
     */
    handleMouseDown(event) {
        if (event.target.closest('[data-format]')) {
            event.preventDefault();
        }
    }

    handleClick(event) {
        const button = event.target.closest('[data-format]');

        if (!button) {
            return;
        }

        const selection = window.getSelection();
        const field = this.getSelectedField(selection);

        if (!field) {
            window.alert('Sélectionne d\'abord des mots dans un texte (paragraphe, liste, tableau ou définition).');

            return;
        }

        switch (button.dataset.format) {
            case 'bold':
                document.execCommand('bold');
                break;
            case 'italic':
                document.execCommand('italic');
                break;
            case 'code':
                this.toggleCode(selection);
                break;
            case 'link':
                this.insertLink();
                break;
        }

        this.onChange(field);
    }

    /**
     * Le champ riche qui contient la sélection (ou le dernier champ utilisé, s'il a encore le curseur).
     */
    getSelectedField(selection) {
        const node = selection.rangeCount > 0 ? selection.anchorNode : null;
        const element = node?.nodeType === Node.ELEMENT_NODE ? node : node?.parentElement;
        const field = element?.closest('[data-rich]');

        return field && this.root.contains(field) && !selection.isCollapsed ? field : null;
    }

    /**
     * Met le texte sélectionné en code, ou le remet en texte normal s'il l'était déjà.
     */
    toggleCode(selection) {
        const range = selection.getRangeAt(0);
        const container = range.commonAncestorContainer;
        const existing = (container.nodeType === Node.ELEMENT_NODE ? container : container.parentElement).closest(CODE_TAG);

        if (existing) {
            existing.replaceWith(document.createTextNode(existing.textContent));

            return;
        }

        const code = document.createElement('code');
        code.className = 'inline-format__code';
        code.textContent = range.toString();
        range.deleteContents();
        range.insertNode(code);
        selection.removeAllRanges();
    }

    insertLink() {
        const url = window.prompt('Adresse du lien (https://…, ou /… pour une page du site) :', 'https://');

        if (url === null || url.trim() === '' || url.trim() === 'https://') {
            return;
        }

        if (!isAllowedLink(url)) {
            window.alert('Adresse refusée : elle doit commencer par https://, http://, mailto:, / ou #.');

            return;
        }

        document.execCommand('createLink', false, url.trim());
    }
}
