<?php
/**
 * Updates für das Theme aus dem GitHub-Repository viktorhafner/radional.
 *
 * WordPress fragt regelmäßig nach Updates. Dabei liest dieses Theme die Datei
 * update.json aus dem Repository (höchstens alle 6 Stunden, sonst aus dem
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
        set_site_transient('radional_update_info', is_array($info) ? $info : array(), $info ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS);
        return $info;
    }

    /** Nur Pakete aus dem eigenen Repository annehmen */
    function radional_update_paket_ok($url)
    {
        return is_string($url) && preg_match('#^https://(raw\.githubusercontent\.com|github\.com)/viktorhafner/radional/#', $url)
            || (defined('RADIONAL_UPDATE_URL') && is_string($url) && strpos($url, dirname(RADIONAL_UPDATE_URL) . '/') === 0);
    }

    /* Nach einem Update sofort neu nachsehen, statt bis zu 6 Stunden das Alte zu glauben */
    add_action('upgrader_process_complete', function () {
        delete_site_transient('radional_update_info');
    });
}

add_filter('pre_set_site_transient_update_themes', function ($transient) {
    if (!is_object($transient)) {
        return $transient;
    }
    $stylesheet = 'radional-tourenbuch';
    $theme = wp_get_theme($stylesheet);
    if (!$theme->exists()) {
        return $transient;
    }
    $info = radional_update_info();
    $neu = $info['theme'] ?? null;
    if (!is_array($neu) || empty($neu['version']) || !radional_update_paket_ok($neu['package'] ?? '')) {
        return $transient;
    }
    $eintrag = array(
        'theme'        => $stylesheet,
        'new_version'  => $neu['version'],
        'url'          => 'https://github.com/viktorhafner/radional',
        'package'      => $neu['package'],
        'requires'     => $neu['requires'] ?? '',
        'requires_php' => $neu['requires_php'] ?? '',
    );
    if (version_compare($neu['version'], $theme->get('Version'), '>')) {
        $transient->response[$stylesheet] = $eintrag;
        unset($transient->no_update[$stylesheet]);
    } else {
        $transient->no_update[$stylesheet] = $eintrag;
        unset($transient->response[$stylesheet]);
    }
    return $transient;
});
