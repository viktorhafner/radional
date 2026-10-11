<?php
/**
 * Radional Tourenbuch – Child-Theme von Twenty Twenty-Five.
 *
 * Die Bausteine (Blöcke) unten lesen die Felder, die das Plugin „Radional Karte“
 * je Beitrag speichert (_rk_art, _rk_km, _rk_hm, _rk_ort). Ohne Plugin oder ohne
 * Eintrag zeigen sie, was sie haben, und sonst nichts.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('RADIONAL_TB_VERSION', '1.4.0');

require_once __DIR__ . '/inc/youtube.php';
require_once __DIR__ . '/inc/uebersicht.php';
require_once __DIR__ . '/inc/galerie.php';
require_once __DIR__ . '/inc/update.php';
require_once __DIR__ . '/inc/rechtstexte.php';

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'radional-tourenbuch',
        get_stylesheet_uri(),
        array(),
        RADIONAL_TB_VERSION
    );
});

/* Dieselben Regeln auch im Block-Editor, damit er aussieht wie die Seite */
add_action('after_setup_theme', function () {
    add_editor_style('style.css');
});

/* ------------------------------------------------------------------ */
/* Werte eines Beitrags                                                */
/* ------------------------------------------------------------------ */

function radional_tb_post_id($block)
{
    if (is_object($block) && isset($block->context['postId'])) {
        return (int) $block->context['postId'];
    }
    return (int) get_the_ID();
}

function radional_tb_art($post_id)
{
    $art = get_post_meta($post_id, '_rk_art', true);
    return in_array($art, array('bike', 'lauf', 'sonstiges'), true) ? $art : '';
}

function radional_tb_art_name($art)
{
    $namen = array('bike' => 'Bike', 'lauf' => 'Lauf', 'sonstiges' => 'Unterwegs');
    return isset($namen[$art]) ? $namen[$art] : '';
}

/** Zahl in österreichischer Schreibweise: 3500 -> "3 500", 21.5 -> "21,5" */
function radional_tb_zahl($wert)
{
    $wert = (float) $wert;
    $stellen = (floor($wert) == $wert) ? 0 : 1;
    return number_format($wert, $stellen, ',', "\u{202F}");
}

/** Geschätzte Lesezeit in Minuten, bei 200 Wörtern je Minute */
function radional_tb_lesezeit($post_id)
{
    $text = wp_strip_all_tags(strip_shortcodes(get_post_field('post_content', $post_id)));
    $woerter = count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY));
    return max(1, (int) round($woerter / 200));
}

/* ------------------------------------------------------------------ */
/* Bausteine                                                           */
/* ------------------------------------------------------------------ */

/** „Lauf · 27. Juli 2026“ */
function radional_tb_kicker($attribute, $inhalt, $block)
{
    $id = radional_tb_post_id($block);
    if (!$id) {
        return '';
    }
    $art = radional_tb_art($id);
    $teile = array();
    if ($art) {
        $teile[] = radional_tb_art_name($art);
    }
    $teile[] = get_the_date('j. F Y', $id);
    $ausrichtung = !empty($attribute['textAlign']) ? ' has-text-align-' . sanitize_html_class($attribute['textAlign']) : '';
    return '<p class="rt-kicker' . ($art ? ' rt-art-' . $art : '') . $ausrichtung . '">'
        . esc_html(implode(' · ', $teile)) . '</p>';
}

/** „57 km · 3 500 Hm · Lesezeit 6 Min.“ */
function radional_tb_eckdaten($attribute, $inhalt, $block)
{
    $id = radional_tb_post_id($block);
    if (!$id) {
        return '';
    }
    $teile = array();
    $km = (float) get_post_meta($id, '_rk_km', true);
    $hm = (float) get_post_meta($id, '_rk_hm', true);
    if ($km > 0) {
        $teile[] = radional_tb_zahl($km) . ' km';
    }
    if ($hm > 0) {
        $teile[] = radional_tb_zahl($hm) . ' Hm';
    }
    $teile[] = 'Lesezeit ' . radional_tb_lesezeit($id) . ' Min.';
    $html = '';
    foreach ($teile as $i => $teil) {
        if ($i) {
            $html .= '<span aria-hidden="true">·</span>';
        }
        $html .= '<span>' . esc_html($teil) . '</span>';
    }
    return '<p class="rt-eckdaten">' . $html . '</p>';
}

