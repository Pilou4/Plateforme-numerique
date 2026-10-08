// Point d'entrée de la page « Éléments ».
// Chargé uniquement sur cette page (templates/app/elements/index.html.twig).
import { IconViewer } from './IconViewer.js';

function initElements() {
    const root = document.querySelector('[data-elements]');

    if (!root) {
        return;
    }

    const iconViewer = new IconViewer(root);
    iconViewer.init();
}

document.addEventListener('DOMContentLoaded', initElements);
