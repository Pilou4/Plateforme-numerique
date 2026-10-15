const STYLE_CLASS_PREFIX = 'section-block__table-wrap--';

/**
 * Page « Éléments » : la liste « Style » change le style du tableau de démonstration
 * (classe section-block__table-wrap--{style}, voir App\Enum\ProjectSectionTableStyle et app.css).
 *
 * HTML attendu : <select data-table-style-select> (valeurs = suffixes CSS) et <div data-table-style-target>.
 */
export class TableStyleDemo {
    constructor(root) {
        this.select = root.querySelector('[data-table-style-select]');
        this.target = root.querySelector('[data-table-style-target]');
        this.handleChange = this.handleChange.bind(this);
    }

    init() {
        if (!this.select || !this.target) {
            return;
        }

        this.select.addEventListener('change', this.handleChange);
    }

    handleChange() {
        const classes = [...this.target.classList].filter((name) => !name.startsWith(STYLE_CLASS_PREFIX));

        this.target.className = [...classes, STYLE_CLASS_PREFIX + this.select.value].join(' ');
    }
}
