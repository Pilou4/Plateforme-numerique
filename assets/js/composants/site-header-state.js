/*
 * Chargé tout de suite, dans <head> (script classique, pas un module) : avant le premier affichage
 * de la page, il remet la nav du haut dans l'état où elle était (rechargement de la page).
 *
 * Si la nav était visible (gardé par AutoHideHeader.js dans sessionStorage, avec sa hauteur),
 * la classe .site-header-shown sur <html> la colle en haut dès l'affichage (styles.css), et
 * --site-header-height place les onglets juste dessous : rien ne bouge quand le JavaScript démarre.
 * Sinon, rien à faire : AutoHideHeader.js s'en occupe.
 */
(function restoreSiteHeaderState() {
    try {
        var hidden = window.sessionStorage.getItem('site-header-hidden');
        var height = window.sessionStorage.getItem('site-header-height');

        if (hidden === 'false' && height) {
            document.documentElement.classList.add('site-header-shown');
            document.documentElement.style.setProperty('--site-header-height', height + 'px');
        }
    } catch (error) {
        // Stockage indisponible (navigation privée…) : AutoHideHeader.js fait le travail normalement
    }
})();
