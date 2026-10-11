<?php
/**
 * Baustein „Alle Berichte“ (radional/uebersicht) für die Seite /blog/.
 *
 * Alle veröffentlichten Berichte auf einer Seite, nach Jahren gruppiert, als Karten
 * mit Titelbild, Art · Datum, Titel und km · Hm. Der Filter (Alle / Bike / Lauf /
 * Sonstiges) blendet nur aus; ohne JavaScript ist einfach alles sichtbar.
 */

if (!defined('ABSPATH')) {
    exit;
}

function radional_tb_uebersicht($attribute = array(), $inhalt = '', $block = null)
{
    $ids = get_posts(array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ));
    if (!$ids) {
        return '<p>Noch keine Berichte.</p>';
    }
    update_meta_cache('post', $ids);
    update_post_thumbnail_cache(new WP_Query(array('post__in' => $ids, 'posts_per_page' => -1, 'orderby' => 'post__in')));

    $jahre = array();
    $arten = array();
    foreach ($ids as $id) {
        $jahre[get_the_date('Y', $id)][] = $id;
        $art = radional_tb_art($id);
        if ($art) {
            $arten[$art] = true;
        }
    }

    $huelle = function_exists('get_block_wrapper_attributes')
        ? get_block_wrapper_attributes(array('class' => 'rt-uebersicht'))
        : 'class="rt-uebersicht"';
    $html = '<div ' . $huelle . '>';
    $html .= '<p class="rt-uebersicht-zahl">' . esc_html(count($ids) . ' Berichte seit ' . min(array_keys($jahre))) . '</p>';
    $html .= '<div class="rt-schnellsuche"><label class="screen-reader-text" for="rt-schnellsuche">Berichte durchsuchen</label>'
        . '<input type="search" id="rt-schnellsuche" placeholder="Bericht suchen – Ort, Berg, Rennen …" autocomplete="off">'
        . '<p class="rt-schnellsuche-leer" hidden>Kein Bericht passt. <a href="' . esc_url(home_url('/?s=')) . '" class="rt-volltext">Im ganzen Text suchen →</a></p></div>';
    if (count($arten) > 1) {
        $html .= '<div class="rt-filter" role="group" aria-label="Nach Art filtern">';
        $html .= '<button type="button" class="rt-filter-knopf" data-art="" aria-pressed="true">Alle</button>';
        foreach (array('bike', 'lauf', 'sonstiges') as $art) {
            if (isset($arten[$art])) {
                $html .= '<button type="button" class="rt-filter-knopf" data-art="' . esc_attr($art) . '" aria-pressed="false">'
                    . esc_html(radional_tb_art_name($art)) . '</button>';
            }
        }
        $html .= '</div>';
    }

    foreach ($jahre as $jahr => $liste) {
        $html .= '<section class="rt-jahr" aria-labelledby="rt-jahr-' . esc_attr($jahr) . '">';
        $html .= '<h2 id="rt-jahr-' . esc_attr($jahr) . '" class="rt-jahr-titel">' . esc_html($jahr)
            . ' <span class="rt-jahr-anzahl">' . esc_html(count($liste) === 1 ? '1 Bericht' : count($liste) . ' Berichte') . '</span></h2>';
        $html .= '<ul class="rt-karten">';
        foreach ($liste as $id) {
            $art = radional_tb_art($id);
            $link = get_permalink($id);
            $titel = get_the_title($id);
            $werte = array();
            $km = (float) get_post_meta($id, '_rk_km', true);
            $hm = (float) get_post_meta($id, '_rk_hm', true);
            if ($km > 0) {
                $werte[] = radional_tb_zahl($km) . ' km';
            }
            if ($hm > 0) {
                $werte[] = radional_tb_zahl($hm) . ' Hm';
            }
            $suche = wp_strip_all_tags($titel) . ' ' . get_post_meta($id, '_rk_ort', true) . ' ' . get_the_date('Y', $id);
            $html .= '<li class="rt-karte" data-art="' . esc_attr($art) . '" data-suche="' . esc_attr(remove_accents(mb_strtolower($suche))) . '">';
            $html .= '<a class="rt-karte-bild" href="' . esc_url($link) . '" tabindex="-1" aria-hidden="true">';
            if (has_post_thumbnail($id)) {
                $html .= get_the_post_thumbnail($id, 'medium_large', array('loading' => 'lazy', 'alt' => ''));
            }
            $html .= '</a>';
            $html .= '<p class="rt-kicker' . ($art ? ' rt-art-' . $art : '') . '">'
                . esc_html(implode(' · ', array_filter(array(radional_tb_art_name($art), get_the_date('j. F Y', $id))))) . '</p>';
            $html .= '<h3 class="rt-karte-titel"><a href="' . esc_url($link) . '">' . esc_html(wp_strip_all_tags($titel)) . '</a></h3>';
            if ($werte) {
                $html .= '<p class="rt-karte-werte">' . esc_html(implode(' · ', $werte)) . '</p>';
            }
            $html .= '</li>';
        }
        $html .= '</ul></section>';
    }
    return $html . '</div>';
}

add_action('init', function () {
    register_block_type('radional/uebersicht', array(
        'api_version'     => 3,
        'title'           => 'Alle Berichte (Übersicht)',
        'category'        => 'theme',
        'supports'        => array('html' => false, 'align' => array('wide', 'full')),
        'attributes'      => array('align' => array('type' => 'string')),
        'editor_script'   => 'radional-tourenbuch-bloecke',
        'render_callback' => 'radional_tb_uebersicht',
    ));
});

add_action('wp_enqueue_scripts', function () {
    if (is_home()) {
        wp_enqueue_script(
            'radional-tourenbuch-filter',
            get_theme_file_uri('assets/filter.js'),
            array(),
            RADIONAL_TB_VERSION,
            array('in_footer' => true, 'strategy' => 'defer')
        );
    }
});
