/* Radional Karte – zeigt alle Challenges als Pins (und optional GPX-Strecken). */
(function () {
    'use strict';

    /* Farben stehen in karte.css (--rk-bike, --rk-lauf, --rk-sonstiges), damit sie
       sich mit dem Theme ändern lassen. Die Werte hier gelten nur, falls dort keine stehen. */
    var SVG = function (inhalt) {
        return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor"' +
            ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + inhalt + '</svg>';
    };
    var ARTEN = {
        bike: {
            name: 'Bike', farbe: '#e26b4a',
            symbol: SVG('<circle cx="5.5" cy="16.5" r="3.5"/><circle cx="18.5" cy="16.5" r="3.5"/>' +
                '<path d="M5.5 16.5 9.5 9h7l2 7.5M9.5 9l3.5 7.5h-7.5M8 6h3M16.5 9l-1-3h2"/>')
        },
        lauf: {
            name: 'Lauf', farbe: '#5e8c6a',
            symbol: SVG('<circle cx="15" cy="4.5" r="2"/>' +
                '<path d="M7 21.5l3.5-5 3 2V22M13.5 18.5l-3-2.5 1.5-6M6 11l3.5-3 4 1 2 3.5h3.5"/>')
        },
        sonstiges: {
            name: 'Sonstiges', farbe: '#8a8580',
            symbol: SVG('<path d="M2.5 19.5 9 8.5l3.5 6 2.5-3.5 6.5 8.5z"/>')
        }
    };
    function artVon(c) { return ARTEN[c.art] ? c.art : 'sonstiges'; }

    /* Farbe einer Art, wie sie das Stylesheet gerade festlegt */
    function farbeVon(wurzel, art) {
        var wert = '';
        try { wert = window.getComputedStyle(wurzel).getPropertyValue('--rk-' + art).trim(); } catch (e) { /* alt */ }
        return wert || ARTEN[art].farbe;
    }

    /* Symbol und Name nebeneinander, z. B. in Knöpfen */
    function artMarke(art, klasse) {
        var s = el('span', klasse || 'rk-marke rk-art-' + art);
        var sym = el('span', 'rk-symbol');
        sym.innerHTML = ARTEN[art].symbol; // feste Zeichenkette aus diesem Skript
        s.appendChild(sym);
        s.appendChild(el('span', '', ARTEN[art].name));
        return s;
    }
    var ZUSTIMMUNG_KEY = 'radionalKarteZustimmung';
    var OSM_DATENSCHUTZ = 'https://osmfoundation.org/wiki/Privacy_Policy';
    var GPX_KUERZEN_M = 300;

    function el(tag, klasse, text) {
        var e = document.createElement(tag);
        if (klasse) { e.className = klasse; }
        if (text !== undefined) { e.textContent = text; }
        return e;
    }

    function zahl(n, stellen) {
        return Number(n).toLocaleString('de-AT', { maximumFractionDigits: stellen || 0 });
    }

    function datumText(iso) {
        var t = iso.split('-');
        return t.length === 3 ? (parseInt(t[2], 10) + '.' + parseInt(t[1], 10) + '.' + t[0]) : iso;
    }

    function zustimmungGespeichert() {
        try { return window.localStorage.getItem(ZUSTIMMUNG_KEY) === '1'; } catch (e) { return false; }
    }

    function zustimmungSpeichern() {
        try { window.localStorage.setItem(ZUSTIMMUNG_KEY, '1'); } catch (e) { /* egal */ }
    }

    /* Leaflet nur einmal und erst nach Zustimmung laden */
    var leafletLaden = null;
    function ladeLeaflet(basis) {
        if (window.L && window.L.markerClusterGroup) { return Promise.resolve(); }
        if (leafletLaden) { return leafletLaden; }
        /* Lädt ein anderes Plugin (auf radional.at: WP GPX Maps) schon Leaflet, wird
           dieses mitbenutzt. Ein zweites Leaflet würde das globale L ersetzen, und die
           Karten des anderen Plugins liefen dann gegen eine fremde Version. */
        var vorhanden = !!window.L;
        function css(href) {
            var l = document.createElement('link');
            l.rel = 'stylesheet';
            l.href = href;
            document.head.appendChild(l);
        }
        function js(src) {
            return new Promise(function (ok, fehler) {
                var s = document.createElement('script');
                s.src = src;
                s.onload = ok;
                s.onerror = fehler;
                document.head.appendChild(s);
            });
        }
        if (!vorhanden) { css(basis + 'leaflet.css'); }
        css(basis + 'MarkerCluster.css');
        leafletLaden = (vorhanden ? Promise.resolve() : js(basis + 'leaflet.js'))
            .then(function () { return js(basis + 'leaflet.markercluster.js'); });
        return leafletLaden;
    }

    /* GPX -> Liste [lat, lng], Start und Ziel gekürzt (Privatsphäre) */
    function gpxPunkte(text) {
        var xml = new DOMParser().parseFromString(text, 'application/xml');
        var knoten = xml.querySelectorAll('trkpt, rtept');
        var p = [];
        for (var i = 0; i < knoten.length; i++) {
            var lat = parseFloat(knoten[i].getAttribute('lat'));
            var lng = parseFloat(knoten[i].getAttribute('lon'));
            if (!isNaN(lat) && !isNaN(lng)) { p.push([lat, lng]); }
        }
        return kuerzen(kuerzen(p, GPX_KUERZEN_M).reverse(), GPX_KUERZEN_M).reverse();
    }

    function abstand(a, b) {
        var r = 6371000, rad = Math.PI / 180;
        var dLat = (b[0] - a[0]) * rad, dLng = (b[1] - a[1]) * rad;
        var h = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(a[0] * rad) * Math.cos(b[0] * rad) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return 2 * r * Math.asin(Math.sqrt(h));
    }

    function kuerzen(p, meter) {
        var weg = 0;
        for (var i = 1; i < p.length; i++) {
            weg += abstand(p[i - 1], p[i]);
            if (weg >= meter) { return p.slice(i); }
        }
        return [];
    }

    function popupHtml(c) {
        var art = artVon(c);
        var box = el('div', 'rk-popup');
        if (c.bild) {
            var img = el('img');
            img.src = c.bild;
            img.alt = '';
            img.loading = 'lazy';
            box.appendChild(img);
        }
        var zeile = el('div', 'rk-popup-art');
        zeile.appendChild(artMarke(art));
        zeile.appendChild(el('span', 'rk-popup-datum', datumText(c.datum)));
        box.appendChild(zeile);
        box.appendChild(el('strong', 'rk-popup-titel', c.titel));
        if (c.ort) { box.appendChild(el('div', 'rk-popup-ort', c.ort)); }
        var werte = [];
        if (c.km) { werte.push(zahl(c.km, 1) + ' km'); }
        if (c.hm) { werte.push(zahl(c.hm) + ' Hm'); }
        if (werte.length) { box.appendChild(el('div', 'rk-popup-werte', werte.join(' · '))); }
        var a = el('a', 'rk-popup-link', 'Zum Bericht →');
        a.href = c.link;
        box.appendChild(a);
        return box;
    }

    /* Zustimmungs-Hinweis vor dem Laden der Kacheln; auch für die Streckenkarte (strecke.js) */
    function zustimmungsHinweis(flaeche, datenschutz, titel, beimLaden) {
        var box = el('div', 'rk-hinweis');
        var symbol = el('div', 'rk-hinweis-symbol');
        symbol.innerHTML = SVG('<path d="M9 4 3 6.5v13L9 17l6 2.5 6-2.5v-13L15 6.5 9 4zM9 4v13M15 6.5v13"/>');
        box.appendChild(symbol);
        box.appendChild(el('strong', '', titel));
        var p = el('p', 'rk-hinweis-text',
            'Beim Laden der Karte wird deine IP-Adresse an OpenStreetMap übertragen. ');
        /* Ohne eingerichtete Datenschutzseite wenigstens die von OpenStreetMap */
        var a = el('a', '', datenschutz ? 'Mehr dazu in der Datenschutzerklärung'
            : 'Datenschutzerklärung von OpenStreetMap');
        a.href = datenschutz || OSM_DATENSCHUTZ;
        if (!datenschutz) { a.rel = 'noopener'; a.target = '_blank'; }
        p.appendChild(a);
        p.appendChild(document.createTextNode('.'));
        box.appendChild(p);
        var knopf = el('button', 'rk-laden', 'Karte laden');
        knopf.type = 'button';
        var merken = el('label', 'rk-merken');
        var check = el('input');
        check.type = 'checkbox';
        merken.appendChild(check);
        merken.appendChild(document.createTextNode(' Karte künftig immer laden'));
        knopf.addEventListener('click', function () {
            if (check.checked) { zustimmungSpeichern(); }
            beimLaden();
        });
        box.appendChild(knopf);
        box.appendChild(merken);
        flaeche.appendChild(box);
    }

    window.RadionalKarte = {
        ladeLeaflet: ladeLeaflet,
        zustimmungGespeichert: zustimmungGespeichert,
        zustimmungsHinweis: zustimmungsHinweis,
        farbeVon: farbeVon
    };

    function starte(wurzel) {
        var konfig;
        try {
            konfig = JSON.parse(wurzel.querySelector('.rk-konfig').textContent);
        } catch (e) {
            return;
        }
        var daten = konfig.daten || [];
        var filter = { art: 'alle', jahr: 'alle' };

        /* Kopf: Kennzahlen + Filter */
        var kopf = el('div', 'rk-kopf');
        var zahlen = el('div', 'rk-zahlen');
        var leiste = el('div', 'rk-filter');
        kopf.appendChild(zahlen);
        kopf.appendChild(leiste);
        wurzel.appendChild(kopf);

        var knoepfe = [];
        ['alle'].concat(Object.keys(ARTEN).filter(function (k) {
            return daten.some(function (c) { return artVon(c) === k; });
        })).forEach(function (k) {
            var b = el('button', 'rk-knopf rk-knopf-' + k);
            b.appendChild(k === 'alle' ? el('span', '', 'Alle') : artMarke(k));
            b.type = 'button';
            b.setAttribute('aria-pressed', k === 'alle' ? 'true' : 'false');
            b.addEventListener('click', function () {
                filter.art = k;
                knoepfe.forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
                aktualisieren(true);
            });
            knoepfe.push(b);
            leiste.appendChild(b);
        });

        var jahre = [];
        daten.forEach(function (c) {
            var j = c.datum.slice(0, 4);
            if (jahre.indexOf(j) < 0) { jahre.push(j); }
        });
        jahre.sort().reverse();
        if (jahre.length > 1) {
            var auswahl = el('select', 'rk-jahr');
            auswahl.setAttribute('aria-label', 'Jahr');
            auswahl.appendChild(new Option('Alle Jahre', 'alle'));
            jahre.forEach(function (j) { auswahl.appendChild(new Option(j, j)); });
            auswahl.addEventListener('change', function () {
                filter.jahr = auswahl.value;
                aktualisieren(true);
            });
            leiste.appendChild(auswahl);
        }

        /* Kartenfläche mit Zustimmungs-Hinweis */
        var flaeche = el('div', 'rk-flaeche');
        wurzel.appendChild(flaeche);

        var karte = null, cluster = null, linien = null, eintraege = [];

        function sichtbar(c) {
            return (filter.art === 'alle' || artVon(c) === filter.art) &&
                (filter.jahr === 'alle' || c.datum.slice(0, 4) === filter.jahr);
        }

        function kennzahlen() {
            var liste = daten.filter(sichtbar);
            var km = 0, hm = 0;
            liste.forEach(function (c) { km += c.km || 0; hm += c.hm || 0; });
            zahlen.innerHTML = '';
            [[liste.length, liste.length === 1 ? 'Challenge' : 'Challenges'], [km, 'km'], [hm, 'Höhenmeter']]
                .forEach(function (z) {
                    var box = el('div', 'rk-zahl');
                    box.appendChild(el('strong', '', zahl(z[0])));
                    box.appendChild(el('span', '', z[1]));
                    zahlen.appendChild(box);
                });
        }

        function aktualisieren(zoomen) {
            kennzahlen();
            if (!karte) { return; }
            cluster.clearLayers();
            linien.clearLayers();
            var grenzen = [];
            eintraege.forEach(function (e) {
                if (!sichtbar(e.c)) { return; }
                cluster.addLayer(e.marker);
                grenzen.push([e.c.lat, e.c.lng]);
                if (e.linie) {
                    linien.addLayer(e.linie);
                    e.linie.getLatLngs().forEach(function (p) { grenzen.push([p.lat, p.lng]); });
                }
            });
            if (zoomen && grenzen.length) {
                karte.fitBounds(grenzen, { padding: [30, 30], maxZoom: 11 });
            }
        }

        function karteZeigen() {
            flaeche.innerHTML = '';
            flaeche.classList.add('rk-laedt');
            ladeLeaflet(konfig.leaflet).then(function () {
                flaeche.classList.remove('rk-laedt');
                var L = window.L;
                L.Icon.Default.imagePath = konfig.leaflet + 'images/';
                karte = L.map(flaeche, { scrollWheelZoom: false });
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(karte);
                karte.on('focus', function () { karte.scrollWheelZoom.enable(); });
                karte.on('blur', function () { karte.scrollWheelZoom.disable(); });

                linien = L.layerGroup().addTo(karte);
                cluster = L.markerClusterGroup({
                    showCoverageOnHover: false,
                    maxClusterRadius: 45,
                    iconCreateFunction: function (gruppe) {
                        var n = gruppe.getChildCount();
                        var g = n < 10 ? 36 : (n < 30 ? 42 : 48);
                        return L.divIcon({
                            className: 'rk-cluster',
                            html: '<span>' + n + '</span>',
                            iconSize: [g, g]
                        });
                    }
                });
                karte.addLayer(cluster);

                eintraege = daten.map(function (c) {
                    var art = artVon(c);
                    var icon = L.divIcon({
                        className: 'rk-pin rk-art-' + art,
                        html: '<span>' + ARTEN[art].symbol + '</span>',
                        iconSize: [34, 34],
                        iconAnchor: [17, 17],
                        popupAnchor: [0, -16]
                    });
                    var marker = L.marker([c.lat, c.lng], { icon: icon, title: c.titel, alt: c.titel });
                    marker.bindPopup(function () { return popupHtml(c); }, { maxWidth: 260, minWidth: 220 });
                    return { c: c, marker: marker, linie: null };
                });

                if (!eintraege.length) {
                    karte.setView([47.6, 13.8], 7); // Österreich
                }
                aktualisieren(true);

                /* GPX-Strecken nachladen */
                eintraege.forEach(function (e) {
                    if (!e.c.gpx) { return; }
                    fetch(e.c.gpx).then(function (r) {
                        if (!r.ok) { throw new Error(r.status); }
                        return r.text();
                    }).then(function (text) {
                        var p = gpxPunkte(text);
                        if (p.length < 2) { return; }
                        e.linie = L.polyline(p, { color: farbeVon(wurzel, artVon(e.c)), weight: 3, opacity: 0.85 });
                        e.linie.on('click', function () {
                            cluster.zoomToShowLayer(e.marker, function () { e.marker.openPopup(); });
                        });
                        if (sichtbar(e.c)) { linien.addLayer(e.linie); }
                    }).catch(function () { /* Strecke fehlt – Pin bleibt */ });
                });
            }).catch(function () {
                flaeche.classList.remove('rk-laedt');
                flaeche.appendChild(el('p', 'rk-hinweis-text', 'Die Karte konnte nicht geladen werden.'));
            });
        }

        function hinweisZeigen() {
            zustimmungsHinweis(flaeche, konfig.datenschutz, 'Karte meiner Challenges', karteZeigen);
        }

        kennzahlen();
        if (zustimmungGespeichert()) {
            karteZeigen();
        } else {
            hinweisZeigen();
        }
    }

    function alle() {
        var w = document.querySelectorAll('.rk:not(.rk-strecke)');
        for (var i = 0; i < w.length; i++) {
            if (!w[i].dataset.rkFertig) {
                w[i].dataset.rkFertig = '1';
                starte(w[i]);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', alle);
    } else {
        alle();
    }
})();
