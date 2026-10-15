/**
 * Menu de gauche de l'app sur mobile et tablette (900px et moins) : un tiroir qui glisse depuis la gauche.
 *
 * - ouverture : bouton « burger » de la nav du haut [data-app-menu-open] ;
 * - fermeture : croix du menu ou fond sombre [data-app-menu-close], touche Échap, clic sur un lien,
 *   ou passage en grand écran (le menu redevient la colonne habituelle) ;
 * - menu ouvert : la page derrière ne défile plus, le focus va sur la croix ;
 *   à la fermeture, il revient sur le bouton « burger ».
 *
 * HTML : templates/_partials/_header.html.twig (burger), templates/app/_partials/_sidebar.html.twig (croix)
 * et templates/app/base.html.twig (fond sombre). CSS : assets/styles/app.css (« Menu en tiroir »).
 */
const OPEN_CLASS = 'app-layout--menu-open';
const SCROLL_LOCK_CLASS = 'app-menu-open';
const MOBILE_QUERY = '(max-width: 900px)';

export class MobileMenu {
    constructor(layout, openButton) {
        this.layout = layout;
        this.openButton = openButton;
        this.sidebar = layout.querySelector('.app-sidebar');
        this.closeButton = layout.querySelector('.app-sidebar__close');
        this.mobileQuery = window.matchMedia(MOBILE_QUERY);

        this.open = this.open.bind(this);
        this.close = this.close.bind(this);
        this.handleKeydown = this.handleKeydown.bind(this);
        this.handleLinkClick = this.handleLinkClick.bind(this);
        this.handleScreenChange = this.handleScreenChange.bind(this);

        openButton.addEventListener('click', this.open);

        for (const button of layout.querySelectorAll('[data-app-menu-close]')) {
            button.addEventListener('click', this.close);
        }

        this.sidebar.addEventListener('click', this.handleLinkClick);
        this.mobileQuery.addEventListener('change', this.handleScreenChange);
    }

    isOpen() {
        return this.layout.classList.contains(OPEN_CLASS);
    }

    open() {
        this.layout.classList.add(OPEN_CLASS);
        document.documentElement.classList.add(SCROLL_LOCK_CLASS);
        this.openButton.setAttribute('aria-expanded', 'true');
        document.addEventListener('keydown', this.handleKeydown);

        this.closeButton?.focus();
    }

    /**
     * @param {boolean} restoreFocus false quand la fermeture vient d'un changement de taille d'écran
     */
    close(restoreFocus = true) {
        if (!this.isOpen()) {
            return;
        }

        this.layout.classList.remove(OPEN_CLASS);
        document.documentElement.classList.remove(SCROLL_LOCK_CLASS);
        this.openButton.setAttribute('aria-expanded', 'false');
        document.removeEventListener('keydown', this.handleKeydown);

        if (restoreFocus !== false) {
            this.openButton.focus();
        }
    }

    handleKeydown(event) {
        if (event.key === 'Escape') {
            this.close();
        }
    }

    /**
     * Un lien du menu mène à une autre page : on referme le tiroir tout de suite
     * (utile si le navigateur garde la page en cache et qu'on revient en arrière).
     */
    handleLinkClick(event) {
        if (this.isOpen() && event.target.closest('a[href]')) {
            this.close(false);
        }
    }

    handleScreenChange(event) {
        if (!event.matches) {
            this.close(false);
        }
    }
}

export function initMobileMenu() {
    const layout = document.querySelector('.app-layout');
    const openButton = document.querySelector('[data-app-menu-open]');

    if (layout && openButton) {
        new MobileMenu(layout, openButton);
    }
}
