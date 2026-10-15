import { createElement } from '../../../fonctions/createElement.js';
import { formatFileSize } from '../../../fonctions/fileSize.js';
import { markupToHtml } from '../../../fonctions/inlineMarkup.js';

/**
 * Dessine les champs de l'éditeur pour chaque bloc d'une section (paragraphe, liste, tableau…).
 *
 * Aucun événement n'est posé ici : chaque bouton porte data-action, et chaque champ data-block
 * (numéro du bloc) et data-field (ce qu'il modifie), plus data-index / data-row / data-col si besoin.
 * C'est SectionBlockEditor qui écoute l'éditeur entier et met son état à jour.
 *
 * Les textes qu'on peut mettre en forme (paragraphe, élément de liste, case de tableau, définition)
 * sont des champs « riches » [data-rich] : le gras et l'italique s'y voient directement (FormatToolbar.js).
 * Les autres (titres, en-têtes, tags, légendes) restent des champs texte simples.
 */

const DOCUMENT_ICONS = {
    pdf: 'file-pdf.svg',
    doc: 'file-word.svg',
    docx: 'file-word.svg',
    odt: 'file-word.svg',
    xls: 'file-excel.svg',
    xlsx: 'file-excel.svg',
    ods: 'file-excel.svg',
    csv: 'file-excel.svg',
    ppt: 'file-powerpoint.svg',
    pptx: 'file-powerpoint.svg',
    odp: 'file-powerpoint.svg',
    txt: 'file-text.svg',
    md: 'file-text.svg',
};
const OTHER_DOCUMENT_ICON = 'file-other.svg';

export class BlockFieldsRenderer {
    /**
     * @param {Object<string, {label: string, help: string, icon: string}>} types types de blocs (ProjectSectionBlockType)
     * @param {string} iconsUrl adresse du dossier des icônes (public/images/icones/)
     * @param {{value: string, label: string}[]} tableStyles styles d'un tableau (ProjectSectionTableStyle)
     * @param {{value: string, label: string, modifier: string}[]} tagColors couleurs d'un tag (ProjectSectionTagColor)
     */
    constructor(types, iconsUrl, tableStyles, tagColors) {
        this.types = types;
        this.iconsUrl = iconsUrl.endsWith('/') ? iconsUrl : `${iconsUrl}/`;
        this.tableStyles = tableStyles;
        this.tagColors = tagColors;
        // Tag dont les pastilles de couleur sont ouvertes : { block, index } ou null
        this.openTagColor = null;
    }

    setOpenTagColor(openTagColor) {
        this.openTagColor = openTagColor;
    }

    colorModifier(value) {
        return (this.tagColors.find((color) => color.value === value) ?? this.tagColors[0]).modifier;
    }

    /**
     * La carte d'un bloc : en-tête (type + monter / descendre / supprimer) et champs.
     */
    renderBlock(block, index, count) {
        const type = this.types[block.type];
        const body = createElement('div', { className: 'section-editor__block-body' });
        this.fillBody(body, block, index);

        return createElement('div', {
            className: 'section-editor__block',
            attributes: { 'data-editor-block': String(index) },
            children: [
                createElement('div', {
                    className: 'section-editor__block-head',
                    children: [
                        createElement('span', {
                            className: 'section-editor__block-type',
                            children: [
                                createElement('span', { className: 'section-editor__icon', text: type.icon, attributes: { 'aria-hidden': 'true' } }),
                                document.createTextNode(type.label),
                            ],
                        }),
                        createElement('span', {
                            className: 'section-editor__block-tools',
                            children: [
                                this.iconButton('arrow-up.svg', 'Monter le bloc', { 'data-action': 'move-block', 'data-block': index, 'data-direction': 'up' }, index === 0),
                                this.iconButton('arrow-down.svg', 'Descendre le bloc', { 'data-action': 'move-block', 'data-block': index, 'data-direction': 'down' }, index === count - 1),
                                this.iconButton('delete.svg', 'Supprimer le bloc', { 'data-action': 'delete-block', 'data-block': index }, false, 'section-editor__tool--delete'),
                            ],
                        }),
                    ],
                }),
                body,
            ],
        });
    }

