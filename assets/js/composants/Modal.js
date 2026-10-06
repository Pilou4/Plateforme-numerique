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
 *      data-modal-size="small|medium|large" (facultatif, medium par défaut)
 * 3. Un élément avec data-modal-close dans le contenu ferme la modal.
 *
 * ── Utilisation en JavaScript ──────────────────────────────────────────
 *      import { getModal, openModalFromTemplate } from '…/composants/Modal.js';
 *      openModalFromTemplate('mon-contenu', { title: 'Mon titre', size: 'large' });
 *      getModal().open({ title: 'Info', content: unElementDuDom });
 *      getModal().close();
 *
 * ── Événements (déclenchés sur la modal, remontent jusqu'à document) ──
 *      modal:open  → event.detail = { modal, name }   (name = id du template)
 *      modal:close → event.detail = { modal, name }
 */

const SIZES = ['small', 'medium', 'large'];
const DEFAULT_SIZE = 'medium';
const CLOSING_CLASS = 'modal--closing';
const CLOSE_FALLBACK_DELAY = 300;

export class Modal {
    constructor(dialog) {
        this.dialog = dialog;
        this.box = dialog.querySelector('[data-modal-box]');
        this.title = dialog.querySelector('[data-modal-title]');
        this.body = dialog.querySelector('[data-modal-body]');

        this.name = null;
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
     */
    open({ title = '', content = null, size = DEFAULT_SIZE, name = null } = {}) {
        // Une modal déjà ouverte est remplacée immédiatement
        if (this.isOpen) {
            this.finishClose();
        }

        this.name = name;
        this.setTitle(title);
        this.setContent(content);
        this.setSize(size);

        this.dialog.showModal();
        this.dispatch('modal:open');
    }

    close() {
        if (!this.isOpen || this.isClosing) {
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
        this.dispatch('modal:close');
        this.body.replaceChildren();
        this.name = null;
    }

    setTitle(title) {
        this.title.textContent = title;
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
export function openModalFromTemplate(templateId, { title = '', size = DEFAULT_SIZE } = {}) {
    const template = document.getElementById(templateId);

    if (!(template instanceof HTMLTemplateElement)) {
        console.error(`Modal : aucun <template> avec l'id « ${templateId} ».`);

        return null;
    }

    const modal = getModal();
    modal.open({
        title,
        size,
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
