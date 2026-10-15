// Point d'entrée de la page « Éléments ».
// Chargé uniquement sur cette page (templates/app/elements/index.html.twig).
import { initTableSorter } from '../../composants/TableSorter.js';
import { IconViewer } from './IconViewer.js';
import { TableStyleDemo } from './TableStyleDemo.js';

function initElements() {
    const root = document.querySelector('[data-elements]');

    if (!root) {
        return;
    }

    const iconViewer = new IconViewer(root);
    iconViewer.init();

    // Tableau : choix du style et tri au clic sur les en-têtes
    new TableStyleDemo(root).init();
    initTableSorter();
}

document.addEventListener('DOMContentLoaded', initElements);
