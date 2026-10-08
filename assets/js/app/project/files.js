// Point d'entrée de la page « Fichiers du projet ».
// Chargé uniquement sur cette page (templates/app/project/files.html.twig).
import { ProjectFileList } from './ProjectFileList.js';

function initProjectFiles() {
    const root = document.querySelector('[data-project-files]');

    if (!root) {
        return;
    }

    const projectFileList = new ProjectFileList(root);
    projectFileList.init();
}

document.addEventListener('DOMContentLoaded', initProjectFiles);
