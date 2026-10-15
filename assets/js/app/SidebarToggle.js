/**
 * Bouton qui replie ou déplie le menu de gauche de l'app.
 *
 * - menu replié : une colonne étroite avec le bouton « › » et les icônes des liens
 *   (le libellé s'affiche en infobulle au survol : SidebarTooltip.js) ;
 * - le choix est retenu dans le navigateur (localStorage) et réappliqué sur les autres pages.
 *
 * HTML : templates/app/_partials/_sidebar.html.twig (bouton data-sidebar-toggle).
 */
const COLLAPSED_CLASS = 'app-layout--sidebar-collapsed';
const STORAGE_KEY = 'app-sidebar-collapsed';

export class SidebarToggle {
    constructor(button) {
        this.button = button;
        this.layout = button.closest('.app-layout');

        this.handleClick = this.handleClick.bind(this);
        button.addEventListener('click', this.handleClick);

        this.apply(readStoredState());
    }

    handleClick() {
        const isCollapsed = !this.layout.classList.contains(COLLAPSED_CLASS);

        this.apply(isCollapsed);
        storeState(isCollapsed);
    }

    apply(isCollapsed) {
        this.layout.classList.toggle(COLLAPSED_CLASS, isCollapsed);
        this.button.setAttribute('aria-expanded', String(!isCollapsed));
        this.button.setAttribute('aria-label', isCollapsed ? 'Ouvrir le menu' : 'Fermer le menu');
    }
}

/**
 * Le stockage du navigateur peut être indisponible (navigation privée…) : le menu reste alors ouvert.
 */
function readStoredState() {
    try {
        return window.localStorage.getItem(STORAGE_KEY) === '1';
    } catch {
        return false;
    }
}

function storeState(isCollapsed) {
    try {
        window.localStorage.setItem(STORAGE_KEY, isCollapsed ? '1' : '0');
    } catch {
        // Rien à faire : le choix ne sera simplement pas retenu
    }
}

export function initSidebarToggle() {
    const button = document.querySelector('[data-sidebar-toggle]');

    if (button) {
        new SidebarToggle(button);
    }
}
