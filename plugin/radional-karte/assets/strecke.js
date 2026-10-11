/* Streckenkarte eines Berichts (includes/strecke.php). Die Kacheln laden erst nach Zustimmung –
   derselben wie bei der großen Karte („Karte künftig immer laden“ gilt für beide). */
(function () {
    'use strict';
    var RK = window.RadionalKarte;
    if (!RK) { return; }

    function zeigen(wurzel, flaeche, konfig) {
        flaeche.innerHTML = '';
        flaeche.classList.add('rk-laedt');
        RK.ladeLeaflet(konfig.leaflet).then(function () {
            flaeche.classList.remove('rk-laedt');
            var L = window.L;
            var karte = L.map(flaeche, { scrollWheelZoom: false });
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(karte);
            karte.on('focus', function () { karte.scrollWheelZoom.enable(); });
            karte.on('blur', function () { karte.scrollWheelZoom.disable(); });
            var art = (wurzel.className.match(/rk-art-(\w+)/) || [])[1] || 'bike';
            var farbe = RK.farbeVon(wurzel, art);
            var linie = L.polyline(konfig.linie, { color: farbe, weight: 4, opacity: 0.9 }).addTo(karte);
            function punkt(lage, titel) {
                L.circleMarker(lage, { radius: 7, color: '#fff', weight: 2, fillColor: farbe, fillOpacity: 1 })
                    .bindTooltip(titel).addTo(karte);
            }
            punkt(konfig.linie[0], 'Start');
            punkt(konfig.linie[konfig.linie.length - 1], 'Ziel');
            karte.fitBounds(linie.getBounds(), { padding: [24, 24] });
        }).catch(function () {
            flaeche.classList.remove('rk-laedt');
            var p = document.createElement('p');
            p.className = 'rk-hinweis-text';
            p.textContent = 'Die Karte konnte nicht geladen werden.';
            flaeche.appendChild(p);
        });
    }

    function start() {
        Array.prototype.forEach.call(document.querySelectorAll('.rk-strecke'), function (wurzel) {
            if (wurzel.dataset.rkFertig) { return; }
            wurzel.dataset.rkFertig = '1';
            var konfig;
            try { konfig = JSON.parse(wurzel.querySelector('.rk-konfig').textContent); } catch (e) { return; }
            if (!konfig.linie || konfig.linie.length < 2) { return; }
            var flaeche = wurzel.querySelector('.rk-flaeche');
            if (RK.zustimmungGespeichert()) {
                zeigen(wurzel, flaeche, konfig);
            } else {
                RK.zustimmungsHinweis(flaeche, konfig.datenschutz, 'Strecke auf der Karte', function () {
                    zeigen(wurzel, flaeche, konfig);
                });
            }
        });
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
})();
