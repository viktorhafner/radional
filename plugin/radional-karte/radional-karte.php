<?php
/**
 * Plugin Name: Radional Karte
 * Description: Interaktive Karte aller Bike- und Lauf-Challenges. Ort, Art, km und Hm werden je Beitrag eingetragen, die Karte erscheint per Shortcode [radional_karte].
 * Version:     1.4.0
 * Author:      radional.at
 * License:     GPL-2.0-or-later
 * Requires PHP: 7.0
 * Update URI:  https://github.com/viktorhafner/radional
 */

if (!defined('ABSPATH')) {
    exit;
}

define('RADIONAL_KARTE_VERSION', '1.4.0');

if (is_admin()) {
    require_once __DIR__ . '/includes/vorschlaege.php';
}
require_once __DIR__ . '/includes/update.php';
require_once __DIR__ . '/includes/strecke.php';

/* ------------------------------------------------------------------ */
/* Eingabe im Beitrag                                                  */
/* ------------------------------------------------------------------ */

function radional_karte_felder()
{
    return array(
        'koordinaten' => '_rk_koordinaten', // "48.2082, 16.3738"
        'ort'         => '_rk_ort',
        'art'         => '_rk_art',          // bike | lauf | sonstiges
        'km'          => '_rk_km',
        'hm'          => '_rk_hm',
        'gpx'         => '_rk_gpx',          // URL einer GPX-Datei aus der Mediathek
    );
}

add_action('add_meta_boxes', function () {
    add_meta_box('radional_karte', 'Karte (Challenge-Ort)', 'radional_karte_metabox', 'post', 'side');
});

function radional_karte_metabox($post)
{
    wp_nonce_field('radional_karte_speichern', 'radional_karte_nonce');
    $f = radional_karte_felder();
    $w = array();
    foreach ($f as $k => $meta) {
        $w[$k] = get_post_meta($post->ID, $meta, true);
    }
    ?>
    <p><label><strong>Koordinaten</strong><br>
        <input type="text" name="rk_koordinaten" value="<?php echo esc_attr($w['koordinaten']); ?>" placeholder="48.2082, 16.3738" style="width:100%"></label><br>
        <small>Tipp: in Google Maps Rechtsklick auf den Ort – die erste Zeile (Koordinaten) anklicken kopiert sie. Leer = Beitrag erscheint nicht auf der Karte.</small></p>
    <p><label><strong>Ort</strong><br>
        <input type="text" name="rk_ort" value="<?php echo esc_attr($w['ort']); ?>" placeholder="z. B. Großglockner" style="width:100%"></label></p>
    <p><label><strong>Art</strong><br>
        <select name="rk_art" style="width:100%">
            <?php foreach (array('bike' => 'Bike', 'lauf' => 'Lauf', 'sonstiges' => 'Sonstiges') as $wert => $text) : ?>
                <option value="<?php echo esc_attr($wert); ?>" <?php selected($w['art'] ?: 'bike', $wert); ?>><?php echo esc_html($text); ?></option>
            <?php endforeach; ?>
        </select></label></p>
    <p style="display:flex;gap:8px">
        <label style="flex:1"><strong>km</strong><br><input type="text" name="rk_km" value="<?php echo esc_attr($w['km']); ?>" style="width:100%"></label>
        <label style="flex:1"><strong>Hm</strong><br><input type="text" name="rk_hm" value="<?php echo esc_attr($w['hm']); ?>" style="width:100%"></label>
    </p>
    <p><label><strong>GPX-Datei (optional)</strong><br>
        <input type="url" name="rk_gpx" value="<?php echo esc_attr($w['gpx']); ?>" placeholder="https://…/wp-content/uploads/…gpx" style="width:100%"></label><br>
        <small>GPX in die Mediathek hochladen und die Datei-URL hier einfügen. Start und Ziel werden auf der Karte um 300 m gekürzt.</small></p>
    <?php
}

add_action('save_post_post', function ($post_id) {
    if (!isset($_POST['radional_karte_nonce'])
        || !wp_verify_nonce($_POST['radional_karte_nonce'], 'radional_karte_speichern')
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        || !current_user_can('edit_post', $post_id)) {
        return;
    }
    $f = radional_karte_felder();

    $koord = isset($_POST['rk_koordinaten']) ? radional_karte_koordinaten(wp_unslash($_POST['rk_koordinaten'])) : null;
    if ($koord) {
        update_post_meta($post_id, $f['koordinaten'], $koord[0] . ', ' . $koord[1]);
    } else {
        delete_post_meta($post_id, $f['koordinaten']);
    }

    $art = isset($_POST['rk_art']) ? sanitize_key($_POST['rk_art']) : 'bike';
    update_post_meta($post_id, $f['art'], in_array($art, array('bike', 'lauf', 'sonstiges'), true) ? $art : 'bike');

    foreach (array('ort', 'km', 'hm') as $k) {
        $wert = isset($_POST['rk_' . $k]) ? sanitize_text_field(wp_unslash($_POST['rk_' . $k])) : '';
        if ($k !== 'ort') {
            $zahl = radional_karte_zahl($wert);
            $wert = $zahl === null ? '' : (string) ($k === 'hm' ? (int) round($zahl) : round($zahl, 2));
        }
        update_post_meta($post_id, $f[$k], $wert);
    }

    $gpx = isset($_POST['rk_gpx']) ? esc_url_raw(wp_unslash($_POST['rk_gpx'])) : '';
    update_post_meta($post_id, $f['gpx'], $gpx);

    delete_transient('radional_karte_daten');
});

