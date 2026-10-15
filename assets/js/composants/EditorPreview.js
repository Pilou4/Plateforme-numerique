/**
 * Aperçu à côté d'un formulaire, dans la modal : réutilisable par tout éditeur (sections d'un projet, blog…).
 *
 * Le bouton « Aperçu » agrandit la modal (taille « full ») et affiche l'aperçu à droite du formulaire ;
 * « Quitter l'aperçu » (dans l'aperçu) ou un nouveau clic sur « Aperçu » remet la modal à sa taille.
 * Le formulaire n'est jamais redessiné : rien de ce qui est saisi n'est perdu.
 *
 * HTML attendu, dans le formulaire (classes CSS dans app.css, « Aperçu d'un formulaire ») :
 *      <form class="editor-preview">
 *          … le formulaire, avec un bouton :
 *          <button type="button" class="editor-preview__toggle" data-preview-toggle aria-pressed="false">
 *              <span data-preview-toggle-label>Aperçu</span>
 *          </button>
 *          <aside class="editor-preview__panel" data-preview-panel hidden>
 *              <div class="editor-preview__head">
 *                  <p class="editor-preview__label">Aperçu</p>
 *                  <button type="button" class="editor-preview__close" data-preview-close>Quitter l'aperçu</button>
 *              </div>
 *              <div data-preview-content></div>
 *          </aside>
 *          <div class="modal__actions">…</div>     (boutons en bas, sur toute la largeur)
 *      </form>
 *
 *      const preview = new EditorPreview(form, { render: (content) => …dessine l'aperçu dans content… });
 *      preview.refresh();   à appeler à chaque modification du formulaire (redessine au plus une fois par image)
 */
import { getModal } from './Modal.js';

const OPEN_CLASS = 'editor-preview--open';
const OPEN_SIZE = 'full';

export class EditorPreview {
    /**
     * @param {HTMLElement} root le formulaire
     * @param {object} options
     * @param {function(HTMLElement): void} options.render dessine l'aperçu dans l'élément donné
     * @param {string} [options.size] taille de la modal sans l'aperçu (large par défaut)
     */
    constructor(root, { render, size = 'large' }) {
        this.root = root;
        this.renderContent = render;
        this.closedSize = size;
        this.toggleButton = root.querySelector('[data-preview-toggle]');
        this.panel = root.querySelector('[data-preview-panel]');
        this.content = root.querySelector('[data-preview-content]');
        this.isOpen = false;
        this.frame = null;

        this.handleClick = this.handleClick.bind(this);
        this.draw = this.draw.bind(this);

        root.addEventListener('click', this.handleClick);
    }

    handleClick(event) {
        if (event.target.closest('[data-preview-toggle]')) {
            this.isOpen ? this.close() : this.open();
        } else if (event.target.closest('[data-preview-close]')) {
            this.close();
            this.toggleButton.focus();
        }
    }

    open() {
        this.setOpen(true);
        this.draw();
    }

    close() {
        this.setOpen(false);
    }

    setOpen(isOpen) {
        this.isOpen = isOpen;
        this.root.classList.toggle(OPEN_CLASS, isOpen);
        this.panel.hidden = !isOpen;
        this.toggleButton.setAttribute('aria-pressed', String(isOpen));
        this.toggleButton.querySelector('[data-preview-toggle-label]').textContent = isOpen ? 'Masquer l\'aperçu' : 'Aperçu';
        getModal().setSize(isOpen ? OPEN_SIZE : this.closedSize);
    }

    /**
     * Redessine l'aperçu s'il est affiché (au plus une fois par image : on peut taper vite).
     */
    refresh() {
        if (this.isOpen && this.frame === null) {
            this.frame = window.requestAnimationFrame(this.draw);
        }
    }

    draw() {
        this.frame = null;

        if (this.isOpen) {
            this.renderContent(this.content);
        }
    }
}
