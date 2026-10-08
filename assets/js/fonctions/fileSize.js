/**
 * Taille d'un fichier lisible : 512 o, 34 Ko, 2,4 Mo.
 */
const UNITS = ['o', 'Ko', 'Mo', 'Go'];
const STEP = 1024;

export function formatFileSize(bytes) {
    let size = Math.max(0, Number(bytes) || 0);
    let unitIndex = 0;

    while (size >= STEP && unitIndex < UNITS.length - 1) {
        size /= STEP;
        unitIndex++;
    }

    // Une décimale seulement en dessous de 10 (2,4 Mo mais 34 Ko)
    const digits = unitIndex > 0 && size < 10 ? 1 : 0;

    return `${size.toLocaleString('fr-FR', { maximumFractionDigits: digits })} ${UNITS[unitIndex]}`;
}
