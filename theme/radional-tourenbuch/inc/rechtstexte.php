<?php
/**
 * Impressum und Datenschutzerklärung als Entwurf anlegen.
 *
 * Beim ersten Aufruf des Admin-Bereichs durch eine Administratorin oder einen
 * Administrator legt das Theme beide Seiten an – als ENTWURF, damit nichts
 * Unfertiges öffentlich wird. Platzhalter in eckigen Klammern sind auszufüllen,
 * danach „Veröffentlichen“.
 *
 * Es wird nie etwas überschrieben: Gibt es schon eine Seite /impressum/ bzw.
 * /datenschutz/ (in welchem Zustand auch immer), bleibt sie unberührt. Als
 * Datenschutzseite unter „Einstellungen → Datenschutz“ wird die neue Seite nur
 * eingetragen, wenn dort noch keine veröffentlichte steht.
 */

if (!defined('ABSPATH')) {
    exit;
}

function radional_tb_seite_mit_slug($slug)
{
    $seiten = get_posts(array(
        'post_type'      => 'page',
        'name'           => $slug,
        'post_status'    => array('publish', 'draft', 'pending', 'private', 'future', 'trash'),
        'posts_per_page' => 1,
    ));
    return $seiten ? $seiten[0] : null;
}

function radional_tb_absatz($text)
{
    return "<!-- wp:paragraph -->\n<p>" . $text . "</p>\n<!-- /wp:paragraph -->\n\n";
}

function radional_tb_ueberschrift($text, $ebene = 2)
{
    $attr = $ebene === 2 ? '' : ' {"level":' . (int) $ebene . '}';
    return '<!-- wp:heading' . $attr . " -->\n<h" . $ebene . ' class="wp-block-heading">' . $text . '</h' . $ebene . ">\n<!-- /wp:heading -->\n\n";
}

function radional_tb_liste($punkte)
{
    $html = "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
    foreach ($punkte as $p) {
        $html .= "<!-- wp:list-item -->\n<li>" . $p . "</li>\n<!-- /wp:list-item -->\n\n";
    }
    return $html . "</ul>\n<!-- /wp:list -->\n\n";
}

function radional_tb_impressum_inhalt()
{
    $a = 'radional_tb_absatz';
    return $a('<strong>Offenlegung gemäß § 25 Mediengesetz</strong>')
        . $a('Medieninhaber und Herausgeber: Viktor Hafner<br>Wohnort: <strong>[WOHNORT]</strong>, Österreich<br>E-Mail: <strong>[E-MAIL-ADRESSE]</strong>')
        . $a('<strong>Grundlegende Richtung:</strong> radional.at ist ein privater Blog. Er berichtet über eigene Rad-, Lauf- und Trailrun-Challenges, Touren und Ausrüstung. Es gibt keine Werbung, keine bezahlten Beiträge und keinen Verkauf.')
        . $a('<strong>Urheberrecht:</strong> Texte, Fotos, Videos und das Logo auf dieser Website stammen, soweit nicht anders angegeben, von Viktor Hafner. Eine Verwendung außerhalb dieser Website ist nur mit vorheriger Zustimmung erlaubt.')
        . $a('<strong>Links:</strong> Diese Website verlinkt auf Seiten anderer Anbieter, etwa Veranstalter, Strava oder YouTube. Für deren Inhalte sind ausschließlich die jeweiligen Betreiber verantwortlich. Werden rechtswidrige Inhalte bekannt, wird der Link umgehend entfernt.')
        . $a('<strong>Datenschutz:</strong> siehe <a href="/datenschutz/">Datenschutzerklärung</a>.');
}

