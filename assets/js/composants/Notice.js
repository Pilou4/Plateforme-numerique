/**
 * Bandeaux de message réutilisables sur tout le site (public et app).
 *
 * Le HTML de la zone est unique : templates/_partials/_notices.html.twig,
 * inclus une seule fois dans templates/base.html.twig.
 *
 * ── 4 types ────────────────────────────────────────────────────────────
 *      success  → vert    (action réussie)
 *      warning  → orange  (attention, rien n'est cassé)
 *      error    → rouge   (l'action a échoué)
 *      info     → bleu    (information)
 *
 * ── Utilisation en JavaScript ──────────────────────────────────────────
 *      import { showNotice } from '…/composants/Notice.js';
 *      showNotice('success', 'Modification enregistrée.');
 *      showNotice('error', 'Impossible d\'enregistrer.', { duration: 0 }); // reste affiché
 *
 * ── Utilisation en PHP (message affiché sur la page suivante) ──────────
 *      $this->addFlash('success', 'Projet enregistré.');
 *
 * ── Comportement ───────────────────────────────────────────────────────
 * - les bandeaux sont collés sous la nav du haut (.site-header), sur toute la largeur ;
 *   quand on fait défiler la page, ils restent en haut de l'écran ;
 * - le bandeau disparaît seul après quelques secondes (durées ci-dessous),
 *   sauf une erreur, qui reste jusqu'à ce qu'on la ferme ;
 * - le survol de la souris met le compte à rebours en pause ;
 * - le même message déjà affiché n'est pas répété : son compte à rebours repart ;
 * - au-delà de MAX_NOTICES bandeaux, le plus ancien est retiré.
 */

export const NOTICE_TYPES = ['success', 'warning', 'error', 'info'];

// Durée d'affichage en millisecondes (0 = reste affiché jusqu'à la fermeture)
const DURATIONS = {
    success: 4000,
    info: 5000,
    warning: 6000,
    error: 0,
};
const DEFAULT_TYPE = 'info';
const MAX_NOTICES = 4;
const LEAVING_CLASS = 'notice--leaving';
const REPEAT_CLASS = 'notice--repeat';
// Si l'animation de sortie ne se déclenche pas (mouvements réduits…), le bandeau est retiré quand même
const LEAVE_FALLBACK_DELAY = 400;
// Les bandeaux se placent juste sous cet élément
const HEADER_SELECTOR = '.site-header';

export class NoticeArea {
    constructor(container) {
        this.container = container;
        this.template = container.querySelector('[data-notice-template]');
        this.header = document.querySelector(HEADER_SELECTOR);
        // Compte à rebours de chaque bandeau : { timer, remaining, startedAt }
        this.timers = new Map();
        this.isPaused = false;

        this.handleClick = this.handleClick.bind(this);
        this.pauseTimers = this.pauseTimers.bind(this);
        this.resumeTimers = this.resumeTimers.bind(this);
        this.updatePosition = this.updatePosition.bind(this);

        container.addEventListener('click', this.handleClick);
        container.addEventListener('pointerenter', this.pauseTimers);
        container.addEventListener('pointerleave', this.resumeTimers);
        window.addEventListener('scroll', this.updatePosition, { passive: true });
        window.addEventListener('resize', this.updatePosition);
        // Nav du site qui se cache ou réapparaît (AutoHideHeader) : on suit sa position après l'animation
        this.header?.addEventListener('transitionend', this.updatePosition);

        this.updatePosition();
    }

    /**
     * Colle la zone sous la nav : bas du header tant qu'il est visible, sinon haut de l'écran.
     */
    updatePosition() {
        const top = this.header ? Math.max(0, this.header.getBoundingClientRect().bottom) : 0;

        this.container.style.setProperty('--notices-top', `${top}px`);
    }

    /**
     * Bandeaux déjà présents dans le HTML (messages flash envoyés par PHP).
     */
    initExistingNotices() {
        for (const notice of this.getNotices()) {
            this.startTimer(notice, getDuration(notice.dataset.noticeType));
        }
    }

    /**
     * @param {string} type     success | warning | error | info
     * @param {string} message
     * @param {object} [options]
     * @param {number} [options.duration] en millisecondes, 0 = reste affiché
     * @returns {HTMLElement} le bandeau
     */
    show(type, message, { duration } = {}) {
        const noticeType = NOTICE_TYPES.includes(type) ? type : DEFAULT_TYPE;
        const noticeDuration = duration ?? getDuration(noticeType);
        const existing = this.findNotice(noticeType, message);

        if (existing) {
            this.repeat(existing, noticeDuration);

            return existing;
        }

        const notice = this.createNotice(noticeType, message);

        // Le plus récent en haut
        this.container.prepend(notice);
        this.removeOverflow();
        this.startTimer(notice, noticeDuration);

        return notice;
    }

