/**
 * En-tête des pages de l'app avec onglets : quand on fait défiler la page, l'en-tête remonte
 * jusqu'à ce qu'il ne reste que la barre d'onglets, qui reste ensuite visible en haut de l'écran.
 *
 * Le CSS (.page-header--sticky dans app.css) colle l'en-tête en haut avec un décalage négatif :
 * --page-header-offset = hauteur de l'en-tête moins hauteur des onglets, calculé ici
 * au chargement et quand la fenêtre change de taille (le titre peut passer sur deux lignes).
 */

const STICKY_CLASS = 'page-header--sticky';
const STUCK_CLASS = 'page-header--stuck';

export class StickyPageHeader {
    constructor(header) {
        this.header = header;
        this.tabs = header.querySelector('.page-tabs');
        this.offset = 0;

        this.updateOffset = this.updateOffset.bind(this);
        this.updateStuckState = this.updateStuckState.bind(this);
    }

    init() {
        this.header.classList.add(STICKY_CLASS);
        this.updateOffset();

        window.addEventListener('resize', this.updateOffset);
        window.addEventListener('scroll', this.updateStuckState, { passive: true });
    }

    updateOffset() {
        this.offset = this.header.offsetHeight - this.tabs.offsetHeight;
        this.header.style.setProperty('--page-header-offset', `${-this.offset}px`);
        this.updateStuckState();
    }

    /**
     * Ombre sous les onglets seulement quand ils sont collés en haut de l'écran.
     */
    updateStuckState() {
        // Hauteur de la nav du haut quand elle est visible (variable gérée par AutoHideHeader.js)
        const siteHeaderHeight = Number.parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--site-header-height')) || 0;
        const stuckTop = siteHeaderHeight - this.offset;
        const isStuck = window.scrollY > 0 && this.header.getBoundingClientRect().top <= stuckTop + 1;

        this.header.classList.toggle(STUCK_CLASS, isStuck);
    }
}

/**
 * Active l'en-tête collant sur la page, si elle a des onglets.
 */
export function initStickyPageHeader() {
    const header = document.querySelector('.page-header--with-tabs');

    if (header?.querySelector('.page-tabs')) {
        new StickyPageHeader(header).init();
    }
}
