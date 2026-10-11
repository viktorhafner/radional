<?php
/**
 * Streckenkarte für einen einzelnen Bericht: [radional_strecke gpx="…"]
 *
 * Ersetzt WP GPX Maps. Solange dieses Plugin aktiv ist, bleibt sein Shortcode
 * [sgpx] unangetastet; ist es deaktiviert, übernimmt dieses Plugin [sgpx gpx="…"]
 * – bestehende Berichte müssen also nicht geändert werden.
 *
 * Die GPX-Datei wird auf dem Server gelesen (zwischengespeichert, bis sie sich
 * ändert): Kennzahlen und Höhenprofil kommen fertig als HTML/SVG, ohne fremde
 * Dienste. Nur die Kartenkacheln (OpenStreetMap) brauchen die Zustimmung – dieselbe
 * wie bei der großen Karte. Auf der Karte sind Start und Ziel um je 300 m gekürzt.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** GPX-Angabe aus dem Shortcode -> Dateipfad auf dem Server, oder null */
function radional_karte_gpx_pfad($angabe)
{
    $angabe = trim(html_entity_decode((string) $angabe, ENT_QUOTES, 'UTF-8'));
    if ($angabe === '') {
        return null;
    }
    $uploads = wp_get_upload_dir();
    $kandidaten = array();
    if (preg_match('#^https?://#i', $angabe)) {
        // nur Dateien dieser Website, nie fremde Adressen abrufen
        $pfad = wp_parse_url($angabe, PHP_URL_PATH);
        $home = wp_parse_url(home_url('/'), PHP_URL_HOST);
        if (wp_parse_url($angabe, PHP_URL_HOST) !== $home || !$pfad) {
            return null;
        }
        $angabe = $pfad;
    }
    $angabe = rawurldecode($angabe);
    if ($angabe[0] === '/') {
        $kandidaten[] = untrailingslashit(ABSPATH) . $angabe;
        $kandidaten[] = untrailingslashit(dirname(ABSPATH)) . $angabe; // WordPress in einem Unterordner
    } else {
        $kandidaten[] = trailingslashit($uploads['basedir']) . 'gpx/' . $angabe; // so legt WP GPX Maps ab
        $kandidaten[] = trailingslashit($uploads['basedir']) . $angabe;
    }
    $erlaubt = realpath(untrailingslashit(ABSPATH) . '/wp-content');
    foreach ($kandidaten as $k) {
        $echt = realpath($k);
        if ($echt && $erlaubt && strpos($echt, $erlaubt) === 0 && is_file($echt)
            && strtolower(pathinfo($echt, PATHINFO_EXTENSION)) === 'gpx') {
            return $echt;
        }
    }
    return null;
}

function radional_karte_abstand($a, $b)
{
    $r = 6371000.0;
    $rad = M_PI / 180;
    $dlat = ($b[0] - $a[0]) * $rad;
    $dlng = ($b[1] - $a[1]) * $rad;
    $h = sin($dlat / 2) ** 2 + cos($a[0] * $rad) * cos($b[0] * $rad) * sin($dlng / 2) ** 2;
    return 2 * $r * asin(min(1, sqrt($h)));
}

