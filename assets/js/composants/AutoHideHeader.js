/**
 * Nav du haut (site et app) qui se cache quand on descend dans la page
 * et réapparaît dès qu'on remonte un peu : plus besoin de remonter tout en haut pour naviguer.
 *
 * CSS : .site-header--auto-hide (nav collée en haut) et .site-header--hidden (nav cachée),
 * dans assets/styles/styles.css.
 *
 * La variable CSS --site-header-height (sur <html>) vaut la hauteur de la nav quand elle est visible,
 * 0 quand elle est cachée : les éléments collés en haut de l'écran (menu de gauche et onglets de l'app)
 * s'en servent pour se placer juste en dessous.
 */

const AUTO_HIDE_CLASS = 'site-header--auto-hide';
const HIDDEN_CLASS = 'site-header--hidden';
// Petits mouvements ignorés (tremblements du pavé tactile, inertie)
const SCROLL_TOLERANCE = 8;
const HEIGHT_VARIABLE = '--site-header-height';

export class AutoHideHeader {
    constructor(header) {
        this.header = header;
        this.lastScrollY = window.scrollY;
        this.isTicking = false;

        this.handleScroll = this.handleScroll.bind(this);
        this.update = this.update.bind(this);
        this.show = this.show.bind(this);
    }

    init() {
        this.header.classList.add(AUTO_HIDE_CLASS);
        this.show();
        window.addEventListener('scroll', this.handleScroll, { passive: true });
        // Navigation au clavier : la nav réapparaît dès qu'un de ses liens reçoit le focus
        this.header.addEventListener('focusin', this.show);
    }

    /**
     * Un seul calcul par image affichée, même si le navigateur envoie beaucoup d'événements.
     */
    handleScroll() {
        if (!this.isTicking) {
            this.isTicking = true;
            window.requestAnimationFrame(this.update);
        }
    }

    update() {
        const scrollY = window.scrollY;
        const delta = scrollY - this.lastScrollY;

        this.isTicking = false;

        if (Math.abs(delta) < SCROLL_TOLERANCE) {
            return;
        }

        // En haut de la page, ou en remontant : visible ; en descendant : cachée
        if (scrollY <= this.header.offsetHeight || delta < 0) {
            this.show();
        } else {
            this.hide();
        }

        this.lastScrollY = scrollY;
    }

    show() {
        this.header.classList.remove(HIDDEN_CLASS);
        document.documentElement.style.setProperty(HEIGHT_VARIABLE, `${this.header.offsetHeight}px`);
    }

    hide() {
        this.header.classList.add(HIDDEN_CLASS);
        document.documentElement.style.setProperty(HEIGHT_VARIABLE, '0px');
    }
}

/**
 * Active la nav qui se cache (toutes les pages qui ont la nav du haut).
 */
export function initAutoHideHeader() {
    const header = document.querySelector('.site-header');

    if (header) {
        new AutoHideHeader(header).init();
    }
}