    /**
     * Le petit bouton « insérer un bloc ici », entre deux blocs.
     */
    renderInsert(index) {
        return createElement('div', {
            className: 'section-editor__insert',
            children: [
                createElement('button', {
                    className: 'section-editor__insert-button',
                    text: '+ Insérer un bloc ici',
                    attributes: { type: 'button', 'data-action': 'insert-block', 'data-block': index },
                }),
            ],
        });
    }

    fillBody(body, block, index) {
        switch (block.type) {
            case 'paragraphe':
                body.append(
                    this.rich(index, 'text', block.text, 'Écris ton texte…', { multiLine: true }),
                    this.hint('Entrée : nouveau paragraphe. Sélectionne des mots puis utilise la barre B / I / code / lien.'),
                );
                break;
            case 'sous_titre':
                body.append(this.input(index, 'text', block.text, 'Ex. : Les tables principales', { maxlength: '255' }));
                break;
            case 'liste':
                this.fillList(body, block, index);
                break;
            case 'tableau':
                this.fillTable(body, block, index);
                break;
            case 'tags':
                this.fillTags(body, block, index);
                break;
            case 'definitions':
                this.fillDefinitions(body, block, index);
                break;
            case 'images':
                this.fillImages(body, block, index);
                break;
            case 'documents':
                this.fillDocuments(body, block, index);
                break;
        }
    }

    fillList(body, block, index) {
        body.append(createElement('div', {
            className: 'section-editor__switch',
            attributes: { role: 'group', 'aria-label': 'Type de liste' },
            children: [
                this.switchButton('• À puces', !block.ordered, { 'data-action': 'list-ordered', 'data-block': index, 'data-value': 'false' }),
                this.switchButton('1. Numérotée', block.ordered, { 'data-action': 'list-ordered', 'data-block': index, 'data-value': 'true' }),
            ],
        }));

        body.append(createElement('div', {
            className: 'section-editor__rows',
            children: block.items.map((item, itemIndex) => createElement('div', {
                className: 'section-editor__row',
                children: [
                    createElement('span', { className: 'section-editor__row-mark', text: block.ordered ? `${itemIndex + 1}.` : '•', attributes: { 'aria-hidden': 'true' } }),
                    this.rich(index, 'item', item, 'Élément de la liste', { attributes: { 'data-index': itemIndex } }),
                    this.removeButton('Retirer cet élément', { 'data-action': 'remove-item', 'data-block': index, 'data-index': itemIndex }),
                ],
            })),
        }));

        body.append(this.addLine('+ Ajouter un élément', { 'data-action': 'add-item', 'data-block': index }));
    }

