/* Kasten „Karte (Challenge-Ort)“: GPX-Datei aus der Mediathek wählen oder hochladen. */
(function ($) {
    'use strict';
    var rahmen = null;
    $(document).on('click', '.rk-gpx-waehlen', function (e) {
        e.preventDefault();
        if (!rahmen) {
            rahmen = wp.media({
                title: 'GPX-Datei wählen',
                button: { text: 'Diese Strecke verwenden' },
                library: { type: 'application/gpx+xml' },
                multiple: false
            });
            rahmen.on('select', function () {
                var datei = rahmen.state().get('selection').first().toJSON();
                $('#rk_gpx').val(datei.url).trigger('change');
                $('.rk-gpx-entfernen').prop('hidden', false);
            });
        }
        rahmen.open();
    });
    $(document).on('click', '.rk-gpx-entfernen', function (e) {
        e.preventDefault();
        $('#rk_gpx').val('').trigger('change');
        $(this).prop('hidden', true);
    });
})(jQuery);
