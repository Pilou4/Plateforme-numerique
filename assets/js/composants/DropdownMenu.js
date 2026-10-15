/**
 * Menus déroulants (ex. : bouton « ⋮ » d'une carte de projet), sur toutes les pages.
 *
 * HTML :
 *      <button type="button" data-menu-toggle aria-expanded="false" aria-controls="menu-12">…</button>
 *      <div class="… " id="menu-12" data-menu hidden>
 *          <a href="…">…</a>  <button type="button">…</button>
 *      </div>
 *
 * - un clic sur le bouton ouvre ou ferme le menu (un seul menu ouvert à la fois) ;
 * - un clic ailleurs, sur un choix du menu, ou la touche Échap le ferme ;
 * - avec Échap, le focus revient sur le bouton.
 */
export class DropdownMenu {
    constructor(root = document) {
        this.root = root;
        this.openToggle = null;

        this.handleClick = this.handleClick.bind(this);
        this.handleKeyDown = this.handleKeyDown.bind(this);

        root.addEventListener('click', this.handleClick);
        root.addEventListener('keydown', this.handleKeyDown);
    }

    handleClick(event) {
        const toggle = event.target.closest('[data-menu-toggle]');

        if (toggle) {
            this.toggle(toggle);

            return;
        }

        // Clic ailleurs ou sur un choix du menu : on ferme
        if (this.openToggle) {
            this.close();
        }
    }

    handleKeyDown(event) {
        if (event.key === 'Escape' && this.openToggle) {
            const toggle = this.openToggle;

            this.close();
            toggle.focus();
        }
    }

    toggle(toggle) {
        const isSameMenu = toggle === this.openToggle;

        this.close();

        if (!isSameMenu) {
            this.open(toggle);
        }
    }

    open(toggle) {
        const menu = getMenu(toggle);

        if (!menu) {
            return;
        }

        menu.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        this.openToggle = toggle;
        menu.querySelector('a, button')?.focus();
    }

    close() {
        if (!this.openToggle) {
            return;
        }

        const menu = getMenu(this.openToggle);

        if (menu) {
            menu.hidden = true;
        }

        this.openToggle.setAttribute('aria-expanded', 'false');
        this.openToggle = null;
    }
}

function getMenu(toggle) {
    return document.getElementById(toggle.getAttribute('aria-controls'));
}

/**
 * Active les menus déroulants de la page.
 */
export function initDropdownMenus() {
    new DropdownMenu();
}
