/**
 * Barre d'onglets des pages de l'app (juste après l'en-tête, voir _page_header.html.twig) :
 * elle reste collée en haut de l'écran grâce au CSS seul (.page-tabs--sticky dans app.css),
 * dès l'affichage de la page.
 *
 * Ce script ajoute seulement :
 *   - l'ombre sous les onglets quand ils sont collés (.page-tabs--stuck) ;
 *   - la variable --page-header-visible-height sur <html> : la hauteur des onglets, pour que d'autres
 *     éléments collants se placent juste en dessous (fiche du projet, sections atteintes depuis le sommaire).
 */

const STUCK_CLASS = 'page-tabs--stuck';
const VISIBLE_HEIGHT_VARIABLE = '--page-header-visible-height';

export class StickyPageHeader {
    constructor(tabs) {
        this.tabs = tabs;
        this.frame = null;

        this.updateHeight = this.updateHeight.bind(this);
        this.requestStuckUpdate = this.requestStuckUpdate.bind(this);
        this.updateStuckState = this.updateStuckState.bind(this);
    }

    init() {
        this.updateHeight();

        window.addEventListener('resize', this.updateHeight);
        window.addEventListener('scroll', this.requestStuckUpdate, { passive: true });
    }

    updateHeight() {
        document.documentElement.style.setProperty(VISIBLE_HEIGHT_VARIABLE, `${this.tabs.offsetHeight}px`);
        this.updateStuckState();
    }

    /**
     * Au plus un calcul par image (le défilement envoie beaucoup d'événements).
     */
    requestStuckUpdate() {
        if (this.frame === null) {
            this.frame = window.requestAnimationFrame(this.updateStuckState);
        }
    }

    /**
     * Ombre sous les onglets seulement quand ils sont collés en haut de l'écran.
     */
    updateStuckState() {
        this.frame = null;

        // Hauteur de la nav du haut quand elle est visible (variable gérée par AutoHideHeader.js)
        const siteHeaderHeight = Number.parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--site-header-height')) || 0;
        const isStuck = window.scrollY > 0 && this.tabs.getBoundingClientRect().top <= siteHeaderHeight + 1;

        this.tabs.classList.toggle(STUCK_CLASS, isStuck);
    }
}

/**
 * Active la barre d'onglets collante, si la page en a une.
 */
export function initStickyPageHeader() {
    const tabs = document.querySelector('[data-sticky-tabs]');

    if (tabs) {
        new StickyPageHeader(tabs).init();
    }
}