    createNotice(type, message) {
        const notice = this.template.content.firstElementChild.cloneNode(true);

        notice.classList.add(`notice--${type}`);
        notice.dataset.noticeType = type;
        // Une erreur est annoncée tout de suite par les lecteurs d'écran, le reste poliment
        notice.setAttribute('role', type === 'error' ? 'alert' : 'status');
        notice.querySelector('[data-notice-icon]').src = this.container.dataset[`icon${capitalize(type)}`] ?? '';
        notice.querySelector('[data-notice-text]').textContent = message;

        return notice;
    }

    /**
     * Même message déjà affiché : petite animation et le compte à rebours repart.
     */
    repeat(notice, duration) {
        notice.classList.remove(REPEAT_CLASS);
        // Force le navigateur à relancer l'animation
        void notice.offsetWidth;
        notice.classList.add(REPEAT_CLASS);
        this.startTimer(notice, duration);
    }

    dismiss(notice) {
        if (!notice.isConnected || notice.classList.contains(LEAVING_CLASS)) {
            return;
        }

        this.clearTimer(notice);
        notice.classList.add(LEAVING_CLASS);
        notice.addEventListener('animationend', removeNotice, { once: true });
        window.setTimeout(removeNotice, LEAVE_FALLBACK_DELAY, { currentTarget: notice });
    }

    removeOverflow() {
        const notices = this.getNotices().filter(isNotLeaving);

        for (const notice of notices.slice(MAX_NOTICES)) {
            this.dismiss(notice);
        }
    }

    handleClick(event) {
        const closeButton = event.target.closest('[data-notice-close]');

        if (closeButton) {
            this.dismiss(closeButton.closest('[data-notice]'));
        }
    }

    /* ------------------------------------------------------------------
       Compte à rebours (mis en pause au survol)
       ------------------------------------------------------------------ */

    startTimer(notice, duration) {
        this.clearTimer(notice);

        if (duration <= 0) {
            return;
        }

        const state = { timer: null, remaining: duration, startedAt: 0 };
        this.timers.set(notice, state);

        if (!this.isPaused) {
            this.runTimer(notice, state);
        }
    }

    runTimer(notice, state) {
        state.startedAt = Date.now();
        state.timer = window.setTimeout(this.dismiss.bind(this, notice), state.remaining);
    }

    clearTimer(notice) {
        const state = this.timers.get(notice);

        if (state) {
            window.clearTimeout(state.timer);
            this.timers.delete(notice);
        }
    }

    pauseTimers() {
        this.isPaused = true;

        for (const state of this.timers.values()) {
            window.clearTimeout(state.timer);
            state.remaining = Math.max(0, state.remaining - (Date.now() - state.startedAt));
        }
    }

    resumeTimers() {
        this.isPaused = false;

        for (const [notice, state] of this.timers) {
            this.runTimer(notice, state);
        }
    }

    /* ------------------------------------------------------------------
       Outils
       ------------------------------------------------------------------ */

    getNotices() {
        return [...this.container.querySelectorAll('[data-notice]')];
    }

    findNotice(type, message) {
        return this.getNotices().find((notice) => isNotLeaving(notice)
            && notice.dataset.noticeType === type
            && notice.querySelector('[data-notice-text]').textContent === message) ?? null;
    }
}

let noticeArea = null;

/**
 * La zone des bandeaux de la page (créée au premier appel).
 */
export function getNoticeArea() {
    if (!noticeArea) {
        const container = document.querySelector('[data-notices]');

        if (!container) {
            throw new Error('Zone des bandeaux introuvable : _partials/_notices.html.twig doit être inclus dans la page.');
        }

        noticeArea = new NoticeArea(container);
    }

    return noticeArea;
}

/**
 * Raccourci : showNotice('success', 'Modification enregistrée.').
 */
export function showNotice(type, message, options = {}) {
    return getNoticeArea().show(type, message, options);
}

/**
 * Active la zone au chargement de la page (bandeaux envoyés par PHP).
 */
export function initNotices() {
    if (document.querySelector('[data-notices]')) {
        getNoticeArea().initExistingNotices();
    }
}

function getDuration(type) {
    return DURATIONS[type] ?? DURATIONS[DEFAULT_TYPE];
}

function capitalize(text) {
    return text.charAt(0).toUpperCase() + text.slice(1);
}

function isNotLeaving(notice) {
    return !notice.classList.contains(LEAVING_CLASS);
}

function removeNotice(event) {
    event.currentTarget.remove();
}
