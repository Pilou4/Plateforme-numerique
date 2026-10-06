const SUBMENU_OPEN_CLASS = 'app-sidebar__item--open';

/**
 * Ouvre ou ferme le sous-menu associé au bouton cliqué.
 */
function toggleSubmenu(event) {
    const button = event.currentTarget;
    const item = button.closest('.app-sidebar__item');

    if (!item) {
        return;
    }

    const isOpen = item.classList.toggle(SUBMENU_OPEN_CLASS);

    button.setAttribute('aria-expanded', String(isOpen));
}

/**
 * Branche tous les boutons de sous-menu présents dans le menu de gauche.
 */
function initSubmenus() {
    const buttons = document.querySelectorAll('.app-sidebar__submenu-toggle');

    buttons.forEach((button) => {
        button.addEventListener('click', toggleSubmenu);
    });
}

document.addEventListener('DOMContentLoaded', initSubmenus);
