// Point d'entrée des pages Tutoriels et Tutoriel de la documentation.
// Chargé uniquement sur ces pages (templates/app/documentation/index.html.twig et show.html.twig).
import { TopicEditor } from './TopicEditor.js';
import { initSectionEditor } from '../../composants/SectionEditor.js';

function initDocumentation() {
    const topicEditor = new TopicEditor();
    topicEditor.init();

    // Sections du tutoriel (page d'un tutoriel uniquement)
    initSectionEditor();
}

document.addEventListener('DOMContentLoaded', initDocumentation);
