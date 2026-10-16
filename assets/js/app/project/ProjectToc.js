/**
 * Sommaire de la présentation d'un projet (colonne de droite, templates/app/project/_project_identity.html.twig).
 *
 *   [data-project-toc]
 *     [data-toc-item="{id}"]   une ligne ; [data-toc-link] mène à la section #section-{id}
 *
 * - un clic sur un titre fait défiler la page jusqu'à la section (en douceur, sous l'en-tête collant :
 *   voir scroll-margin-top sur .project__section dans app.css) ;
 * - la section en cours de lecture est surlignée dans le sommaire (aria-current="true").
 */
const CURRENT_ATTRIBUTE = 'aria-current';

export class ProjectToc {
    constructor(root) {
        this.root = root;
        this.items = [...root.querySelectorAll('[data-toc-item]')];
        this.sections = this.items
            .map((item) => document.getElementById(`section-${item.dataset.tocItem}`))
            .filter(Boolean);

        this.handleClick = this.handleClick.bind(this);
        this.updateCurrent = this.updateCurrent.bind(this);
        this.frame = null;
        this.requestUpdate = this.requestUpdate.bind(this);
    }

    init() {
        this.root.addEventListener('click', this.handleClick);
        window.addEventListener('scroll', this.requestUpdate, { passive: true });
        window.addEventListener('resize', this.requestUpdate);
        this.updateCurrent();
    }

    handleClick(event) {
        const link = event.target.closest('[data-toc-link]');
        const section = link ? document.querySelector(link.getAttribute('href')) : null;

        if (!section) {
            return;
        }

        event.preventDefault();
        section.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        history.replaceState(null, '', link.getAttribute('href'));
    }

    /**
     * Au plus une mise à jour par image (le défilement envoie beaucoup d'événements).
     */
    requestUpdate() {
        if (this.frame === null) {
            this.frame = window.requestAnimationFrame(this.updateCurrent);
        }
    }

    /**
     * Section en cours : la dernière dont le haut a dépassé le premier tiers de l'écran
     * (la première tant qu'on n'a pas encore défilé jusqu'à elle).
     */
    updateCurrent() {
        this.frame = null;

        const line = window.innerHeight / 3;
        let current = this.sections[0] ?? null;

        for (const section of this.sections) {
            if (section.getBoundingClientRect().top <= line) {
                current = section;
            }
        }

        for (const item of this.items) {
            const isCurrent = current !== null && current.id === `section-${item.dataset.tocItem}`;

            if (isCurrent) {
                item.setAttribute(CURRENT_ATTRIBUTE, 'true');
            } else {
                item.removeAttribute(CURRENT_ATTRIBUTE);
            }
        }
    }
}

export function initProjectToc() {
    const root = document.querySelector('[data-project-toc]');

    if (root) {
        new ProjectToc(root).init();
    }
}
