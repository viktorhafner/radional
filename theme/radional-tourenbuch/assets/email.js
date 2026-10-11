/* Macht aus geschützten E-Mail-Adressen (inc/email-schutz.php) wieder klickbare Links. */
(function () {
    'use strict';
    function start() {
        Array.prototype.forEach.call(document.querySelectorAll('.rt-mail[data-rt-mail]'), function (s) {
            var adresse;
            try { adresse = window.atob(s.getAttribute('data-rt-mail')).split('').reverse().join(''); } catch (e) { return; }
            if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(adresse)) { return; }
            var a = document.createElement('a');
            a.href = 'mailto:' + adresse;
            a.textContent = s.hasAttribute('data-rt-text') ? s.textContent : adresse;
            s.replaceWith(a);
        });
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
})();
