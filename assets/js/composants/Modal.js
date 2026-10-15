/**
 * Modal réutilisable sur tout le site (public et app).
 *
 * Le HTML de la modal est unique : templates/_partials/_modal.html.twig,
 * inclus une seule fois dans templates/base.html.twig.
 *
 * ── Utilisation sans JavaScript (le plus courant) ──────────────────────
 * 1. Mettre le contenu dans un <template> de la page :
 *      <template id="mon-contenu"> … </template>
 * 2. Ajouter sur un bouton :
 *      data-modal-open="mon-contenu"     (id du template, obligatoire)
 *      data-modal-title="Mon titre"      (facultatif)
 *      data-modal-size="small|medium|large|full" (facultatif, medium par défaut ; full = presque tout l'écran)
 *      data-modal-icon="/images/icones/…svg" (facultatif, icône à gauche du titre)
 * 3. Un élément avec data-modal-close dans le contenu ferme la modal.
 *
 * ── Utilisation en JavaScript ──────────────────────────────────────────
 *      import { getModal, openModalFromTemplate } from '…/composants/Modal.js';
 *      openModalFromTemplate('mon-contenu', { title: 'Mon titre', size: 'large' });
 *      getModal().open({ title: 'Info', content: unElementDuDom });
 *      getModal().close();
 *      getModal().open({ …, beforeClose: () => window.confirm('Fermer ?') });   false = la modal reste ouverte
 *      getModal().setSize('full');                                           change la taille, modal ouverte
 *
 * ── Défilement ──────────────────────────────────────────────────────────
 * Tant que la modal est ouverte, la page derrière ne défile plus (classe modal-open sur <html>) :
 * la molette et le doigt font défiler le contenu de la modal s'il est trop long.
 *
 * ── Événements (déclenchés sur la modal, remontent jusqu'à document) ──
 *      modal:open  → event.detail = { modal, name }   (name = id du template)
 *      modal:close → event.detail = { modal, name }
 */

const SIZES = ['small', 'medium', 'large', 'full'];
const DEFAULT_SIZE = 'medium';
const CLOSING_CLASS = 'modal--closing';
const CLOSE_FALLBACK_DELAY = 300;
const SCROLL_LOCK_CLASS = 'modal-open';
const SCROLLBAR_WIDTH_VARIABLE = '--modal-scrollbar-width';

export class Modal {
    constructor(dialog) {
        this.dialog = dialog;
        this.box = dialog.querySelector('[data-modal-box]');
        this.title = dialog.querySelector('[data-modal-title]');
        this.icon = dialog.querySelector('[data-modal-icon]');
        this.body = dialog.querySelector('[data-modal-body]');

        this.name = null;
        // Appelé avant chaque fermeture (croix, Échap, fond, bouton Annuler) : false empêche la fermeture
        this.beforeClose = null;
        this.isClosing = false;
        this.isPointerDownOnBackdrop = false;
        this.closeTimer = null;

        this.handlePointerDown = this.handlePointerDown.bind(this);
        this.handleClick = this.handleClick.bind(this);
        this.handleCancel = this.handleCancel.bind(this);
        this.finishClose = this.finishClose.bind(this);

        dialog.addEventListener('pointerdown', this.handlePointerDown);
        dialog.addEventListener('click', this.handleClick);
        dialog.addEventListener('cancel', this.handleCancel);
    }

    get isOpen() {
        return this.dialog.open;
    }

    /**
     * @param {object} options
     * @param {string} [options.title]
     * @param {Node|string|null} [options.content] un élément du DOM, ou du texte simple
     * @param {'small'|'medium'|'large'} [options.size]
     * @param {string|null} [options.name] identifiant libre, renvoyé dans les événements
     * @param {string|null} [options.icon] adresse d'une icône affichée à gauche du titre
     * @param {function(): boolean|null} [options.beforeClose] appelé avant la fermeture ; false = la modal reste ouverte
     */
    open({ title = '', content = null, size = DEFAULT_SIZE, name = null, icon = null, beforeClose = null } = {}) {
        // Une modal déjà ouverte est remplacée immédiatement
        if (this.isOpen) {
            this.finishClose();
        }

        this.name = name;
        this.beforeClose = beforeClose;
        this.setTitle(title);
        this.setIcon(icon);
        this.setContent(content);
        this.setSize(size);

        lockPageScroll();
        this.dialog.showModal();
        this.dispatch('modal:open');
    }

    close() {
        if (!this.isOpen || this.isClosing) {
            return;
        }

        if (this.beforeClose && this.beforeClose() === false) {
            return;
        }

        if (prefersReducedMotion()) {
            this.finishClose();

            return;
        }

        // Animation de fermeture, puis fermeture réelle
        this.isClosing = true;
        this.dialog.classList.add(CLOSING_CLASS);
        this.box.addEventListener('animationend', this.finishClose, { once: true });
        this.closeTimer = window.setTimeout(this.finishClose, CLOSE_FALLBACK_DELAY);
    }

