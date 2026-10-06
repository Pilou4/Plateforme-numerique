// Point d'entrée de la page « Tâches du projet ».
// Chargé uniquement sur cette page (templates/app/project/steps.html.twig).
import { ProjectStepList } from './ProjectStepList.js';

function initProjectSteps() {
    const root = document.querySelector('[data-project-steps]');

    if (!root) {
        return;
    }

    const projectStepList = new ProjectStepList(root);
    projectStepList.init();
}

document.addEventListener('DOMContentLoaded', initProjectSteps);
