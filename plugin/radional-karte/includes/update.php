<?php
/**
 * Updates für das Plugin aus dem GitHub-Repository viktorhafner/radional.
 *
 * WordPress fragt regelmäßig nach Updates. Dabei liest dieses Plugin die Datei
 * update.json aus dem Repository (höchstens alle 2 Stunden, sonst aus dem
 * Zwischenspeicher). Steht dort eine höhere Version, erscheint das Update unter
 * „Dashboard → Aktualisierungen“ und wird von dort mit einem Klick installiert.
 *
 * Für Tests lässt sich die Adresse in der wp-config.php umbiegen:
 *   define('RADIONAL_UPDATE_URL', 'https://…/update.json');
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('radional_update_info')) {
    /** Inhalt von update.json, oder null wenn nicht erreichbar */
    function radional_update_info($neu_holen = false)
    {
        $adresse = defined('RADIONAL_UPDATE_URL')
            ? RADIONAL_UPDATE_URL
            : 'https://raw.githubusercontent.com/viktorhafner/radional/main/update.json';
        $info = $neu_holen ? false : get_site_transient('radional_update_info');
        if (is_array($info)) {
            return $info;
        }
        $antwort = wp_remote_get($adresse, array('timeout' => 8, 'headers' => array('Accept' => 'application/json')));
        $info = null;
        if (!is_wp_error($antwort) && wp_remote_retrieve_response_code($antwort) === 200) {
            $daten = json_decode(wp_remote_retrieve_body($antwort), true);
            if (is_array($daten)) {
                $info = $daten;
            }
        }
        // auch einen Fehlschlag kurz merken, damit nicht jeder Seitenaufruf im Admin wartet
        set_site_transient('radional_update_info', is_array($info) ? $info : array(), $info ? 2 * HOUR_IN_SECONDS : HOUR_IN_SECONDS);
        return $info;
    }

    /** Nur Pakete aus dem eigenen Repository annehmen */
    function radional_update_paket_ok($url)
    {
        return is_string($url) && preg_match('#^https://(raw\.githubusercontent\.com|github\.com)/viktorhafner/radional/#', $url)
            || (defined('RADIONAL_UPDATE_URL') && is_string($url) && strpos($url, dirname(RADIONAL_UPDATE_URL) . '/') === 0);
    }

    /* Nach einem Update sofort neu nachsehen, statt bis zu 2 Stunden das Alte zu glauben */
    add_action('upgrader_process_complete', function () {
        delete_site_transient('radional_update_info');
    });

    /* „Erneut prüfen“ unter Dashboard → Aktualisierungen fragt immer frisch bei GitHub nach.
       Ohne das blieb bis zu 6 Stunden die alte Versionsliste stehen, obwohl längst eine neue da war.
       Priorität 1: vor wp_update_themes/wp_update_plugins, die WordPress am selben Haken aufruft. */
    add_action('load-update-core.php', function () {
        if (!empty($_GET['force-check']) && current_user_can('update_themes')) {
            delete_site_transient('radional_update_info');
        }
    }, 1);
}

add_filter('pre_set_site_transient_update_plugins', function ($transient) {
    if (!is_object($transient)) {
        return $transient;
    }
    $datei = plugin_basename(dirname(__DIR__) . '/radional-karte.php');
    $info = radional_update_info();
    $neu = $info['karte'] ?? null;
    if (!is_array($neu) || empty($neu['version']) || !radional_update_paket_ok($neu['package'] ?? '')) {
        return $transient;
    }
    $eintrag = (object) array(
        'id'           => 'github.com/viktorhafner/radional/radional-karte',
        'slug'         => 'radional-karte',
        'plugin'       => $datei,
        'new_version'  => $neu['version'],
        'url'          => 'https://github.com/viktorhafner/radional',
        'package'      => $neu['package'],
        'requires'     => $neu['requires'] ?? '',
        'requires_php' => $neu['requires_php'] ?? '',
        'icons'        => array(),
        'banners'      => array(),
    );
    if (version_compare($neu['version'], RADIONAL_KARTE_VERSION, '>')) {
        $transient->response[$datei] = $eintrag;
        unset($transient->no_update[$datei]);
    } else {
        $transient->no_update[$datei] = $eintrag;
        unset($transient->response[$datei]);
    }
    return $transient;
});

/* „Details ansehen“ in der Update-Liste: kurze Beschreibung statt Fehlermeldung */
add_filter('plugins_api', function ($ergebnis, $aktion, $args) {
    if ($aktion !== 'plugin_information' || empty($args->slug) || $args->slug !== 'radional-karte') {
        return $ergebnis;
    }
    $info = radional_update_info();
    $neu = $info['karte'] ?? array();
    return (object) array(
        'name'          => 'Radional Karte',
        'slug'          => 'radional-karte',
        'version'       => $neu['version'] ?? RADIONAL_KARTE_VERSION,
        'author'        => 'radional.at',
        'homepage'      => 'https://github.com/viktorhafner/radional',
        'requires'      => $neu['requires'] ?? '',
        'requires_php'  => $neu['requires_php'] ?? '',
        'download_link' => $neu['package'] ?? '',
        'sections'      => array(
            'description' => 'Interaktive Karte aller Challenges auf radional.at.',
            'changelog'   => esc_html($neu['changelog'] ?? ''),
        ),
    );
}, 10, 3);
