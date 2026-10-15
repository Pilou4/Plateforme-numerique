import { createElement } from '../../../fonctions/createElement.js';
import { formatFileSize } from '../../../fonctions/fileSize.js';
import { markupToHtml } from '../../../fonctions/inlineMarkup.js';

/**
 * Aperçu d'une section pendant sa modification : le même affichage que sur la page du projet
 * (templates/app/project/_section_block.html.twig, mêmes classes CSS), dessiné à partir de l'état de l'éditeur.
 * Les blocs encore vides ne s'affichent pas (ils seraient refusés à l'enregistrement).
 */
// Famille d'un document, pour la couleur de son étiquette (comme file_kind() dans App\Twig\ProjectFileExtension)
const DOCUMENT_KINDS = {
    pdf: 'pdf',
    doc: 'word',
    docx: 'word',
    odt: 'word',
    xls: 'excel',
    xlsx: 'excel',
    ods: 'excel',
    csv: 'excel',
    ppt: 'powerpoint',
    pptx: 'powerpoint',
    odp: 'powerpoint',
    txt: 'text',
    md: 'text',
};

export class SectionPreview {
    /**
     * @param {HTMLElement} root zone où l'aperçu est dessiné
     * @param {string} iconsUrl adresse du dossier des icônes
     * @param {{value: string, modifier: string}[]} tableStyles styles d'un tableau (ProjectSectionTableStyle)
     * @param {{value: string, modifier: string}[]} tagColors couleurs d'un tag (ProjectSectionTagColor)
     */
    constructor(root, iconsUrl, tableStyles, tagColors) {
        this.root = root;
        this.tableStyles = tableStyles;
        this.tagColors = tagColors;
        this.iconsUrl = iconsUrl.endsWith('/') ? iconsUrl : `${iconsUrl}/`;
    }

    /**
     * @param {{title: string, blocks: object[]}} state
     */
    render(state) {
        const blocks = state.blocks.flatMap((block) => this.renderBlock(block));

        this.root.replaceChildren(
            createElement('h2', { className: 'section-editor__preview-title', text: state.title.trim() || 'Titre de la section' }),
            blocks.length > 0
                ? createElement('div', { className: 'section-blocks', children: blocks })
                : createElement('p', { className: 'section-blocks__empty', text: 'Ajoute des blocs : ils s\'afficheront ici.' }),
        );
    }

    /**
     * @returns {HTMLElement[]}
     */
    renderBlock(block) {
        switch (block.type) {
            case 'paragraphe':
                return lines(block.text).map((line) => this.rich('p', 'section-block section-block__text', line));
            case 'sous_titre':
                return block.text.trim() ? [createElement('h3', { className: 'section-block section-block__subtitle', text: block.text })] : [];
            case 'liste':
                return this.renderList(block);
            case 'tableau':
                return this.renderTable(block);
            case 'tags':
                return block.tags.length > 0 ? [createElement('ul', {
                    className: 'section-block section-block__tags',
                    children: block.tags.map((tag) => createElement('li', { className: `section-block__tag section-block__tag--${this.tagColorModifier(tag.color)}`, text: tag.label })),
                })] : [];
            case 'definitions':
                return this.renderDefinitions(block);
            case 'images':
                return this.renderImages(block);
            case 'documents':
                return this.renderDocuments(block);
            default:
                return [];
        }
    }

    tagColorModifier(value) {
        return (this.tagColors.find((color) => color.value === value) ?? this.tagColors[0]).modifier;
    }

    renderList(block) {
        const items = block.items.filter((item) => item.trim() !== '');

        if (items.length === 0) {
            return [];
        }

        return [createElement(block.ordered ? 'ol' : 'ul', {
            className: `section-block section-block__list${block.ordered ? ' section-block__list--ordered' : ''}`,
            children: items.map((item) => this.rich('li', 'section-block__list-item', item)),
        })];
    }

    renderTable(block) {
        const rows = block.rows.filter((row) => row.join('').trim() !== '');

        if (rows.length === 0) {
            return [];
        }

        const style = this.tableStyles.find((item) => item.value === block.style) ?? this.tableStyles[0];

        return [createElement('div', {
            className: `section-block section-block__table-wrap section-block__table-wrap--${style.modifier}`,
            children: [createElement('table', {
                className: 'section-block__table',
                attributes: { 'data-table-sort': '' },
                children: [
                    createElement('thead', {
                        className: 'section-block__table-header',
                        children: [createElement('tr', {
                            children: block.columns.map((column) => this.renderTableHead(column)),
                        })],
                    }),
                    createElement('tbody', {
                        children: rows.map((row) => createElement('tr', {
                            className: 'section-block__table-row',
                            children: row.map((text, columnIndex) => this.renderTableCell(text, columnIndex)),
                        })),
                    }),
                ],
            })],
        })];
    }

