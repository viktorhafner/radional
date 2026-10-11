/* Übersicht „Alle Berichte“: Filter nach Art und Schnellsuche nach Titel, Ort und Jahr.
   Beides wirkt zusammen; leere Jahre verschwinden mit. */
(function () {
    'use strict';
    function ohneAkzente(t) {
        return t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/ß/g, 'ss');
    }
    function start() {
        var knoepfe = document.querySelectorAll('.rt-filter-knopf');
        var feld = document.getElementById('rt-schnellsuche');
        var leer = document.querySelector('.rt-schnellsuche-leer');
        var volltext = document.querySelector('.rt-volltext');
        var art = '';

        function anwenden() {
            var woerter = feld ? ohneAkzente(feld.value.trim()).split(/\s+/).filter(Boolean) : [];
            var gesamt = 0;
            Array.prototype.forEach.call(document.querySelectorAll('.rt-jahr'), function (jahr) {
                var sichtbar = 0;
                Array.prototype.forEach.call(jahr.querySelectorAll('.rt-karte'), function (karte) {
                    var text = ohneAkzente(karte.getAttribute('data-suche') || '');
                    var zeigen = (!art || karte.getAttribute('data-art') === art)
                        && woerter.every(function (w) { return text.indexOf(w) !== -1; });
                    karte.hidden = !zeigen;
                    if (zeigen) { sichtbar++; }
                });
                jahr.hidden = sichtbar === 0;
                gesamt += sichtbar;
            });
            if (leer) {
                leer.hidden = gesamt > 0;
                if (volltext && feld) { volltext.href = volltext.href.split('?')[0] + '?s=' + encodeURIComponent(feld.value.trim()); }
            }
        }

        Array.prototype.forEach.call(knoepfe, function (k) {
            k.addEventListener('click', function () {
                art = k.getAttribute('data-art');
                Array.prototype.forEach.call(knoepfe, function (x) { x.setAttribute('aria-pressed', x === k ? 'true' : 'false'); });
                anwenden();
            });
        });
        if (feld) {
            feld.addEventListener('input', anwenden);
            // Enter: volle Suche im Text, falls hier nichts passt
            feld.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && leer && !leer.hidden && volltext) { window.location.href = volltext.href; }
            });
        }
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
})();