function radional_tb_datenschutz_inhalt()
{
    $a = 'radional_tb_absatz';
    $h = 'radional_tb_ueberschrift';
    $google = '<a href="https://policies.google.com/privacy">policies.google.com/privacy</a>';
    return $h('1. Verantwortlicher')
        . $a('Viktor Hafner, <strong>[WOHNORT]</strong>, Österreich, E-Mail: <strong>[E-MAIL-ADRESSE]</strong>. radional.at ist ein privater Blog ohne Werbung und ohne Verkauf.')
        . $h('2. Hosting und Server-Protokolle')
        . $a('Diese Website liegt bei <strong>[HOSTER]</strong>, <strong>[SITZ DES HOSTERS]</strong>. Bei jedem Aufruf speichert der Server automatisch IP-Adresse, Datum und Uhrzeit, die aufgerufene Seite, die zuvor besuchte Seite sowie Browser und Betriebssystem. Das ist für einen sicheren und stabilen Betrieb nötig (Art. 6 Abs. 1 lit. f DSGVO). Die Protokolle werden nach der vom Hoster festgelegten Frist gelöscht.')
        . $h('3. Schriften')
        . $a('Die Schriften dieser Website liegen auf dem eigenen Server. Beim Laden werden keine Daten an Google oder andere Schriftanbieter übertragen.')
        . $h('4. Jetpack: Statistik, Bilder, Kommentare, „Gefällt mir“ und Teilen')
        . $a('Diese Website nutzt Jetpack der Automattic Inc., San Francisco, USA. Dabei werden folgende Daten verarbeitet:')
        . radional_tb_liste(array(
            '<strong>Besucherstatistik:</strong> IP-Adresse, aufgerufene Seite, Herkunftsseite, Browser und Zeitpunkt, um zu sehen, welche Berichte gelesen werden (Art. 6 Abs. 1 lit. f DSGVO).',
            '<strong>Bilder:</strong> Fotos werden teilweise über das Bildnetz von Jetpack (i0.wp.com) ausgeliefert, damit sie schneller laden. Dabei erhält Automattic die IP-Adresse (Art. 6 Abs. 1 lit. f DSGVO).',
            '<strong>Kommentare:</strong> Wer kommentiert, gibt Name, E-Mail-Adresse und optional eine Website an; zusätzlich werden IP-Adresse und Zeitpunkt gespeichert. Die Kommentarfunktion läuft über Jetpack. Aus der E-Mail-Adresse wird eine verschlüsselte Zeichenfolge gebildet und an den Dienst Gravatar (ebenfalls Automattic) übermittelt, um ein Profilbild anzuzeigen, sofern eines hinterlegt ist. Rechtsgrundlage ist die freiwillige Angabe beim Kommentieren (Art. 6 Abs. 1 lit. a DSGVO). Kommentare bleiben gespeichert, bis du ihre Löschung verlangst.',
            '<strong>„Gefällt mir“ und Teilen-Knöpfe:</strong> Die Knöpfe werden von Automattic geladen. Daten an Facebook oder andere Netzwerke gehen erst, wenn du einen Teilen-Knopf anklickst.',
        ))
        . $a('Daten können dabei in die USA übermittelt werden. Datenschutzerklärung von Automattic: <a href="https://automattic.com/privacy/">automattic.com/privacy</a>.')
        . $h('5. Eingebettete Videos (YouTube)')
        . $a('Viele Berichte enthalten Videos von YouTube (Google Ireland Limited, Gordon House, Barrow Street, Dublin 4, Irland). Sie werden erst geladen, wenn du auf „Video abspielen“ klickst; vorher stellt dein Browser keine Verbindung zu YouTube her. Nach dem Klick wird das Video über youtube-nocookie.com abgespielt, und Google erhält deine IP-Adresse und die aufgerufene Seite; beim Abspielen kann YouTube Daten im Browser speichern. Rechtsgrundlage ist deine Einwilligung durch den Klick (Art. 6 Abs. 1 lit. a DSGVO). Wählst du „YouTube-Videos künftig immer laden“, wird das nur in deinem Browser gespeichert (localStorage); löschst du die Websitedaten, ist es widerrufen. Daten können in die USA übermittelt werden. Datenschutzerklärung: ' . $google . '.')
        . $h('6. Strava, Komoot und Embedly')
        . $a('Einige Berichte zeigen Touren von Strava (Strava, Inc., San Francisco, USA) oder Komoot (komoot GmbH, Potsdam, Deutschland). Eingebettete Inhalte anderer Seiten werden teils über Embedly (A Medium Corporation, San Francisco, USA) geladen. Beim Aufruf erhalten diese Anbieter deine IP-Adresse und die aufgerufene Seite. Die Streckenvorschau älterer Strava-Touren ist eine Kartengrafik von Google Maps (Google Ireland Limited); auch dabei erhält Google deine IP-Adresse. Daten können in die USA übermittelt werden. Datenschutzerklärungen: <a href="https://www.strava.com/legal/privacy">strava.com/legal/privacy</a>, <a href="https://www.komoot.com/privacy">komoot.com/privacy</a>, ' . $google . '.')
        . $h('7. Karten (OpenStreetMap)')
        . $a('Die Karte auf der Startseite wird erst geladen, wenn du auf „Karte laden“ klickst. Dann ruft dein Browser Kartenkacheln von der OpenStreetMap Foundation (St John’s Innovation Centre, Cowley Road, Cambridge, CB4 0WS, Vereinigtes Königreich) ab, die dabei deine IP-Adresse erhält. Rechtsgrundlage ist deine Einwilligung (Art. 6 Abs. 1 lit. a DSGVO); für das Vereinigte Königreich besteht ein Angemessenheitsbeschluss der EU-Kommission. Wählst du „Karte künftig immer laden“, wird diese Entscheidung nur in deinem Browser gespeichert (localStorage); löschst du die Websitedaten deines Browsers, ist sie widerrufen. Streckenkarten in einzelnen Berichten nutzen ebenfalls OpenStreetMap. Datenschutzerklärung: <a href="https://osmfoundation.org/wiki/Privacy_Policy">osmfoundation.org/wiki/Privacy_Policy</a>.')
        . $h('8. Links zu sozialen Netzwerken')
        . $a('Die Links zu Instagram und Facebook im Fuß der Seite sind einfache Links. Daten erhält der jeweilige Anbieter erst, wenn du einen Link anklickst.')
        . $h('9. Kontakt per E-Mail')
        . $a('Schreibst du eine E-Mail, werden deine Angaben nur zur Beantwortung verwendet und danach gelöscht, sofern keine Aufbewahrungspflicht besteht (Art. 6 Abs. 1 lit. f DSGVO).')
        . $h('10. Cookies')
        . $a('WordPress und Jetpack können Cookies setzen, etwa um beim Kommentieren Name und E-Mail-Adresse für das nächste Mal zu merken (nur wenn du das Häkchen setzt). Eingebettete Dienste wie YouTube können nach dem Klick eigene Cookies setzen. Cookies kannst du in deinem Browser jederzeit löschen oder blockieren.')
        . $h('11. Deine Rechte')
        . $a('Du hast das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung, Datenübertragbarkeit und Widerspruch sowie das Recht, eine Einwilligung jederzeit mit Wirkung für die Zukunft zu widerrufen. Schreib dazu an <strong>[E-MAIL-ADRESSE]</strong>. Du kannst dich außerdem bei der österreichischen Datenschutzbehörde beschweren: Barichgasse 40–42, 1030 Wien, <a href="https://www.dsb.gv.at">dsb.gv.at</a>.')
        . $a('Stand: Oktober 2026');
}