/**
 * Zahl, wie man sie in Österreich tippt: "1.200" und "1 200" sind tausendzweihundert,
 * "21,5" und "21.5" einundzwanzigeinhalb, "1.234,5" beides zusammen. Sonst null.
 */
function radional_karte_zahl($text)
{
    $t = str_replace(array(' ', "\xc2\xa0", "'", '’'), '', trim((string) $text));
    if ($t === '') {
        return null;
    }
    if (strpos($t, ',') !== false) {
        $t = str_replace(array('.', ','), array('', '.'), $t);   // 1.234,5
    } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $t)) {
        $t = str_replace('.', '', $t);                          // 1.200
    }
    return (is_numeric($t) && (float) $t >= 0) ? (float) $t : null;
}

/** "48.2082, 16.3738" oder "48.2082 16.3738" -> array(48.2082, 16.3738), sonst null */
function radional_karte_koordinaten($text)
{
    if (!preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*[,; ]\s*(-?\d+(?:\.\d+)?)\s*$/', (string) $text, $m)) {
        return null;
    }
    $lat = (float) $m[1];
    $lng = (float) $m[2];
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        return null;
    }
    return array(round($lat, 5), round($lng, 5));
}

/* GPX-Upload in der Mediathek erlauben */
add_filter('upload_mimes', function ($mimes) {
    $mimes['gpx'] = 'application/gpx+xml';
    return $mimes;
});
add_filter('wp_check_filetype_and_ext', function ($daten, $datei, $name) {
    if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'gpx') {
        $daten['ext']  = 'gpx';
        $daten['type'] = 'application/gpx+xml';
    }
    return $daten;
}, 10, 3);

/* Cache leeren, wenn ein Beitrag gelöscht oder (un)veröffentlicht wird */
add_action('transition_post_status', function () {
    delete_transient('radional_karte_daten');
});
add_action('deleted_post', function () {
    delete_transient('radional_karte_daten');
});

/* ------------------------------------------------------------------ */
/* Daten für die Karte                                                 */
/* ------------------------------------------------------------------ */

function radional_karte_daten()
{
    $daten = get_transient('radional_karte_daten');
    if (is_array($daten)) {
        return $daten;
    }
    $f = radional_karte_felder();
    $ids = get_posts(array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_key'       => $f['koordinaten'],
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    $daten = array();
    foreach ($ids as $id) {
        $koord = radional_karte_koordinaten(get_post_meta($id, $f['koordinaten'], true));
        if (!$koord) {
            continue;
        }
        $bild = get_the_post_thumbnail_url($id, 'medium');
        $daten[] = array(
            'titel' => html_entity_decode(get_the_title($id), ENT_QUOTES, 'UTF-8'),
            'link'  => get_permalink($id),
            'datum' => get_the_date('Y-m-d', $id),
            'lat'   => $koord[0],
            'lng'   => $koord[1],
            'ort'   => (string) get_post_meta($id, $f['ort'], true),
            'art'   => get_post_meta($id, $f['art'], true) ?: 'bike',
            'km'    => (float) get_post_meta($id, $f['km'], true),
            'hm'    => (float) get_post_meta($id, $f['hm'], true),
            'gpx'   => (string) get_post_meta($id, $f['gpx'], true),
            'bild'  => $bild ? $bild : '',
        );
    }
    set_transient('radional_karte_daten', $daten, DAY_IN_SECONDS);
    return $daten;
}

/* ------------------------------------------------------------------ */
/* Shortcode [radional_karte hoehe="520px"]                            */
/* ------------------------------------------------------------------ */

add_shortcode('radional_karte', function ($atts) {
    $atts = shortcode_atts(array('hoehe' => '520px'), $atts, 'radional_karte');
    $hoehe = preg_match('/^\d+(px|vh|rem|em)$/', $atts['hoehe']) ? $atts['hoehe'] : '520px';

    $basis = plugin_dir_url(__FILE__) . 'assets/';
    wp_enqueue_style('radional-karte', $basis . 'karte.css', array(), RADIONAL_KARTE_VERSION);
    wp_enqueue_script('radional-karte', $basis . 'karte.js', array(), RADIONAL_KARTE_VERSION, true);

    $id = 'radional-karte-' . wp_rand(1000, 9999);
    $konfig = array(
        'daten'      => radional_karte_daten(),
        'leaflet'    => $basis . 'leaflet/',
        'datenschutz' => get_privacy_policy_url(),
    );

    ob_start();
    ?>
    <div class="rk" id="<?php echo esc_attr($id); ?>" style="--rk-hoehe:<?php echo esc_attr($hoehe); ?>">
        <script type="application/json" class="rk-konfig"><?php echo wp_json_encode($konfig); ?></script>
    </div>
    <?php
    return ob_get_clean();
});
