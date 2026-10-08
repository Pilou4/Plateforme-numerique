// Point d'entrée JavaScript de l'app (partie connectée).
// Chargé uniquement sur les pages de l'app, via templates/app/base.html.twig.
import './sidebar.js';
import { initPageSearch } from '../composants/PageSearch.js';
import { initStickyPageHeader } from '../composants/StickyPageHeader.js';

// Barre de recherche de l'en-tête des pages (si la page en a une)
document.addEventListener('DOMContentLoaded', initPageSearch);

// Onglets de l'en-tête toujours visibles quand on fait défiler la page (si la page en a)
document.addEventListener('DOMContentLoaded', initStickyPageHeader);
