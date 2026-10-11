<?php
/**
 * E-Mail-Adressen vor Spam-Sammlern schützen.
 *
 * Im Inhalt von Seiten und Berichten wird jede E-Mail-Adresse – ob als Text oder
 * als mailto-Link – ersetzt durch:
 *   <span class="rt-mail" data-rt-mail="…">name (at) domain</span>
 * Im Attribut steht die Adresse umgedreht und Base64-kodiert. Das Skript
 * assets/email.js macht daraus im Browser wieder einen normalen, anklickbaren
 * Link mit der richtigen Adresse. Ohne Skript bleibt „name (at) domain“ lesbar.
 *
 * Programme, die Seiten nach „etwas@etwas.at“ durchsuchen, finden so nichts.
 */

if (!defined('ABSPATH')) {
    exit;
}

function radional_tb_mail_span($adresse, $text = '')
{
    $adresse = trim(html_entity_decode($adresse, ENT_QUOTES, 'UTF-8'));
    if (!is_email($adresse)) {
        return null;
    }
    $teile = explode('@', $adresse, 2);
    $ersatz = $teile[0] . ' (at) ' . $teile[1];
    // Eigener Linktext (z. B. „Schreib mir“) bleibt, sofern er nicht selbst die Adresse ist
    $text = trim(wp_strip_all_tags((string) $text));
    $zeigen = ($text === '' || strpos($text, '@') !== false) ? $ersatz : $text;
    $GLOBALS['radional_tb_mail_gefunden'] = true;
    return '<span class="rt-mail" data-rt-mail="' . esc_attr(base64_encode(strrev($adresse))) . '"'
        . ($zeigen !== $ersatz ? ' data-rt-text="1"' : '') . '>' . esc_html($zeigen) . '</span>';
}

function radional_tb_mail_schuetzen($html)
{
    if (strpos($html, '@') === false || is_admin()) {
        return $html;
    }
    // 1. mailto-Links als Ganzes
    $html = preg_replace_callback(
        '#<a\s[^>]*href=(["\'])mailto:([^"\'?]+)(?:\?[^"\']*)?\1[^>]*>(.*?)</a>#is',
        function ($m) {
            $span = radional_tb_mail_span(rawurldecode($m[2]), $m[3]);
            return $span === null ? $m[0] : $span;
        },
        $html
    );
    // 2. Adressen im Text – nur außerhalb von Tags und nicht in script/style
    $teile = preg_split('#(<script\b.*?</script>|<style\b.*?</style>|<[^>]+>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($teile as $i => $teil) {
        if ($teil === '' || $teil[0] === '<' || strpos($teil, '@') === false) {
            continue;
        }
        $teile[$i] = preg_replace_callback(
            '/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/',
            function ($m) {
                $span = radional_tb_mail_span($m[0]);
                return $span === null ? $m[0] : $span;
            },
            $teil
        );
    }
    return implode('', $teile);
}

add_filter('the_content', function ($html) {
    $neu = radional_tb_mail_schuetzen($html);
    if (!empty($GLOBALS['radional_tb_mail_gefunden']) && !is_feed()) {
        wp_enqueue_script(
            'radional-tourenbuch-email',
            get_theme_file_uri('assets/email.js'),
            array(),
            RADIONAL_TB_VERSION,
            array('in_footer' => true, 'strategy' => 'defer')
        );
    }
    return $neu;
}, 100);

/* Auch in Kurzbeschreibungen: Yoast SEO schreibt den Seitenanfang in den Seitenkopf
   (og:description, Twitter, Schema), WordPress in Auszüge – dort ohne Skript,
   also nur als „name (at) domain“. */
function radional_tb_mail_text($text)
{
    if (!is_string($text) || strpos($text, '@') === false) {
        return $text;
    }
    return preg_replace_callback('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', function ($m) {
        return is_email($m[0]) ? str_replace('@', ' (at) ', $m[0]) : $m[0];
    }, $text);
}

function radional_tb_mail_text_tief($wert)
{
    if (is_string($wert)) {
        return radional_tb_mail_text($wert);
    }
    if (is_array($wert)) {
        foreach ($wert as $k => $v) {
            $wert[$k] = radional_tb_mail_text_tief($v);
        }
    }
    return $wert;
}

foreach (array('wpseo_metadesc', 'wpseo_opengraph_desc', 'wpseo_twitter_description', 'get_the_excerpt') as $filter) {
    add_filter($filter, 'radional_tb_mail_text', 99);
}
add_filter('wpseo_schema_graph', 'radional_tb_mail_text_tief', 99);
