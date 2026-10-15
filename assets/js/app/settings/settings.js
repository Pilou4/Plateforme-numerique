// Point d'entrée de la page « Paramètres ».
// Chargé uniquement sur cette page (templates/app/settings/index.html.twig).
import { SettingsEditor } from './SettingsEditor.js';

function initSettings() {
    const root = document.querySelector('[data-settings]');

    if (!root) {
        return;
    }

    const settingsEditor = new SettingsEditor(root);
    settingsEditor.init();
}

document.addEventListener('DOMContentLoaded', initSettings);
