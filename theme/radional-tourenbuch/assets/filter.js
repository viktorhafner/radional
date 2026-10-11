/* Filter der Übersicht „Alle Berichte“: blendet Karten nach Art aus, leere Jahre gleich mit. */
(function () {
    'use strict';
    function start() {
        var knoepfe = document.querySelectorAll('.rt-filter-knopf');
        Array.prototype.forEach.call(knoepfe, function (k) {
            k.addEventListener('click', function () {
                var art = k.getAttribute('data-art');
                Array.prototype.forEach.call(knoepfe, function (x) { x.setAttribute('aria-pressed', x === k ? 'true' : 'false'); });
                Array.prototype.forEach.call(document.querySelectorAll('.rt-jahr'), function (jahr) {
                    var sichtbar = 0;
                    Array.prototype.forEach.call(jahr.querySelectorAll('.rt-karte'), function (karte) {
                        var zeigen = !art || karte.getAttribute('data-art') === art;
                        karte.hidden = !zeigen;
                        if (zeigen) { sichtbar++; }
                    });
                    jahr.hidden = sichtbar === 0;
                });
            });
        });
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
})();
