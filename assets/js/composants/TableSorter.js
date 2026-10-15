/**
 * Tri d'un tableau au clic sur un en-tête : 1er clic croissant, 2e clic décroissant, et ainsi de suite.
 * Réutilisable pour tout tableau du site ; un seul écouteur sur la page, donc les tableaux ajoutés
 * plus tard (aperçu d'un éditeur…) sont triables sans rien initialiser de plus.
 *
 * HTML attendu (styles dans app.css, « Tri d'un tableau ») :
 *      <table data-table-sort>
 *          <thead><tr>
 *              <th scope="col" aria-sort="none" data-sortable>
 *                  <button type="button" class="table-sort">Nom
 *                      <span class="table-sort__icon" aria-hidden="true"><span class="table-sort__up"></span><span class="table-sort__down"></span></span>
 *                  </button>
 *              </th>
 *              <th scope="col">Colonne non triable</th>
 *          </tr></thead>
 *          <tbody>…</tbody>
 *      </table>
 *
 * Les textes sont comparés en français, sans tenir compte des majuscules, et les nombres comme des nombres
 * (6 < 12 < 135). Les cases vides restent à la fin, dans les deux sens.
 */
const ASCENDING = 'ascending';
const DESCENDING = 'descending';

export class TableSorter {
    constructor(root = document) {
        this.handleClick = this.handleClick.bind(this);
        root.addEventListener('click', this.handleClick);
    }

    handleClick(event) {
        const head = event.target.closest('table[data-table-sort] th[data-sortable]');

        if (!head) {
            return;
        }

        const table = head.closest('table');
        const direction = head.getAttribute('aria-sort') === ASCENDING ? DESCENDING : ASCENDING;

        table.querySelectorAll('th[data-sortable]').forEach((other) => other.setAttribute('aria-sort', 'none'));
        head.setAttribute('aria-sort', direction);
        TableSorter.sort(table, head.cellIndex, direction);
    }

    /**
     * @param {HTMLTableElement} table
     * @param {number} columnIndex
     * @param {string} direction ascending ou descending
     */
    static sort(table, columnIndex, direction) {
        const body = table.tBodies[0];
        const factor = direction === DESCENDING ? -1 : 1;
        const rows = [...body.rows].sort((a, b) => TableSorter.compare(
            TableSorter.cellText(a, columnIndex),
            TableSorter.cellText(b, columnIndex),
            factor,
        ));

        body.append(...rows);
    }

    static cellText(row, columnIndex) {
        return (row.cells[columnIndex]?.textContent ?? '').trim();
    }

    static compare(a, b, factor) {
        if (a === '' || b === '') {
            return (a === '') - (b === '');
        }

        return factor * a.localeCompare(b, 'fr', { numeric: true, sensitivity: 'base' });
    }
}

export function initTableSorter() {
    return new TableSorter(document);
}
