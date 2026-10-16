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
 *
 * Page rechargée plus bas (le navigateur remet la position) : la nav reprend l'état qu'elle avait
 * (visible ou cachée, gardé pour l'onglet du navigateur dans sessionStorage), et la classe
 * .animations-ready (sur <html>) n'arrive qu'une fois la page affichée. Les animations de position
 * (nav, menu de gauche, onglets, fiche du projet) en dépendent : rien ne « saute » au chargement.
 */

const AUTO_HIDE_CLASS = 'site-header--auto-hide';
const HIDDEN_CLASS = 'site-header--hidden';
// Petits mouvements ignorés (tremblements du pavé tactile, inertie)
const SCROLL_TOLERANCE = 8;
const HEIGHT_VARIABLE = '--site-header-height';
const ANIMATIONS_READY_CLASS = 'animations-ready';
// État et hauteur de la nav, gardés le temps de l'onglet du navigateur (rechargement de la page) ;
// relus avant l'affichage par site-header-state.js
const STORAGE_KEY = 'site-header-hidden';
const HEIGHT_STORAGE_KEY = 'site-header-height';

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
        // Déjà plus bas dans la page (rechargement) : la nav reprend son état d'avant (cachée par défaut)
        if (window.scrollY > this.header.offsetHeight && readHiddenState() !== false) {
            this.hide();
        } else {
            this.show();
        }

        this.header.classList.add(AUTO_HIDE_CLASS);
        // Les animations ne démarrent qu'une fois la page chargée et affichée (les onglets collants ont
        // alors calculé leur position), deux images plus tard
        if (document.readyState === 'complete') {
            scheduleAnimations();
        } else {
            window.addEventListener('load', scheduleAnimations, { once: true });
        }
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
        saveHiddenState(false, this.header.offsetHeight);
    }

    hide() {
        this.header.classList.add(HIDDEN_CLASS);
        document.documentElement.style.setProperty(HEIGHT_VARIABLE, '0px');
        saveHiddenState(true);
    }
}

/**
 * @returns {boolean|null} null si rien n'est gardé (ou stockage indisponible : navigation privée…)
 */
function readHiddenState() {
    try {
        const value = window.sessionStorage.getItem(STORAGE_KEY);

        return value === null ? null : value === 'true';
    } catch {
        return null;
    }
}

function saveHiddenState(hidden, height = null) {
    try {
        window.sessionStorage.setItem(STORAGE_KEY, String(hidden));

        if (height !== null) {
            window.sessionStorage.setItem(HEIGHT_STORAGE_KEY, String(height));
        }
    } catch {
        // Stockage indisponible : la nav fonctionne quand même, sans se souvenir de son état
    }
}

function scheduleAnimations() {
    window.requestAnimationFrame(() => window.requestAnimationFrame(enableAnimations));
}

function enableAnimations() {
    document.documentElement.classList.add(ANIMATIONS_READY_CLASS);
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
