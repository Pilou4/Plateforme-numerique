/**
 * Recharge la page à l'adresse donnée, en gardant l'ancre (#section-12…) pour revenir au bon endroit.
 *
 * Sur la même page, changer seulement le « #… » ne recharge pas : on remplace l'adresse puis on recharge
 * (le message de confirmation, enregistré en message flash par le serveur, s'affiche alors en bandeau).
 */
export function reloadAt(url) {
    const target = new URL(url, window.location.href);

    if (target.pathname !== window.location.pathname) {
        window.location.assign(target.href);

        return;
    }

    window.history.replaceState(null, '', target.href);
    window.location.reload();
}
