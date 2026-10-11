/* YouTube erst nach Klick: ersetzt die Fläche durch das Video (youtube-nocookie.com). */
(function () {
    'use strict';
    var KEY = 'radionalYoutubeImmer';

    function immer() {
        try { return window.localStorage.getItem(KEY) === '1'; } catch (e) { return false; }
    }

    function laden(flaeche, autoplay) {
        var src = flaeche.getAttribute('data-rt-yt');
        if (!autoplay) { src = src.replace('autoplay=1', 'autoplay=0'); }
        var f = document.createElement('iframe');
        f.src = src;
        f.title = flaeche.getAttribute('data-rt-titel') || 'YouTube-Video';
        f.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
        f.setAttribute('allowfullscreen', '');
        f.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
        f.className = 'rt-yt-video';
        flaeche.replaceWith(f);
    }

    function start() {
        var flaechen = document.querySelectorAll('.rt-yt[data-rt-yt]');
        var alle = immer();
        Array.prototype.forEach.call(flaechen, function (fl) {
            if (alle) { laden(fl, false); return; }
            var knopf = fl.querySelector('.rt-yt-knopf');
            var merken = fl.querySelector('.rt-yt-merken input');
            knopf.addEventListener('click', function () {
                if (merken && merken.checked) {
                    try { window.localStorage.setItem(KEY, '1'); } catch (e) { /* egal */ }
                }
                laden(fl, true);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