    finishClose() {
        window.clearTimeout(this.closeTimer);
        this.box.removeEventListener('animationend', this.finishClose);
        this.isClosing = false;
        this.dialog.classList.remove(CLOSING_CLASS);

        if (!this.isOpen) {
            return;
        }

        this.dialog.close();
        unlockPageScroll();
        this.dispatch('modal:close');
        this.body.replaceChildren();
        this.name = null;
        this.beforeClose = null;
    }

    setTitle(title) {
        this.title.textContent = title;
    }

    setIcon(icon) {
        if (!this.icon) {
            return;
        }

        this.icon.hidden = !icon;
        this.icon.querySelector('img').src = icon ?? '';
    }

    /**
     * Du texte est inséré en texte brut (jamais interprété comme du HTML).
     * Pour du HTML, passer un élément ou le contenu d'un <template>.
     */
    setContent(content) {
        if (content === null || content === undefined) {
            this.body.replaceChildren();
        } else if (typeof content === 'string') {
            this.body.textContent = content;
        } else {
            this.body.replaceChildren(content);
        }
    }

    setSize(size) {
        const validSize = SIZES.includes(size) ? size : DEFAULT_SIZE;

        for (const name of SIZES) {
            this.dialog.classList.toggle(`modal--${name}`, name === validSize);
        }
    }

    getBody() {
        return this.body;
    }

    handlePointerDown(event) {
        // Mémorise si le clic commence sur le fond (et pas dans la boîte),
        // pour ne pas fermer quand on sélectionne du texte en glissant hors de la boîte
        this.isPointerDownOnBackdrop = event.target === this.dialog;
    }

    handleClick(event) {
        const isBackdropClick = event.target === this.dialog && this.isPointerDownOnBackdrop;
        const closeButton = event.target.closest('[data-modal-close]');

        if (isBackdropClick || closeButton) {
            this.close();
        }
    }

    handleCancel(event) {
        // Un champ fichier de la modal envoie lui aussi « cancel » quand on ferme la fenêtre de choix du fichier
        // avec « Annuler » : l'événement remonte jusqu'ici, mais il ne doit pas fermer la modal
        if (event.target !== this.dialog) {
            return;
        }

        // Touche Échap : on remplace la fermeture du navigateur par la nôtre (animée)
        event.preventDefault();
        this.close();
    }

    dispatch(type) {
        this.dialog.dispatchEvent(new CustomEvent(type, {
            bubbles: true,
            detail: { modal: this, name: this.name },
        }));
    }
}

/**
 * Bloque le défilement de la page derrière la modal.
 * La barre de défilement disparaît : sa largeur est gardée en marge (variable CSS)
 * pour que la page ne se décale pas vers la droite à l'ouverture.
 */
function lockPageScroll() {
    const root = document.documentElement;
    const scrollbarWidth = window.innerWidth - root.clientWidth;

    root.style.setProperty(SCROLLBAR_WIDTH_VARIABLE, `${scrollbarWidth}px`);
    root.classList.add(SCROLL_LOCK_CLASS);
}

function unlockPageScroll() {
    const root = document.documentElement;

    root.classList.remove(SCROLL_LOCK_CLASS);
    root.style.removeProperty(SCROLLBAR_WIDTH_VARIABLE);
}

let modalInstance = null;

/**
 * Renvoie la modal unique de la page.
 */
export function getModal() {
    if (!modalInstance) {
        const dialog = document.getElementById('app-modal');

        if (!dialog) {
            throw new Error('La modal est introuvable : vérifier que _modal.html.twig est inclus dans la page.');
        }

        modalInstance = new Modal(dialog);
    }

    return modalInstance;
}

/**
 * Ouvre la modal avec le contenu d'un <template> de la page.
 */
export function openModalFromTemplate(templateId, { title = '', size = DEFAULT_SIZE, icon = null } = {}) {
    const template = document.getElementById(templateId);

    if (!(template instanceof HTMLTemplateElement)) {
        console.error(`Modal : aucun <template> avec l'id « ${templateId} ».`);

        return null;
    }

    const modal = getModal();
    modal.open({
        title,
        size,
        icon,
        content: template.content.cloneNode(true),
        name: templateId,
    });

    return modal;
}

function handleModalTriggerClick(event) {
    const trigger = event.target.closest('[data-modal-open]');

    if (!trigger) {
        return;
    }

    event.preventDefault();
    openModalFromTemplate(trigger.dataset.modalOpen, {
        title: trigger.dataset.modalTitle ?? '',
        size: trigger.dataset.modalSize ?? DEFAULT_SIZE,
        icon: trigger.dataset.modalIcon ?? null,
    });
}

/**
 * Active les boutons data-modal-open de toutes les pages.
 */
export function initModalTriggers() {
    document.addEventListener('click', handleModalTriggerClick);
}

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}
