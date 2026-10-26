'use strict';

document.addEventListener('DOMContentLoaded', function () {
    var button = document.querySelector('.scroll-top');
    if (!button) return;

    var threshold = 280;
    var scheduled = false;

    function updateVisibility() {
        var visible = window.scrollY > threshold;
        button.classList.toggle('is-visible', visible);
        button.setAttribute('aria-hidden', visible ? 'false' : 'true');
        scheduled = false;
    }

    function scheduleUpdate() {
        if (scheduled) return;
        scheduled = true;
        window.requestAnimationFrame(updateVisibility);
    }

    button.setAttribute('aria-hidden', 'true');
    button.addEventListener('click', function (event) {
        event.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    window.addEventListener('scroll', scheduleUpdate, { passive: true });
    window.addEventListener('pageshow', scheduleUpdate);
    updateVisibility();
});