/** Legt fehlende Seiten als Entwurf an; gibt die IDs zurück (fehlend = 0) */
function radional_tb_rechtstexte_anlegen()
{
    $ergebnis = array('impressum' => 0, 'datenschutz' => 0);

    $impressum = radional_tb_seite_mit_slug('impressum');
    if (!$impressum) {
        $ergebnis['impressum'] = (int) wp_insert_post(array(
            'post_type'    => 'page',
            'post_status'  => 'draft',
            'post_title'   => 'Impressum',
            'post_name'    => 'impressum',
            'post_content' => radional_tb_impressum_inhalt(),
        ));
    } else {
        $ergebnis['impressum'] = (int) $impressum->ID;
    }

    $datenschutz = radional_tb_seite_mit_slug('datenschutz');
    if (!$datenschutz) {
        $ergebnis['datenschutz'] = (int) wp_insert_post(array(
            'post_type'    => 'page',
            'post_status'  => 'draft',
            'post_title'   => 'Datenschutzerklärung',
            'post_name'    => 'datenschutz',
            'post_content' => radional_tb_datenschutz_inhalt(),
        ));
        $bisher = (int) get_option('wp_page_for_privacy_policy');
        if ($ergebnis['datenschutz'] && (!$bisher || get_post_status($bisher) !== 'publish')) {
            update_option('wp_page_for_privacy_policy', $ergebnis['datenschutz']);
        }
    } else {
        $ergebnis['datenschutz'] = (int) $datenschutz->ID;
    }
    return $ergebnis;
}

/* Einmal, beim ersten Admin-Aufruf mit passender Berechtigung */
add_action('admin_init', function () {
    if (get_option('radional_tb_rechtstexte') || !current_user_can('manage_options') || !current_user_can('publish_pages')) {
        return;
    }
    update_option('radional_tb_rechtstexte', radional_tb_rechtstexte_anlegen(), false);
});

/* Hinweis im Admin-Bereich, solange eine der beiden Seiten nicht veröffentlicht ist */
add_action('admin_notices', function () {
    $ids = get_option('radional_tb_rechtstexte');
    if (!is_array($ids) || !current_user_can('edit_pages')) {
        return;
    }
    $offen = array();
    foreach (array('impressum' => 'Impressum', 'datenschutz' => 'Datenschutzerklärung') as $k => $name) {
        $id = (int) ($ids[$k] ?? 0);
        if ($id && get_post_status($id) && get_post_status($id) !== 'publish' && get_post_status($id) !== 'trash') {
            $offen[] = '<a href="' . esc_url(get_edit_post_link($id)) . '">' . esc_html($name) . '</a>';
        }
    }
    if (!$offen) {
        return;
    }
    echo '<div class="notice notice-warning"><p><strong>Radional:</strong> '
        . implode(' und ', $offen) // Links aus esc_url/esc_html
        . ' liegen als Entwurf bereit. Bitte die Stellen in eckigen Klammern ausfüllen und veröffentlichen.</p></div>';
});
