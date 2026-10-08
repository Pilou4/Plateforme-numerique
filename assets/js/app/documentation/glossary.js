// Point d'entrée de la page « Glossaire ».
// Chargé uniquement sur cette page (templates/app/documentation/glossary.html.twig).
import { GlossaryTermEditor } from './GlossaryTermEditor.js';

function initGlossary() {
    const root = document.querySelector('[data-glossary]');

    if (!root) {
        return;
    }

    const glossaryTermEditor = new GlossaryTermEditor(root);
    glossaryTermEditor.init();
}

document.addEventListener('DOMContentLoaded', initGlossary);