    /**
     * Le style du tableau, puis une grille de champs : la première ligne contient les en-têtes (<th>)
     * avec l'interrupteur « Triable » de chaque colonne, les suivantes les cases (<td>).
     */
    fillTable(body, block, index) {
        body.append(this.tableStyleField(block, index));

        const headCells = block.columns.map((column, columnIndex) => createElement('th', {
            className: 'section-editor__grid-cell',
            attributes: { scope: 'col' },
            children: [createElement('div', {
                className: 'section-editor__grid-head',
                children: [
                    this.input(index, 'column', column.label, 'En-tête', { 'data-col': columnIndex, 'aria-label': `En-tête de la colonne ${columnIndex + 1}`, maxlength: '255' }),
                    this.sortableSwitch(index, columnIndex, column.sortable),
                ],
            })],
        }));

        const rows = block.rows.map((row, rowIndex) => createElement('tr', {
            children: [
                createElement('td', {
                    className: 'section-editor__grid-cell',
                    children: [this.removeButton('Supprimer cette ligne', { 'data-action': 'remove-row', 'data-block': index, 'data-row': rowIndex })],
                }),
                ...row.map((text, columnIndex) => createElement('td', {
                    className: 'section-editor__grid-cell',
                    children: [this.rich(index, 'cell', text, 'Case', { attributes: { 'data-row': rowIndex, 'data-col': columnIndex, 'aria-label': `Ligne ${rowIndex + 1}, colonne ${columnIndex + 1}` } })],
                })),
            ],
        }));

        const removeColumns = block.columns.map((column, columnIndex) => createElement('td', {
            className: 'section-editor__grid-cell',
            children: [createElement('button', {
                className: 'section-editor__remove-column',
                text: 'Retirer la colonne',
                attributes: { type: 'button', 'data-action': 'remove-column', 'data-block': index, 'data-col': columnIndex },
            })],
        }));

        body.append(createElement('div', {
            className: 'section-editor__grid-wrap',
            children: [createElement('table', {
                className: 'section-editor__grid',
                children: [
                    createElement('thead', { children: [createElement('tr', { children: [createElement('td'), ...headCells] })] }),
                    createElement('tbody', { children: rows }),
                    createElement('tfoot', { children: [createElement('tr', { children: [createElement('td'), ...removeColumns] })] }),
                ],
            })],
        }));

        body.append(createElement('div', {
            className: 'section-editor__line-actions',
            children: [
                this.addLine('+ Ajouter une ligne', { 'data-action': 'add-row', 'data-block': index }),
                this.addLine('+ Ajouter une colonne', { 'data-action': 'add-column', 'data-block': index }),
            ],
        }));

        body.append(this.hint('La première ligne (en bleu) contient les en-têtes du tableau. Triable : on peut trier le tableau en cliquant sur l\'en-tête. Les lignes vides sont ignorées.'));
    }

    /**
     * Les 9 pastilles de couleur sous un tag ; la couleur actuelle est cochée.
     */
    tagColorPopover(tag, blockIndex, tagIndex) {
        return createElement('span', {
            className: 'section-editor__color-popover',
            attributes: { role: 'group', 'aria-label': `Couleur de « ${tag.label} »`, 'data-tag-color-popover': '' },
            children: this.tagColors.map((color) => createElement('button', {
                className: `section-editor__swatch section-editor__swatch--${color.modifier}`,
                attributes: {
                    type: 'button',
                    title: color.label,
                    'aria-label': color.label,
                    'aria-pressed': color.value === tag.color ? 'true' : 'false',
                    'data-action': 'set-tag-color',
                    'data-block': blockIndex,
                    'data-index': tagIndex,
                    'data-color': color.value,
                },
            })),
        });
    }

    /**
     * Liste « Style » du tableau (l'aperçu permet de voir le résultat).
     */
    tableStyleField(block, index) {
        const id = `section-editor-table-style-${index}`;
        const select = createElement('select', {
            className: 'section-editor__select',
            attributes: { id, 'data-block': index, 'data-field': 'table-style' },
            children: this.tableStyles.map((style) => createElement('option', { text: style.label, attributes: { value: style.value } })),
        });
        select.value = block.style;

        return createElement('div', {
            className: 'section-editor__table-style',
            children: [
                createElement('label', { className: 'section-editor__table-style-label', text: 'Style', attributes: { for: id } }),
                select,
            ],
        });
    }

    /**
     * Interrupteur « Triable / Non triable » sous l'en-tête d'une colonne (le texte suit l'état, en CSS).
     */
    sortableSwitch(blockIndex, columnIndex, sortable) {
        const checkbox = createElement('input', {
            className: 'section-editor__toggle-input',
            attributes: { type: 'checkbox', 'data-block': blockIndex, 'data-field': 'column-sortable', 'data-col': columnIndex },
        });
        checkbox.checked = sortable;

        return createElement('label', {
            className: 'section-editor__toggle',
            attributes: { title: 'Trier le tableau en cliquant sur cet en-tête' },
            children: [
                checkbox,
                createElement('span', { className: 'section-editor__toggle-track', attributes: { 'aria-hidden': 'true' } }),
                createElement('span', { className: 'section-editor__toggle-text section-editor__toggle-text--on', text: 'Triable' }),
                createElement('span', { className: 'section-editor__toggle-text section-editor__toggle-text--off', text: 'Non triable' }),
            ],
        });
    }

