'use strict';
/*
 * Client-side translations.
 *
 * The server exposes window.WORKSPACE_I18N for the current language; this file
 * gives every script one lookup so DataTables, Select2 and buttons follow the
 * interface language instead of a hard-coded French string.
 */
window.UI = function (key, fallback) {
    var dict = window.WORKSPACE_I18N || {};
    return typeof dict[key] === 'string' && dict[key] !== '' ? dict[key] : (fallback || key);
};

window.UILang = function () {
    return document.documentElement.lang === 'fr' ? 'fr' : 'en';
};