/** Liest die Datei: Punkte, Distanz, Anstieg, Höhen – oder null */
function radional_karte_gpx_lesen($datei)
{
    $schluessel = 'radional_gpx_' . md5($datei . '|' . filemtime($datei) . '|2');
    $gespeichert = get_transient($schluessel);
    if (is_array($gespeichert)) {
        return $gespeichert;
    }
    $alt = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    // LIBXML_NONET: keine Netzzugriffe aus der Datei heraus; Entitäten werden nicht ersetzt (Vorgabe)
    $gelesen = $dom->load($datei, LIBXML_NONET);
    libxml_use_internal_errors($alt);
    if (!$gelesen) {
        return null;
    }
    $punkte = array();
    // Track bevorzugt, sonst Route; getElementsByTagName ist vom Namensraum der Datei unabhängig
    foreach (array('trkpt', 'rtept') as $name) {
        foreach ($dom->getElementsByTagName($name) as $p) {
            $lat = (float) $p->getAttribute('lat');
            $lon = (float) $p->getAttribute('lon');
            if ($lat === 0.0 && $lon === 0.0) {
                continue;
            }
            $ele = null;
            $e = $p->getElementsByTagName('ele');
            if ($e->length && is_numeric(trim($e->item(0)->textContent))) {
                $ele = (float) trim($e->item(0)->textContent);
            }
            $punkte[] = array($lat, $lon, $ele);
        }
        if ($punkte) {
            break;
        }
    }
    if (count($punkte) < 2) {
        return null;
    }

    // Distanz je Punkt
    $dist = array(0.0);
    for ($i = 1, $n = count($punkte); $i < $n; $i++) {
        $dist[$i] = $dist[$i - 1] + radional_karte_abstand($punkte[$i - 1], $punkte[$i]);
    }
    $gesamt = end($dist);

    // Anstieg: Höhen geglättet, kleine Schwankungen (< 3 m) nicht mitgezählt
    $anstieg = 0.0;
    $hoehen = array_column($punkte, 2);
    $mit_hoehe = count(array_filter($hoehen, function ($h) { return $h !== null; })) > count($punkte) / 2;
    $max = null;
    $min = null;
    if ($mit_hoehe) {
        $bezug = null;
        foreach ($hoehen as $h) {
            if ($h === null) {
                continue;
            }
            $max = $max === null ? $h : max($max, $h);
            $min = $min === null ? $h : min($min, $h);
            if ($bezug === null) {
                $bezug = $h;
            } elseif ($h - $bezug >= 3) {
                $anstieg += $h - $bezug;
                $bezug = $h;
            } elseif ($bezug - $h >= 3) {
                $bezug = $h;
            }
        }
    }

    // Höhenprofil: 240 gleich weit auseinanderliegende Stellen
    $profil = array();
    if ($mit_hoehe && $gesamt > 0) {
        $j = 0;
        $letzte = $hoehen[0] ?? $min;
        for ($s = 0; $s <= 240; $s++) {
            $ziel = $gesamt * $s / 240;
            while ($j < count($dist) - 1 && $dist[$j] < $ziel) {
                $j++;
            }
            if ($hoehen[$j] !== null) {
                $letzte = $hoehen[$j];
            }
            $profil[] = array(round($ziel / 1000, 3), round((float) $letzte, 1));
        }
    }

    // Karte: Start und Ziel um 300 m kürzen, dann auf höchstens 1500 Punkte ausdünnen
    $von = 0;
    while ($von < count($dist) - 1 && $dist[$von] < 300) {
        $von++;
    }
    $bis = count($dist) - 1;
    while ($bis > $von && $gesamt - $dist[$bis] < 300) {
        $bis--;
    }
    $linie = array();
    if ($bis - $von >= 1) {
        $schritt = max(1, (int) ceil(($bis - $von + 1) / 1500));
        for ($i = $von; $i <= $bis; $i += $schritt) {
            $linie[] = array(round($punkte[$i][0], 5), round($punkte[$i][1], 5));
        }
        $letzter = array(round($punkte[$bis][0], 5), round($punkte[$bis][1], 5));
        if (end($linie) !== $letzter) {
            $linie[] = $letzter;
        }
    }

    $ergebnis = array(
        'linie'   => $linie,
        'km'      => round($gesamt / 1000, 1),
        'anstieg' => $mit_hoehe ? (int) round($anstieg) : null,
        'max'     => $max === null ? null : (int) round($max),
        'min'     => $min === null ? null : (int) round($min),
        'profil'  => $profil,
    );
    set_transient($schluessel, $ergebnis, 30 * DAY_IN_SECONDS);
    return $ergebnis;
}