    /**
     * Les tags en pastilles de leur couleur : le petit rond ouvre le choix de la couleur (9 pastilles).
     */
    fillTags(body, block, index) {
        const chips = block.tags.map((tag, tagIndex) => {
            const isOpen = this.openTagColor?.block === index && this.openTagColor?.index === tagIndex;

            return createElement('span', {
                className: `section-editor__chip section-editor__chip--${this.colorModifier(tag.color)}`,
                children: [
                    createElement('button', {
                        className: 'section-editor__chip-color',
                        attributes: {
                            type: 'button',
                            title: 'Changer la couleur',
                            'aria-label': `Couleur de « ${tag.label} »`,
                            'aria-expanded': isOpen ? 'true' : 'false',
                            'data-action': 'tag-color',
                            'data-block': index,
                            'data-index': tagIndex,
                        },
                    }),
                    document.createTextNode(tag.label),
                    createElement('button', {
                        className: 'section-editor__chip-remove',
                        text: '×',
                        attributes: { type: 'button', title: `Retirer « ${tag.label} »`, 'aria-label': `Retirer « ${tag.label} »`, 'data-action': 'remove-tag', 'data-block': index, 'data-index': tagIndex },
                    }),
                    ...(isOpen ? [this.tagColorPopover(tag, index, tagIndex)] : []),
                ],
            });
        });

        body.append(createElement('div', {
            className: 'section-editor__chips',
            children: [
                ...chips,
                createElement('input', {
                    className: 'section-editor__chip-input',
                    attributes: {
                        type: 'text',
                        placeholder: 'Ajouter un tag puis Entrée',
                        maxlength: '100',
                        'aria-label': 'Nouveau tag',
                        'data-block': index,
                        'data-field': 'new-tag',
                    },
                }),
            ],
        }));
    }

    fillDefinitions(body, block, index) {
        body.append(createElement('div', {
            className: 'section-editor__rows',
            children: block.items.map((item, itemIndex) => createElement('div', {
                className: 'section-editor__definition',
                children: [
                    this.input(index, 'term', item.term, 'Terme', { 'data-index': itemIndex, maxlength: '255' }),
                    this.rich(index, 'definition', item.definition, 'Définition', { multiLine: true, attributes: { 'data-index': itemIndex } }),
                    this.removeButton('Retirer cette définition', { 'data-action': 'remove-item', 'data-block': index, 'data-index': itemIndex }),
                ],
            })),
        }));

        body.append(this.addLine('+ Ajouter une définition', { 'data-action': 'add-item', 'data-block': index }));
    }

    fillImages(body, block, index) {
        const cards = block.files.map((file, fileIndex) => createElement('div', {
            className: 'section-editor__file-card',
            children: [
                createElement('img', { className: 'section-editor__file-image', attributes: { src: file.viewUrl, alt: '', loading: 'lazy' } }),
                createElement('div', {
                    className: 'section-editor__file-body',
                    children: [
                        createElement('span', {
                            className: 'section-editor__file-name',
                            children: [
                                createElement('span', { className: 'section-editor__file-title', text: file.title }),
                                this.removeButton('Retirer du bloc (le fichier reste dans Fichiers)', { 'data-action': 'remove-file', 'data-block': index, 'data-index': fileIndex }),
                            ],
                        }),
                        this.input(index, 'caption', file.caption ?? '', 'Légende (facultative)', { 'data-index': fileIndex, maxlength: '255' }),
                    ],
                }),
            ],
        }));

        body.append(createElement('div', {
            className: 'section-editor__files',
            children: [
                ...cards,
                createElement('button', {
                    className: 'section-editor__file-add',
                    attributes: { type: 'button', 'data-action': 'add-files', 'data-block': index },
                    children: [
                        createElement('span', { className: 'section-editor__file-add-title', text: '+ Ajouter des images' }),
                        createElement('span', { className: 'section-editor__file-add-help', text: 'Choisir dans les Fichiers du projet ou en envoyer une nouvelle' }),
                    ],
                }),
            ],
        }));
    }

