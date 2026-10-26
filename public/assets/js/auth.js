'use strict';

// Affichage / masquage du mot de passe. Aucun script en ligne (CSP stricte).
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-password-toggle');
            var input = targetId ? document.getElementById(targetId) : null;

            if (!input) {
                return;
            }

            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';

            button.classList.toggle('is-visible', show);
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
            button.setAttribute(
                'aria-label',
                show ? 'Masquer le mot de passe' : 'Afficher le mot de passe'
            );
        });
    });
});
