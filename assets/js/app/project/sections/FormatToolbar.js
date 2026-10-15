/**
 * Barre de mise en forme de l'éditeur de section : B, I, code, lien.
 *
 * Elle agit sur le dernier champ de texte où se trouvait le curseur, dans n'importe quel bloc :
 * le texte sélectionné est entouré des marques de mise en forme (**gras**, *italique*, `code`,
 * [texte](adresse)). Sans sélection, un mot d'exemple est inséré et sélectionné.
 * Les marques sont transformées en HTML par le serveur à l'affichage (App\Service\InlineFormatter).
 */
const MARKS = {
    bold: ['**', '**'],
    italic: ['*', '*'],
    code: ['`', '`'],
};
const PLACEHOLDER = 'texte';
const FORMATTABLE_FIELDS = 'textarea, input[type="text"]:not([data-no-format])';

export class FormatToolbar {
    /**
     * @param {HTMLElement} root l'éditeur : la barre (boutons [data-format]) et les champs sont dedans
     * @param {function(HTMLInputElement|HTMLTextAreaElement): void} onChange appelé après une modification
     */
    constructor(root, onChange) {
        this.root = root;
        this.onChange = onChange;
        this.lastField = null;

        this.handleFocusIn = this.handleFocusIn.bind(this);
        this.handleClick = this.handleClick.bind(this);
        this.handleMouseDown = this.handleMouseDown.bind(this);

        root.addEventListener('focusin', this.handleFocusIn);
        root.addEventListener('mousedown', this.handleMouseDown);
        root.addEventListener('click', this.handleClick);
    }

    handleFocusIn(event) {
        if (event.target.matches(FORMATTABLE_FIELDS)) {
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

        if (!this.lastField || !this.lastField.isConnected) {
            this.lastField = null;
            window.alert('Clique d\'abord dans un champ de texte, puis sélectionne les mots à mettre en forme.');

            return;
        }

        if (button.dataset.format === 'link') {
            this.insertLink();
        } else {
            this.wrap(...MARKS[button.dataset.format]);
        }
    }

    insertLink() {
        const url = window.prompt('Adresse du lien (https://…, ou /… pour une page du site) :', 'https://');

        if (url && url.trim() !== '' && url.trim() !== 'https://') {
            this.wrap('[', `](${url.trim()})`);
        }
    }

    wrap(before, after) {
        const field = this.lastField;
        const start = field.selectionStart;
        const end = field.selectionEnd;
        const selected = field.value.slice(start, end) || PLACEHOLDER;

        field.value = field.value.slice(0, start) + before + selected + after + field.value.slice(end);
        field.focus();
        field.setSelectionRange(start + before.length, start + before.length + selected.length);

        this.onChange(field);
    }
}
