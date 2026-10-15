/**
 * Crée un élément HTML en une ligne (pour les parties de page dessinées en JavaScript).
 * Le texte est toujours inséré en texte brut (jamais interprété comme du HTML).
 *
 *      createElement('button', { className: 'mon-bouton', text: 'Ajouter', attributes: { type: 'button', 'data-action': 'add' } });
 *
 * @param {string} tag
 * @param {object} [options]
 * @param {string} [options.className]
 * @param {string} [options.text]
 * @param {Object<string, string>} [options.attributes]
 * @param {Node[]} [options.children]
 */
export function createElement(tag, { className = '', text = null, attributes = {}, children = [] } = {}) {
    const element = document.createElement(tag);

    if (className) {
        element.className = className;
    }

    if (text !== null) {
        element.textContent = text;
    }

    for (const [name, value] of Object.entries(attributes)) {
        element.setAttribute(name, value);
    }

    element.append(...children);

    return element;
}
