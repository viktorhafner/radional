<?php
/**
 * Werkzeuge → Karten-Vorschläge
 *
 * Übernimmt vorbereitete Orte, Art, km und Hm für die bestehenden Berichte
 * (assets/vorschlaege.json). Regeln:
 * - Nur leere Felder werden gefüllt; was schon eingetragen ist, bleibt.
 * - Ein Vorschlag gilt nur für den Beitrag, dessen Titel passt. Stimmt die ID
 *   nicht (etwa nach einem Umzug), wird über den genauen Titel gesucht.
 * - Vorschläge mit niedriger Sicherheit sind nicht vorausgewählt.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_management_page(
        'Karten-Vorschläge',
        'Karten-Vorschläge',
        'edit_posts',
        'radional-karte-vorschlaege',
        'radional_karte_vorschlaege_seite'
    );
});

function radional_karte_vorschlaege_laden()
{
    $datei = plugin_dir_path(__DIR__) . 'assets/vorschlaege.json';
    $daten = is_readable($datei) ? json_decode((string) file_get_contents($datei), true) : null;
    return is_array($daten) ? $daten : array();
}

function radional_karte_titel_gleich($a, $b)
{
    $n = function ($t) {
        $t = html_entity_decode(wp_strip_all_tags((string) $t), ENT_QUOTES, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', str_replace(array('’', '‘', '–', '—'), array("'", "'", '-', '-'), $t)));
    };
    return $n($a) === $n($b);
}

/** Der Beitrag zu einem Vorschlag, oder null */
function radional_karte_vorschlag_beitrag($v)
{
    $post = get_post((int) $v['id']);
    if ($post && $post->post_type === 'post' && radional_karte_titel_gleich($post->post_title, $v['titel'])) {
        return $post;
    }
    $treffer = get_posts(array(
        'post_type'      => 'post',
        'post_status'    => array('publish', 'draft', 'private', 'future'),
        'title'          => html_entity_decode($v['titel'], ENT_QUOTES, 'UTF-8'),
        'posts_per_page' => 2,
    ));
    foreach ($treffer as $t) {
        if (radional_karte_titel_gleich($t->post_title, $v['titel'])) {
            return $t;
        }
    }
    return null;
}

/** Füllt nur leere Felder; gibt die Namen der ergänzten Felder zurück */
function radional_karte_vorschlag_uebernehmen($post_id, $v)
{
    $f = radional_karte_felder();
    $neu = array();
    $setze = function ($feld, $wert) use ($post_id, $f, &$neu) {
        if ($wert === null || $wert === '') {
            return;
        }
        if ((string) get_post_meta($post_id, $f[$feld], true) !== '') {
            return; // schon eingetragen – bleibt
        }
        update_post_meta($post_id, $f[$feld], $wert);
        $neu[] = $feld;
    };
    $koord = radional_karte_koordinaten($v['koordinaten']);
    if ($koord) {
        $setze('koordinaten', $koord[0] . ', ' . $koord[1]);
    }
    $setze('ort', sanitize_text_field($v['ort']));
    if (in_array($v['art'], array('bike', 'lauf', 'sonstiges'), true)) {
        $setze('art', $v['art']);
    }
    if (is_numeric($v['km']) && $v['km'] > 0) {
        $setze('km', (string) round((float) $v['km'], 2));
    }
    if (is_numeric($v['hm']) && $v['hm'] > 0) {
        $setze('hm', (string) (int) round((float) $v['hm']));
    }
    return $neu;
}