/** Nur ein selbst geschriebener Auszug – der automatische wiederholte bloß den ersten Absatz */
function radional_tb_vorspann($attribute, $inhalt, $block)
{
    $id = radional_tb_post_id($block);
    if (!$id || !has_excerpt($id)) {
        return '';
    }
    $ausrichtung = !empty($attribute['textAlign']) ? ' has-text-align-' . sanitize_html_class($attribute['textAlign']) : '';
    return '<p class="rt-vorspann' . $ausrichtung . '">' . esc_html(get_the_excerpt($id)) . '</p>';
}

/** Kasten mit Art, km, Hm und Ort; erscheint nur, wenn mindestens ein Wert eingetragen ist */
function radional_tb_steckbrief($attribute, $inhalt, $block)
{
    $id = radional_tb_post_id($block);
    if (!$id) {
        return '';
    }
    $art = radional_tb_art($id);
    $km = (float) get_post_meta($id, '_rk_km', true);
    $hm = (float) get_post_meta($id, '_rk_hm', true);
    $ort = (string) get_post_meta($id, '_rk_ort', true);
    $koordinaten = (string) get_post_meta($id, '_rk_koordinaten', true);

    $zeilen = array();
    if ($art) {
        $zeilen[] = array('Art', '<span class="rt-art-' . $art . '">' . esc_html(radional_tb_art_name($art)) . '</span>');
    }
    if ($km > 0) {
        $zeilen[] = array('Distanz', esc_html(radional_tb_zahl($km) . ' km'));
    }
    if ($hm > 0) {
        $zeilen[] = array('Höhenmeter', esc_html(radional_tb_zahl($hm) . ' Hm'));
    }
    if ($ort !== '') {
        $zeilen[] = array('Ort', esc_html($ort));
    }
    $zeilen[] = array('Datum', esc_html(get_the_date('j. F Y', $id)));
    if (count($zeilen) < 2) {
        return ''; // nur das Datum – das steht schon über dem Titel
    }

    $html = '<aside class="rt-steckbrief" aria-label="Steckbrief"><div class="rt-steckbrief-box"><h2>Steckbrief</h2><dl>';
    foreach ($zeilen as $z) {
        $html .= '<dt>' . esc_html($z[0]) . '</dt><dd>' . $z[1] . '</dd>';
    }
    $html .= '</dl></div>';
    if ($koordinaten !== '') {
        $html .= '<a class="rt-steckbrief-karte" href="' . esc_url(home_url('/#karte')) . '">Auf der großen Karte zeigen →</a>';
    }
    return $html . '</aside>';
}

add_action('init', function () {
    wp_register_script(
        'radional-tourenbuch-bloecke',
        get_theme_file_uri('assets/bloecke.js'),
        array('wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor'),
        RADIONAL_TB_VERSION,
        true
    );
    $bloecke = array(
        'kicker'     => array('Überzeile (Art · Datum)', 'radional_tb_kicker'),
        'eckdaten'   => array('Eckdaten (km · Hm · Lesezeit)', 'radional_tb_eckdaten'),
        'vorspann'   => array('Vorspann (eigener Auszug)', 'radional_tb_vorspann'),
        'steckbrief' => array('Steckbrief', 'radional_tb_steckbrief'),
    );
    foreach ($bloecke as $name => $b) {
        register_block_type('radional/' . $name, array(
            'api_version'     => 3,
            'title'           => $b[0],
            'category'        => 'theme',
            'uses_context'    => array('postId', 'postType'),
            'attributes'      => array('textAlign' => array('type' => 'string')),
            'supports'        => array('html' => false),
            'editor_script'   => 'radional-tourenbuch-bloecke',
            'render_callback' => $b[1],
        ));
    }
});

/* Shortcode-Blöcke in Templates und Mustern ausführen – dort tut WordPress das nicht von selbst.
   So läuft [radional_karte] auch im Startseiten-Template. */
add_filter('render_block_core/shortcode', function ($inhalt) {
    return do_shortcode($inhalt);
});

/* Muster-Kategorie für die Abschnitte der Startseite */
add_action('init', function () {
    register_block_pattern_category('radional', array('label' => 'Radional'));
});