    /**
     * Une case ; la première est dans une étiquette (mise en forme par le style « Fiche » seulement).
     */
    renderTableCell(text, columnIndex) {
        if (columnIndex > 0 || text.trim() === '') {
            return this.rich('td', 'section-block__table-cell', text);
        }

        return createElement('td', {
            className: 'section-block__table-cell',
            children: [this.rich('span', 'section-block__table-label', text)],
        });
    }

    /**
     * En-tête d'une colonne ; triable : bouton avec les flèches (tri par TableSorter.js, comme sur la page).
     */
    renderTableHead(column) {
        if (!column.sortable) {
            return createElement('th', { className: 'section-block__table-head', text: column.label, attributes: { scope: 'col' } });
        }

        return createElement('th', {
            className: 'section-block__table-head',
            attributes: { scope: 'col', 'aria-sort': 'none', 'data-sortable': '' },
            children: [createElement('button', {
                className: 'table-sort',
                attributes: { type: 'button' },
                children: [
                    document.createTextNode(column.label),
                    createElement('span', {
                        className: 'table-sort__icon',
                        attributes: { 'aria-hidden': 'true' },
                        children: [createElement('span', { className: 'table-sort__up' }), createElement('span', { className: 'table-sort__down' })],
                    }),
                ],
            })],
        });
    }

    renderDefinitions(block) {
        const items = block.items.filter((item) => item.term.trim() !== '' || item.definition.trim() !== '');

        if (items.length === 0) {
            return [];
        }

        return [createElement('dl', {
            className: 'section-block section-block__definitions',
            children: items.map((item) => createElement('div', {
                className: 'section-block__definition',
                children: [
                    createElement('dt', { className: 'section-block__term', text: item.term }),
                    this.rich('dd', 'section-block__description', item.definition),
                ],
            })),
        })];
    }

    renderImages(block) {
        if (block.files.length === 0) {
            return [];
        }

        return [createElement('div', {
            className: `section-block section-block__gallery${block.files.length === 1 ? ' section-block__gallery--single' : ''}`,
            children: block.files.map((file) => createElement('figure', {
                className: 'section-block__figure',
                children: [
                    createElement('img', { className: 'section-block__image', attributes: { src: file.viewUrl, alt: file.caption || file.title } }),
                    ...(file.caption ? [createElement('figcaption', { className: 'section-block__caption', text: file.caption })] : []),
                ],
            })),
        })];
    }

    /**
     * Cartes avec aperçu, comme sur la page (sans le bouton « ouvrir en grand » : on est déjà dans la modal).
     */
    renderDocuments(block) {
        if (block.files.length === 0) {
            return [];
        }

        return [createElement('ul', {
            className: 'section-block section-block__documents',
            children: block.files.map((file) => createElement('li', {
                className: 'section-block__document',
                children: [
                    createElement('div', { className: 'section-block__document-preview', children: [this.renderDocumentPreview(file)] }),
                    createElement('div', {
                        className: 'section-block__document-body',
                        children: [
                            createElement('p', { className: 'section-block__document-title', text: file.caption || file.title }),
                            createElement('p', {
                                className: 'section-block__document-meta',
                                children: [
                                    createElement('span', { text: `${(file.extension ?? '').toUpperCase()} · ${formatFileSize(file.size)}` }),
                                    createElement('span', { className: 'section-block__download', text: 'Télécharger' }),
                                ],
                            }),
                        ],
                    }),
                ],
            })),
        })];
    }

    renderDocumentPreview(file) {
        if (file.preview === 'pdf' || file.preview === 'text') {
            return createElement('iframe', {
                className: 'section-block__document-frame',
                attributes: {
                    // PDF : barre d'outils et volet latéral fermés (Chrome : toolbar, navpanes, view ; Firefox : pagemode, zoom)
                    src: file.preview === 'pdf' ? `${file.viewUrl}#toolbar=0&navpanes=0&view=Fit&pagemode=none&zoom=page-fit` : file.viewUrl,
                    title: `Aperçu de « ${file.title} »`,
                    loading: 'lazy',
                    tabindex: '-1',
                    'aria-hidden': 'true',
                },
            });
        }

        if (file.preview === 'image') {
            return createElement('img', { className: 'section-block__document-image', attributes: { src: file.viewUrl, alt: '', loading: 'lazy' } });
        }

        return createElement('span', {
            className: 'section-block__document-empty',
            children: [
                createElement('span', {
                    className: `section-block__document-badge section-block__document-badge--${DOCUMENT_KINDS[file.extension] ?? 'other'}`,
                    text: (file.extension ?? '?').toUpperCase(),
                }),
                document.createTextNode('Pas d\'aperçu'),
            ],
        });
    }

    /**
     * Un élément dont le texte est mis en forme (gras, italique, code, lien) ; le reste est échappé.
     */
    rich(tag, className, text) {
        const element = createElement(tag, { className });
        element.innerHTML = markupToHtml(text);

        return element;
    }
}

function lines(text) {
    return String(text ?? '').split('\n').map((line) => line.trim()).filter((line) => line !== '');
}
