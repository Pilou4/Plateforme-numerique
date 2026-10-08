import { getModal } from '../../composants/Modal.js';

/**
 * Page « Éléments » : un clic sur une icône l'ouvre dans la modal,
 * en grand, sur fond sombre et à sa taille réelle.
 */
export class IconViewer {
    constructor(root) {
        this.root = root;
        this.template = root.querySelector('[data-icon-viewer]');

        this.handleClick = this.handleClick.bind(this);
    }

    init() {
        this.root.addEventListener('click', this.handleClick);
    }

    handleClick(event) {
        const button = event.target.closest('[data-icon-preview]');

        if (button) {
            this.open(button.dataset.iconName, button.dataset.iconUrl);
        }
    }

    open(name, url) {
        const viewer = this.template.content.firstElementChild.cloneNode(true);

        for (const image of viewer.querySelectorAll('[data-icon-image]')) {
            image.src = url;
            image.alt = name;
        }

        viewer.querySelector('[data-icon-path]').textContent = `public/images/icones/${name}`;
        viewer.querySelector('[data-icon-natural]').addEventListener('load', showNaturalSize, { once: true });

        getModal().open({ title: name, content: viewer, size: 'medium' });
    }
}

/**
 * Affiche la taille réelle de l'icône (attributs width/height du SVG) une fois l'image chargée.
 */
function showNaturalSize(event) {
    const image = event.currentTarget;
    const caption = image.closest('figure').querySelector('[data-icon-natural-caption]');

    caption.textContent = `Taille réelle (${image.naturalWidth} × ${image.naturalHeight} px)`;
}
