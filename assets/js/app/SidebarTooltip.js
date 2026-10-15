/**
 * Infobulle du menu de gauche replié.
 *
 * Quand le menu est replié, seules les icônes restent visibles : au survol (ou au focus clavier)
 * d'un lien, son libellé s'affiche dans une infobulle à droite de l'icône.
 *
 * L'infobulle est ajoutée à la fin de <body> (position: fixed) : dans le menu, elle serait coupée
 * par le bord de la colonne (overflow: clip).
 *
 * HTML : templates/app/_partials/_sidebar.html.twig (liens .app-sidebar__link, libellés .app-sidebar__label).
 */
const COLLAPSED_CLASS = 'app-layout--sidebar-collapsed';
const VISIBLE_CLASS = 'app-sidebar-tooltip--visible';
const GAP = 14;
// Le menu replié n'existe que sur grand écran (sur mobile, le menu est un tiroir)
const LARGE_SCREEN_QUERY = '(min-width: 901px)';

export class SidebarTooltip {
    constructor(sidebar) {
        this.layout = sidebar.closest('.app-layout');
        this.links = sidebar.querySelectorAll('.app-sidebar__link');
        this.tooltip = createTooltip();

        this.show = this.show.bind(this);
        this.hide = this.hide.bind(this);

        this.links.forEach(this.listen, this);
    }

    listen(link) {
        link.addEventListener('mouseenter', this.show);
        link.addEventListener('focus', this.show);
        link.addEventListener('mouseleave', this.hide);
        link.addEventListener('blur', this.hide);
    }

    show(event) {
        if (!this.layout?.classList.contains(COLLAPSED_CLASS) || !window.matchMedia(LARGE_SCREEN_QUERY).matches) {
            return;
        }

        const link = event.currentTarget;
        const label = link.querySelector('.app-sidebar__label');
        const box = link.getBoundingClientRect();

        this.tooltip.textContent = label ? label.textContent.trim() : '';
        this.tooltip.style.left = `${box.right + GAP}px`;
        this.tooltip.style.top = `${box.top + box.height / 2}px`;
        this.tooltip.classList.add(VISIBLE_CLASS);
    }

    hide() {
        this.tooltip.classList.remove(VISIBLE_CLASS);
    }
}

/**
 * aria-hidden : le libellé est déjà lu par les lecteurs d'écran dans le lien lui-même.
 */
function createTooltip() {
    const tooltip = document.createElement('div');

    tooltip.className = 'app-sidebar-tooltip';
    tooltip.setAttribute('aria-hidden', 'true');
    document.body.append(tooltip);

    return tooltip;
}

export function initSidebarTooltip() {
    const sidebar = document.querySelector('.app-sidebar');

    if (sidebar) {
        new SidebarTooltip(sidebar);
    }
}
