<?php
/**
 * YouTube erst nach Klick.
 *
 * Jedes YouTube-iframe im Inhalt eines Berichts wird durch eine Fläche ersetzt
 * (Titelbild des Berichts, Titel des Videos, Knopf „Video abspielen“). Erst der
 * Klick lädt das Video – über youtube-nocookie.com. Vorher geht keine einzige
 * Anfrage an Google, auch kein Vorschaubild (das käme von i.ytimg.com).
 *
 * Ersetzt wird am Ende von the_content, damit alles erfasst ist: alte, von Hand
 * eingefügte iframes, automatisch eingebettete Links und Einbettungs-Blöcke.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** "https://www.youtube.com/embed/ID?start=9" -> "https://www.youtube-nocookie.com/embed/ID?start=9&autoplay=1", sonst null */
function radional_tb_youtube_adresse($src)
{
    $src = html_entity_decode((string) $src, ENT_QUOTES, 'UTF-8');
    if (strpos($src, '//') === 0) {
        $src = 'https:' . $src;
    }
    $teile = wp_parse_url($src);
    if (empty($teile['host']) || empty($teile['path'])) {
        return null;
    }
    if (!preg_match('/(^|\.)(youtube\.com|youtube-nocookie\.com)$/i', $teile['host'])) {
        return null;
    }
    if (!preg_match('#^/embed/([A-Za-z0-9_-]+)$#', $teile['path'], $m)) {
        return null;
    }
    $abfrage = array();
    if (!empty($teile['query'])) {
        wp_parse_str($teile['query'], $abfrage);
    }
    unset($abfrage['feature']);
    $abfrage['autoplay'] = '1';
    $abfrage['rel'] = '0';
    return array(
        'id'      => $m[1],
        'abspiel' => add_query_arg(array_map('rawurlencode', $abfrage), 'https://www.youtube-nocookie.com/embed/' . $m[1]),
    );
}

function radional_tb_youtube_flaeche($iframe)
{
    if (!preg_match('/\ssrc=(["\'])(.*?)\1/i', $iframe, $s)) {
        return $iframe;
    }
    $yt = radional_tb_youtube_adresse($s[2]);
    if (!$yt) {
        return $iframe;
    }
    $titel = '';
    if (preg_match('/\stitle=(["\'])(.*?)\1/i', $iframe, $t)) {
        $titel = html_entity_decode($t[2], ENT_QUOTES, 'UTF-8');
    }
    $video_id = $yt['id'];
    $ansehen = 'https://www.youtube.com/watch?v=' . rawurlencode($video_id);
    if ($video_id === 'videoseries') {
        $ansehen = 'https://www.youtube.com/';
    }
    $bild = get_the_post_thumbnail_url(get_the_ID(), 'large');
    $datenschutz = get_privacy_policy_url();

    $html = '<div class="rt-yt" data-rt-yt="' . esc_url($yt['abspiel']) . '" data-rt-titel="' . esc_attr($titel ?: 'YouTube-Video') . '">';
    if ($bild) {
        $html .= '<img class="rt-yt-bild" src="' . esc_url($bild) . '" alt="" loading="lazy" decoding="async">';
    }
    $html .= '<div class="rt-yt-inhalt">';
    $html .= '<button type="button" class="rt-yt-knopf"><span class="rt-yt-play" aria-hidden="true"></span>'
        . '<span>Video abspielen</span></button>';
    if ($titel) {
        $html .= '<p class="rt-yt-titel">' . esc_html($titel) . '</p>';
    }
    $html .= '<p class="rt-yt-hinweis">Beim Abspielen wird eine Verbindung zu YouTube (Google) hergestellt'
        . ($datenschutz ? ' – <a href="' . esc_url($datenschutz) . '">Datenschutz</a>' : '')
        . '. <a href="' . esc_url($ansehen) . '" target="_blank" rel="noopener">Auf YouTube ansehen</a></p>';
    $html .= '<label class="rt-yt-merken"><input type="checkbox"> YouTube-Videos künftig immer laden</label>';
    $html .= '</div></div>';
    return $html;
}

add_filter('the_content', function ($inhalt) {
    if (stripos($inhalt, 'youtu') === false || is_feed() || is_admin()) {
        return $inhalt;
    }
    return preg_replace_callback('/<iframe\b[^>]*>\s*<\/iframe>/is', function ($m) {
        return radional_tb_youtube_flaeche($m[0]);
    }, $inhalt);
}, 99);

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script(
        'radional-tourenbuch-youtube',
        get_theme_file_uri('assets/youtube.js'),
        array(),
        RADIONAL_TB_VERSION,
        array('in_footer' => true, 'strategy' => 'defer')
    );
});
