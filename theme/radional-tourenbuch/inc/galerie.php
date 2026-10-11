<?php
/**
 * Baustein „Alle Fotos und Videos“ (radional/galerie).
 *
 * Zeigt jedes Foto und Video aus der Mediathek, das zu einem veröffentlichten
 * Bericht oder einer Seite gehört – das neueste zuerst, nach Monaten gruppiert.
 * Weggelassen wird, was kein Foto von unterwegs ist: Logos und Zuschnitte ohne
 * Bericht, Screenshots, sehr kleine Bilder.
 *
 * Sortiert wird nach dem Datum in der Mediathek (Hochladen). Eine Aufnahmezeit
 * aus der Kamera steht in diesen Bildern nicht; das Hochladen liegt praktisch
 * immer beim Bericht.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Ist das ein Bild, das in die Galerie gehört? */
function radional_tb_galerie_passt($anhang, $eltern_status)
{
    if (!$anhang->post_parent || ($eltern_status[$anhang->post_parent] ?? '') !== 'publish') {
        return false; // Logos, Zuschnitte für Kopf und Symbol, Entwürfe
    }
    $datei = strtolower(wp_basename((string) get_post_meta($anhang->ID, '_wp_attached_file', true)));
    if (preg_match('/(screenshot|screen-shot|bildschirmfoto|logo|^cropped-|eventlogo|^\d{4}-\d\d-\d\d_\d\dh\d\d)/', $datei)) {
        return false;
    }
    if (strpos($anhang->post_mime_type, 'image/') === 0) {
        $meta = wp_get_attachment_metadata($anhang->ID);
        if (!empty($meta['width']) && max((int) $meta['width'], (int) $meta['height']) < 500) {
            return false;
        }
    }
    return true;
}

/** Fertige Galerie aus dem Zwischenspeicher; neu gebaut, wenn sich Medien oder Berichte ändern */
function radional_tb_galerie($attribute = array(), $inhalt = '', $block = null)
{
    $huelle = function_exists('get_block_wrapper_attributes')
        ? get_block_wrapper_attributes(array('class' => 'rt-galerie'))
        : 'class="rt-galerie"';
    $schluessel = 'radional_tb_galerie_' . RADIONAL_TB_VERSION;
    $innen = get_transient($schluessel);
    if (!is_string($innen)) {
        $innen = radional_tb_galerie_bauen();
        set_transient($schluessel, $innen, DAY_IN_SECONDS);
    }
    return $innen === '' ? '' : '<div ' . $huelle . '>' . $innen . '</div>';
}

function radional_tb_galerie_leeren()
{
    delete_transient('radional_tb_galerie_' . RADIONAL_TB_VERSION);
}
foreach (array('add_attachment', 'edit_attachment', 'delete_attachment', 'transition_post_status', 'deleted_post') as $haken) {
    add_action($haken, 'radional_tb_galerie_leeren');
}

