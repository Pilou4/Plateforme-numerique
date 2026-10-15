// Point d'entrée JavaScript de l'app (partie connectée).
// Chargé uniquement sur les pages de l'app, via templates/app/base.html.twig.
import './sidebar.js';
import { initDropdownMenus } from '../composants/DropdownMenu.js';
import { initPageFilters } from '../composants/PageFilters.js';
import { initPageSearch } from '../composants/PageSearch.js';
import { initStickyPageHeader } from '../composants/StickyPageHeader.js';
import { initSidebarToggle } from './SidebarToggle.js';
import { initSidebarTooltip } from './SidebarTooltip.js';
import { initMobileMenu } from './MobileMenu.js';

// Bouton qui replie ou déplie le menu de gauche
document.addEventListener('DOMContentLoaded', initSidebarToggle);

// Menu replié : libellé des liens en infobulle au survol
document.addEventListener('DOMContentLoaded', initSidebarTooltip);

// Mobile et tablette : menu de gauche en tiroir, ouvert par le bouton « burger »
document.addEventListener('DOMContentLoaded', initMobileMenu);

// Menus déroulants (bouton « ⋮ » des cartes…)
document.addEventListener('DOMContentLoaded', initDropdownMenus);

// Barre de recherche de l'en-tête des pages (si la page en a une)
document.addEventListener('DOMContentLoaded', initPageSearch);

// Filtres de l'en-tête des pages : listes déroulantes (si la page en a)
document.addEventListener('DOMContentLoaded', initPageFilters);

// Onglets de l'en-tête toujours visibles quand on fait défiler la page (si la page en a)
document.addEventListener('DOMContentLoaded', initStickyPageHeader);
