/**
 * Liens vers un autre site : ouverts dans un nouvel onglet, pour que le visiteur ne quitte pas le site.
 *
 * Les liens écrits dans les templates ont déjà target="_blank" ; ce composant s'occupe de tous les autres,
 * en particulier ceux du contenu enregistré en base (tutoriels, glossaire, présentation des projets).
 *
 * Pour chaque lien http(s) dont l'origine (protocole + domaine + port) diffère de celle de la page :
 * - target="_blank" : nouvel onglet ;
 * - rel="noopener noreferrer" : la page ouverte ne peut pas agir sur notre onglet (sécurité) ;
 * - un texte caché « (nouvel onglet) » pour prévenir les lecteurs d'écran.
 *
 * Les liens mailto: et tel: ne sont pas concernés.
 */

const HINT_CLASS = 'external-link__hint';
const HINT_TEXT = '(nouvel onglet)';
const EXTERNAL_PROTOCOLS = ['http:', 'https:'];

export class ExternalLinks {
    constructor(root = document) {
        this.root = root;
    }

    init() {
        for (const link of this.root.querySelectorAll('a[href]')) {
            if (this.isExternal(link)) {
                this.openInNewTab(link);
            }
        }
    }

    isExternal(link) {
        let url;

        try {
            url = new URL(link.getAttribute('href'), window.location.href);
        } catch {
            return false;
        }

        return EXTERNAL_PROTOCOLS.includes(url.protocol) && url.origin !== window.location.origin;
    }

    openInNewTab(link) {
        link.target = '_blank';
        link.rel = 'noopener noreferrer';

        if (!link.querySelector(`.${HINT_CLASS}`)) {
            link.append(createHint());
        }
    }
}

function createHint() {
    const hint = document.createElement('span');

    hint.className = HINT_CLASS;
    hint.textContent = ` ${HINT_TEXT}`;

    return hint;
}

/**
 * Raccourci : traite tous les liens de la page.
 */
export function initExternalLinks() {
    new ExternalLinks().init();
}