/** Das Innere der Galerie (ohne äußere Hülle) */
function radional_tb_galerie_bauen()
{
    $anhaenge = get_posts(array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'post_mime_type' => array('image', 'video'),
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ));
    if (!$anhaenge) {
        return '';
    }

    // Status und Titel der Berichte auf einmal holen statt je Bild
    $eltern_ids = array_values(array_unique(array_filter(wp_list_pluck($anhaenge, 'post_parent'))));
    $eltern_status = array();
    $eltern = array();
    if ($eltern_ids) {
        foreach (get_posts(array('post_type' => 'any', 'post__in' => $eltern_ids, 'posts_per_page' => -1, 'post_status' => 'any', 'no_found_rows' => true)) as $e) {
            $eltern_status[$e->ID] = $e->post_status;
            $eltern[$e->ID] = $e;
        }
    }
    update_meta_cache('post', wp_list_pluck($anhaenge, 'ID'));

    $monate = array();
    $fotos = 0;
    $videos = 0;
    foreach ($anhaenge as $a) {
        if (!radional_tb_galerie_passt($a, $eltern_status)) {
            continue;
        }
        $monate[get_the_date('Y-m', $a)][] = $a;
        if (strpos($a->post_mime_type, 'video/') === 0) {
            $videos++;
        } else {
            $fotos++;
        }
    }
    if (!$monate) {
        return '';
    }

    $html = '';

    $zeile = number_format_i18n($fotos) . ' Fotos';
    if ($videos) {
        $zeile .= ' und ' . number_format_i18n($videos) . ($videos === 1 ? ' Video' : ' Videos');
    }
    $html .= '<p class="rt-galerie-zahl">' . esc_html($zeile) . ' · das neueste zuerst</p>';

    // Sprungmarken zu den Jahren
    $jahre = array();
    foreach (array_keys($monate) as $ym) {
        $jahre[substr($ym, 0, 4)] = substr($ym, 0, 4) . '-' . substr($ym, 5, 2);
    }
    $html .= '<nav class="rt-galerie-jahre" aria-label="Zu einem Jahr springen">';
    foreach ($jahre as $jahr => $erster_monat) {
        $html .= '<a href="#rt-g-' . esc_attr($erster_monat) . '">' . esc_html($jahr) . '</a>';
    }
    $html .= '</nav>';

    foreach ($monate as $ym => $liste) {
        $titel = date_i18n('F Y', strtotime($ym . '-01'));
        $html .= '<section class="rt-g-monat" id="rt-g-' . esc_attr($ym) . '" aria-label="' . esc_attr($titel) . '">';
        $html .= '<h2 class="rt-g-monat-titel">' . esc_html($titel) . '</h2><ul class="rt-g-raster">';
        foreach ($liste as $a) {
            $bericht = $eltern[$a->post_parent] ?? null;
            $daten = array(
                'data-datum'   => get_the_date('j. F Y', $a),
                'data-bericht' => $bericht ? wp_strip_all_tags(get_the_title($bericht)) : '',
                'data-link'    => $bericht ? get_permalink($bericht) : '',
            );
            $alt = trim((string) get_post_meta($a->ID, '_wp_attachment_image_alt', true));
            if ($alt === '' && $bericht) {
                $alt = 'Foto aus „' . $daten['data-bericht'] . '“';
            }
            $attr = '';
            foreach ($daten as $k => $v) {
                $attr .= ' ' . $k . '="' . esc_attr($v) . '"';
            }

            if (strpos($a->post_mime_type, 'video/') === 0) {
                $poster = $bericht ? get_the_post_thumbnail_url($bericht, 'medium') : '';
                $html .= '<li><a class="rt-g-element rt-g-video" href="' . esc_url(wp_get_attachment_url($a->ID)) . '" data-typ="video"' . $attr
                    . ' aria-label="' . esc_attr('Video aus „' . $daten['data-bericht'] . '“ abspielen') . '">';
                if ($poster) {
                    $html .= '<img src="' . esc_url($poster) . '" alt="" loading="lazy" decoding="async">';
                }
                $html .= '<span class="rt-g-play" aria-hidden="true"></span></a></li>';
            } else {
                $gross = wp_get_attachment_image_src($a->ID, '1536x1536');
                if (!$gross) {
                    $gross = wp_get_attachment_image_src($a->ID, 'large');
                }
                $html .= '<li><a class="rt-g-element" href="' . esc_url($gross ? $gross[0] : wp_get_attachment_url($a->ID)) . '" data-typ="bild"' . $attr . '>'
                    . wp_get_attachment_image($a->ID, 'medium', false, array(
                        'loading' => 'lazy',
                        'decoding' => 'async',
                        'alt' => $alt,
                        'sizes' => '(max-width: 600px) 50vw, 240px',
                    ))
                    . '</a></li>';
            }
        }
        $html .= '</ul></section>';
    }
    return $html;
}

add_action('init', function () {
    register_block_type('radional/galerie', array(
        'api_version'     => 3,
        'title'           => 'Alle Fotos und Videos (Galerie)',
        'category'        => 'theme',
        'supports'        => array('html' => false, 'align' => array('wide', 'full')),
        'attributes'      => array('align' => array('type' => 'string')),
        'editor_script'   => 'radional-tourenbuch-bloecke',
        'render_callback' => 'radional_tb_galerie',
    ));
});

add_action('wp_enqueue_scripts', function () {
    wp_register_script(
        'radional-tourenbuch-galerie',
        get_theme_file_uri('assets/galerie.js'),
        array(),
        RADIONAL_TB_VERSION,
        array('in_footer' => true, 'strategy' => 'defer')
    );
});

/* Skript nur dort laden, wo die Galerie tatsächlich steht */
add_filter('render_block_radional/galerie', function ($html) {
    if ($html !== '') {
        wp_enqueue_script('radional-tourenbuch-galerie');
    }
    return $html;
});
