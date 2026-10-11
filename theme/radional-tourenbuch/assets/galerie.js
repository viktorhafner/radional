/* Galerie: Großansicht mit Pfeilen, Tastatur (← → Esc) und Wischen. Ohne Skript öffnet ein Klick einfach das Bild. */
(function () {
    'use strict';

    function el(tag, klasse, text) {
        var e = document.createElement(tag);
        if (klasse) { e.className = klasse; }
        if (text) { e.textContent = text; }
        return e;
    }

    function start() {
        var elemente = Array.prototype.slice.call(document.querySelectorAll('.rt-galerie .rt-g-element'));
        if (!elemente.length || typeof HTMLDialogElement === 'undefined') { return; }

        var dlg = el('dialog', 'rt-lb');
        dlg.setAttribute('aria-label', 'Foto groß');
        var buehne = el('div', 'rt-lb-buehne');
        var unten = el('div', 'rt-lb-unten');
        var info = el('p', 'rt-lb-info');
        var zaehler = el('span', 'rt-lb-zaehler');
        var zu = el('button', 'rt-lb-zu');
        zu.type = 'button'; zu.setAttribute('aria-label', 'Schließen'); zu.textContent = '×';
        var zurueck = el('button', 'rt-lb-pfeil rt-lb-zurueck');
        zurueck.type = 'button'; zurueck.setAttribute('aria-label', 'Vorheriges'); zurueck.textContent = '‹';
        var weiter = el('button', 'rt-lb-pfeil rt-lb-weiter');
        weiter.type = 'button'; weiter.setAttribute('aria-label', 'Nächstes'); weiter.textContent = '›';
        unten.appendChild(info);
        unten.appendChild(zaehler);
        dlg.appendChild(zu);
        dlg.appendChild(zurueck);
        dlg.appendChild(buehne);
        dlg.appendChild(weiter);
        dlg.appendChild(unten);
        document.body.appendChild(dlg);

        var jetzt = 0;

        function zeige(i) {
            jetzt = (i + elemente.length) % elemente.length;
            var a = elemente[jetzt];
            buehne.innerHTML = '';
            if (a.getAttribute('data-typ') === 'video') {
                var v = el('video', 'rt-lb-medium');
                v.src = a.href;
                v.controls = true;
                v.autoplay = true;
                v.playsInline = true;
                buehne.appendChild(v);
            } else {
                var img = el('img', 'rt-lb-medium');
                img.src = a.href;
                var klein = a.querySelector('img');
                img.alt = klein ? klein.alt : '';
                buehne.appendChild(img);
            }
            info.innerHTML = '';
            info.appendChild(document.createTextNode(a.getAttribute('data-datum') || ''));
            var link = a.getAttribute('data-link');
            if (link) {
                info.appendChild(document.createTextNode(' · aus '));
                var b = el('a', '', a.getAttribute('data-bericht') || 'dem Bericht');
                b.href = link;
                info.appendChild(b);
            }
            zaehler.textContent = (jetzt + 1) + ' / ' + elemente.length;
            // nächstes Bild schon vorladen
            var n = elemente[(jetzt + 1) % elemente.length];
            if (n.getAttribute('data-typ') !== 'video') { (new Image()).src = n.href; }
        }

        function schliessen() {
            var v = buehne.querySelector('video');
            if (v) { v.pause(); }
            buehne.innerHTML = '';
            dlg.close();
        }

        elemente.forEach(function (a, i) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                zeige(i);
                if (!dlg.open) { dlg.showModal(); }
            });
        });
        zu.addEventListener('click', schliessen);
        zurueck.addEventListener('click', function () { zeige(jetzt - 1); });
        weiter.addEventListener('click', function () { zeige(jetzt + 1); });
        dlg.addEventListener('cancel', function (e) { e.preventDefault(); schliessen(); });
        dlg.addEventListener('close', function () {
            var a = elemente[jetzt];
            if (a) { a.focus({ preventScroll: true }); }
        });
        dlg.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft') { zeige(jetzt - 1); }
            if (e.key === 'ArrowRight') { zeige(jetzt + 1); }
        });
        // Klick neben das Bild schließt
        buehne.addEventListener('click', function (e) { if (e.target === buehne) { schliessen(); } });

        var x0 = null;
        buehne.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
        buehne.addEventListener('touchend', function (e) {
            if (x0 === null) { return; }
            var dx = e.changedTouches[0].clientX - x0;
            if (Math.abs(dx) > 50) { zeige(dx < 0 ? jetzt + 1 : jetzt - 1); }
            x0 = null;
        });
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
})();