function radional_karte_vorschlaege_seite()
{
    if (!current_user_can('edit_posts')) {
        wp_die('Keine Berechtigung.');
    }
    $vorschlaege = radional_karte_vorschlaege_laden();
    $meldung = '';

    if (isset($_POST['radional_vorschlaege_nonce'])
        && wp_verify_nonce(sanitize_key($_POST['radional_vorschlaege_nonce']), 'radional_vorschlaege')) {
        $gewaehlt = isset($_POST['vorschlag']) ? array_map('intval', (array) $_POST['vorschlag']) : array();
        $ergaenzt = 0;
        $uebersprungen = array();
        foreach ($vorschlaege as $i => $v) {
            if (!in_array($i, $gewaehlt, true)) {
                continue;
            }
            $post = radional_karte_vorschlag_beitrag($v);
            if (!$post) {
                $uebersprungen[] = $v['titel'] . ' (Beitrag nicht gefunden)';
                continue;
            }
            if (!current_user_can('edit_post', $post->ID)) {
                $uebersprungen[] = $v['titel'] . ' (keine Berechtigung)';
                continue;
            }
            if (radional_karte_vorschlag_uebernehmen($post->ID, $v)) {
                $ergaenzt++;
            }
        }
        delete_transient('radional_karte_daten');
        $meldung = '<div class="notice notice-success"><p>' . esc_html(sprintf('%d Beiträge ergänzt.', $ergaenzt)) . '</p>';
        if ($uebersprungen) {
            $meldung .= '<p>Übersprungen: ' . esc_html(implode('; ', $uebersprungen)) . '</p>';
        }
        $meldung .= '</div>';
    }

    $art_name = array('bike' => 'Bike', 'lauf' => 'Lauf', 'sonstiges' => 'Sonstiges');
    $f = radional_karte_felder();
    ?>
    <div class="wrap">
        <h1>Karten-Vorschläge</h1>
        <?php echo $meldung; // aus esc_html() zusammengesetzt ?>
        <p>Vorbereitete Orte, Art, km und Hm für deine bestehenden Berichte. Übernommen werden nur
            <strong>leere</strong> Felder – was du schon eingetragen hast, bleibt unverändert.
            Die Koordinaten sind Näherungen (Start, Ziel oder Gipfel); mit „Karte“ prüfst du sie in Google Maps,
            ändern kannst du jeden Wert danach im Beitrag im Kasten „Karte (Challenge-Ort)“.</p>
        <p>Vorschläge mit <strong>niedriger</strong> Sicherheit sind nicht angehakt – bitte erst prüfen.</p>
        <form method="post">
            <?php wp_nonce_field('radional_vorschlaege', 'radional_vorschlaege_nonce'); ?>
            <p>
                <button type="button" class="button" onclick="document.querySelectorAll('.rk-v:not(:disabled)').forEach(function(c){c.checked=true})">Alle anhaken</button>
                <button type="button" class="button" onclick="document.querySelectorAll('.rk-v').forEach(function(c){c.checked=false})">Keine</button>
            </p>
            <table class="widefat striped">
                <thead><tr>
                    <th style="width:2em"></th><th>Beitrag</th><th>Ort</th><th>Koordinaten</th><th>Art</th>
                    <th>km</th><th>Hm</th><th>Sicherheit</th><th>Hinweis</th><th>Stand</th>
                </tr></thead>
                <tbody>
                <?php foreach ($vorschlaege as $i => $v) :
                    $post = radional_karte_vorschlag_beitrag($v);
                    $hat = $post ? (string) get_post_meta($post->ID, $f['koordinaten'], true) !== '' : false;
                    $koord = radional_karte_koordinaten($v['koordinaten']);
                    if (!$post) {
                        $stand = 'Beitrag nicht gefunden';
                    } elseif ($hat) {
                        $stand = 'Ort schon eingetragen – nur leere Felder werden ergänzt';
                    } else {
                        $stand = 'noch leer';
                    }
                    $vorgewaehlt = $post && !$hat && $v['sicherheit'] !== 'niedrig';
                    ?>
                    <tr>
                        <td><input type="checkbox" class="rk-v" name="vorschlag[]" value="<?php echo (int) $i; ?>"
                            aria-label="<?php echo esc_attr($v['titel']); ?>"
                            <?php checked($vorgewaehlt); ?> <?php disabled(!$post); ?>></td>
                        <td><?php if ($post) : ?><a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>"><?php echo esc_html($v['titel']); ?></a><?php else : echo esc_html($v['titel']); endif; ?></td>
                        <td><?php echo esc_html($v['ort']); ?></td>
                        <td><?php if ($koord) : ?><a href="<?php echo esc_url('https://www.google.com/maps?q=' . $koord[0] . ',' . $koord[1]); ?>" target="_blank" rel="noopener noreferrer">Karte</a><?php endif; ?></td>
                        <td><?php echo esc_html(isset($art_name[$v['art']]) ? $art_name[$v['art']] : ''); ?></td>
                        <td><?php echo $v['km'] ? esc_html(number_format_i18n((float) $v['km'], 0)) : '–'; ?></td>
                        <td><?php echo $v['hm'] ? esc_html(number_format_i18n((float) $v['hm'], 0)) : '–'; ?></td>
                        <td><?php echo esc_html($v['sicherheit']); ?></td>
                        <td><?php echo esc_html($v['hinweis']); ?></td>
                        <td><?php echo esc_html($stand); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="submit" class="button button-primary">Angehakte übernehmen</button></p>
        </form>
    </div>
    <?php
}