/** Höhenprofil als SVG, fertig zum Einbetten */
function radional_karte_profil_svg($profil, $min, $max)
{
    if (count($profil) < 2 || $max === null) {
        return '';
    }
    $b = 1000;
    $h = 180;
    $unten = $h - 22;
    $oben = 12;
    $spanne = max(50, $max - $min);
    $boden = $min - $spanne * 0.08;
    $decke = $max + $spanne * 0.08;
    $km_max = end($profil)[0] ?: 1;
    $x = function ($km) use ($b, $km_max) { return round(40 + ($b - 50) * $km / $km_max, 1); };
    $y = function ($m) use ($unten, $oben, $boden, $decke) { return round($unten - ($unten - $oben) * ($m - $boden) / ($decke - $boden), 1); };
    $punkte = array();
    foreach ($profil as $p) {
        $punkte[] = $x($p[0]) . ',' . $y($p[1]);
    }
    $flaeche = 'M' . $x(0) . ',' . $unten . ' L' . implode(' L', $punkte) . ' L' . $x($km_max) . ',' . $unten . ' Z';
    $svg = '<svg class="rk-profil" viewBox="0 0 ' . $b . ' ' . $h . '" preserveAspectRatio="none" role="img" aria-label="'
        . esc_attr(sprintf('Höhenprofil: von %d bis %d m über %s km', $min, $max, number_format_i18n($km_max, 1))) . '">';
    $svg .= '<path class="rk-profil-flaeche" d="' . esc_attr($flaeche) . '"/>';
    $svg .= '<polyline class="rk-profil-linie" points="' . esc_attr(implode(' ', $punkte)) . '"/>';
    // Hilfslinien: höchster und tiefster Punkt, km-Marken
    foreach (array($max, $min) as $m) {
        $svg .= '<line class="rk-profil-raster" x1="40" x2="' . ($b - 10) . '" y1="' . $y($m) . '" y2="' . $y($m) . '"/>';
        $svg .= '<text class="rk-profil-text" x="34" y="' . ($y($m) + 4) . '" text-anchor="end">' . esc_html(number_format_i18n($m)) . '</text>';
    }
    $teil = $km_max > 80 ? 20 : ($km_max > 40 ? 10 : ($km_max > 15 ? 5 : 1));
    for ($km = $teil; $km < $km_max; $km += $teil) {
        $svg .= '<text class="rk-profil-text" x="' . $x($km) . '" y="' . ($h - 5) . '" text-anchor="middle">' . esc_html($km . ' km') . '</text>';
    }
    return $svg . '</svg>';
}

function radional_karte_strecke($atts)
{
    $atts = shortcode_atts(array('gpx' => '', 'hoehe' => '420px'), $atts, 'radional_strecke');
    $datei = radional_karte_gpx_pfad($atts['gpx']);
    $daten = $datei ? radional_karte_gpx_lesen($datei) : null;
    if (!$daten) {
        return current_user_can('edit_posts')
            ? '<p class="rk-strecke-fehler">Streckenkarte: GPX-Datei „' . esc_html($atts['gpx']) . '“ nicht gefunden oder nicht lesbar. (Nur für Angemeldete sichtbar.)</p>'
            : '';
    }
    $hoehe = preg_match('/^\d+(px|vh|rem|em)$/', $atts['hoehe']) ? $atts['hoehe'] : '420px';

    $basis = plugin_dir_url(__DIR__) . 'assets/';
    wp_enqueue_style('radional-karte', $basis . 'karte.css', array(), RADIONAL_KARTE_VERSION);
    wp_enqueue_script('radional-karte', $basis . 'karte.js', array(), RADIONAL_KARTE_VERSION, true);
    wp_enqueue_script('radional-karte-strecke', $basis . 'strecke.js', array('radional-karte'), RADIONAL_KARTE_VERSION, true);

    $art = get_post_meta(get_the_ID(), '_rk_art', true);
    $art = in_array($art, array('bike', 'lauf', 'sonstiges'), true) ? $art : 'bike';

    $zahlen = array(array(number_format_i18n($daten['km'], 1), 'km'));
    if ($daten['anstieg'] !== null) {
        $zahlen[] = array(number_format_i18n($daten['anstieg']), 'Hm Anstieg');
    }
    if ($daten['max'] !== null) {
        $zahlen[] = array(number_format_i18n($daten['max']), 'm höchster Punkt');
    }

    $konfig = array(
        'linie'       => $daten['linie'],
        'leaflet'     => $basis . 'leaflet/',
        'datenschutz' => get_privacy_policy_url(),
    );
    $html = '<figure class="rk rk-strecke rk-art-' . esc_attr($art) . '" style="--rk-hoehe:' . esc_attr($hoehe) . '">';
    $html .= '<div class="rk-zahlen">';
    foreach ($zahlen as $z) {
        $html .= '<div class="rk-zahl"><strong>' . esc_html($z[0]) . '</strong><span>' . esc_html($z[1]) . '</span></div>';
    }
    $html .= '</div>';
    $html .= '<div class="rk-flaeche"></div>';
    $html .= radional_karte_profil_svg($daten['profil'], $daten['min'], $daten['max']);
    $html .= '<script type="application/json" class="rk-konfig">' . wp_json_encode($konfig) . '</script>';
    $html .= '</figure>';
    return $html;
}

add_action('init', function () {
    add_shortcode('radional_strecke', 'radional_karte_strecke');
}, 20);

/* [sgpx] übernehmen, aber nur, wenn WP GPX Maps nicht (mehr) aktiv ist */
add_action('wp_loaded', function () {
    if (!shortcode_exists('sgpx')) {
        add_shortcode('sgpx', 'radional_karte_strecke');
    }
});
