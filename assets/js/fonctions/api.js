/**
 * Appels à l'API JSON de l'application.
 * Fonctions partagées par toutes les pages qui parlent à l'API.
 */

export class ApiError extends Error {
    constructor(status, payload) {
        super(extractErrorMessage(payload) ?? `Erreur ${status}`);
        this.name = 'ApiError';
        this.status = status;
        this.payload = payload;
    }
}

/**
 * Récupère un message lisible dans une réponse d'erreur de Symfony.
 */
function extractErrorMessage(payload) {
    if (!payload) {
        return null;
    }

    if (Array.isArray(payload.violations) && payload.violations.length > 0) {
        return payload.violations.map(getViolationMessage).join(' ');
    }

    return payload.detail ?? payload.title ?? null;
}

function getViolationMessage(violation) {
    return violation.title ?? violation.message ?? '';
}

async function readJson(response) {
    if (response.status === 204) {
        return null;
    }

    const text = await response.text();

    if (text === '') {
        return null;
    }

    try {
        return JSON.parse(text);
    } catch {
        return null;
    }
}

async function apiRequest(method, url, data = undefined) {
    const options = {
        method,
        headers: { Accept: 'application/json' },
    };

    if (data instanceof FormData) {
        // Formulaire avec fichiers : le navigateur fixe lui-même le Content-Type (multipart/form-data)
        options.body = data;
    } else if (data !== undefined) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(data);
    }

    const response = await fetch(url, options);
    const payload = await readJson(response);

    if (!response.ok) {
        throw new ApiError(response.status, payload);
    }

    return payload;
}

export function apiGet(url) {
    return apiRequest('GET', url);
}

/**
 * data : un objet (envoyé en JSON) ou un FormData (formulaire avec fichiers).
 */
export function apiPost(url, data) {
    return apiRequest('POST', url, data);
}

export function apiPatch(url, data) {
    return apiRequest('PATCH', url, data);
}

export function apiPut(url, data) {
    return apiRequest('PUT', url, data);
}

export function apiDelete(url) {
    return apiRequest('DELETE', url);
}