    fillDocuments(body, block, index) {
        body.append(createElement('div', {
            className: 'section-editor__documents',
            children: block.files.map((file, fileIndex) => createElement('div', {
                className: 'section-editor__document',
                children: [
                    createElement('img', {
                        className: 'section-editor__document-icon',
                        attributes: { src: this.iconsUrl + (DOCUMENT_ICONS[file.extension] ?? OTHER_DOCUMENT_ICON), alt: '', width: '28', height: '28' },
                    }),
                    createElement('span', {
                        className: 'section-editor__document-text',
                        children: [
                            createElement('span', { className: 'section-editor__document-title', text: file.title }),
                            createElement('span', { className: 'section-editor__document-meta', text: `${(file.extension ?? '').toUpperCase()} · ${formatFileSize(file.size)}` }),
                        ],
                    }),
                    this.input(index, 'caption', file.caption ?? '', 'Nom affiché (facultatif)', { 'data-index': fileIndex, maxlength: '255' }),
                    this.removeButton('Retirer du bloc (le fichier reste dans Fichiers)', { 'data-action': 'remove-file', 'data-block': index, 'data-index': fileIndex }),
                ],
            })),
        }));

        body.append(this.addLine('+ Ajouter des documents (depuis les Fichiers ou nouveau)', { 'data-action': 'add-files', 'data-block': index }));
    }

    input(blockIndex, field, value, placeholder, attributes = {}) {
        const input = createElement('input', {
            className: 'section-editor__input',
            attributes: { type: 'text', placeholder, 'data-block': blockIndex, 'data-field': field, ...attributes },
        });
        input.value = value ?? '';

        return input;
    }

    /**
     * Champ riche : le texte est affiché mis en forme (gras, italique, code, lien).
     * multiLine : Entrée crée une nouvelle ligne (paragraphe, définition) ; sinon Entrée est ignorée.
     */
    rich(blockIndex, field, value, placeholder, { multiLine = false, attributes = {} } = {}) {
        const element = createElement('div', {
            className: `section-editor__rich${multiLine ? ' section-editor__rich--multiline' : ''}`,
            attributes: {
                contenteditable: 'true',
                role: 'textbox',
                'aria-multiline': multiLine ? 'true' : 'false',
                'aria-label': placeholder,
                'data-placeholder': placeholder,
                'data-rich': multiLine ? 'multiline' : 'single',
                'data-block': blockIndex,
                'data-field': field,
                ...attributes,
            },
        });

        // Une ligne par paragraphe : chaque ligne du texte devient un bloc <div> dans le champ
        const lines = String(value ?? '').split('\n');
        element.innerHTML = multiLine && lines.length > 1
            ? lines.map((line) => `<div>${markupToHtml(line) || '<br>'}</div>`).join('')
            : markupToHtml(value ?? '');

        return element;
    }

    hint(text) {
        return createElement('p', { className: 'section-editor__hint', text });
    }

    addLine(text, attributes) {
        return createElement('button', { className: 'section-editor__add-line', text, attributes: { type: 'button', ...attributes } });
    }

    switchButton(text, pressed, attributes) {
        return createElement('button', {
            className: 'section-editor__switch-button',
            text,
            attributes: { type: 'button', 'aria-pressed': pressed ? 'true' : 'false', ...attributes },
        });
    }

    removeButton(label, attributes) {
        return createElement('button', {
            className: 'section-editor__remove',
            attributes: { type: 'button', title: label, 'aria-label': label, ...attributes },
            children: [createElement('img', { attributes: { src: `${this.iconsUrl}close.svg`, alt: '', width: '14', height: '14' } })],
        });
    }

    iconButton(icon, label, attributes, disabled, modifier = '') {
        const button = createElement('button', {
            className: `section-editor__tool ${modifier}`.trim(),
            attributes: { type: 'button', title: label, 'aria-label': label, ...attributes },
            children: [createElement('img', { attributes: { src: this.iconsUrl + icon, alt: '', width: '16', height: '16' } })],
        });
        button.disabled = disabled;

        return button;
    }
}
