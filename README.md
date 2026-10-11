# radional.at – Theme und Karten-Plugin

Quellcode und Update-Quelle für [radional.at](https://www.radional.at/).

| Ordner | Inhalt |
| --- | --- |
| `theme/radional-tourenbuch/` | Theme „Radional Tourenbuch“ (Child-Theme von Twenty Twenty-Five) |
| `plugin/radional-karte/` | Plugin „Radional Karte“ – Karte aller Challenges |
| `dist/` | Fertige ZIP-Pakete, aus denen WordPress die Updates installiert |
| `update.json` | Aktuelle Versionen; WordPress liest diese Datei alle 2 Stunden („Erneut prüfen“ unter Dashboard → Aktualisierungen sofort) |

## Neue Version veröffentlichen
1. Versionsnummer im Code erhöhen (Theme: `style.css` und `functions.php`, Plugin: Kopf von `radional-karte.php` und `RADIONAL_KARTE_VERSION`).
2. ZIP neu bauen und in `dist/` ablegen; der oberste Ordner im ZIP muss `radional-tourenbuch` bzw. `radional-karte` heißen.
3. `version` in `update.json` erhöhen und alles auf `main` pushen.

WordPress zeigt das Update danach unter *Dashboard → Aktualisierungen*.

## Lizenz
Code: GPLv2 oder später. Schriften (Montserrat, Cormorant Garamond): SIL Open Font License 1.1.
Logo und Bilder: radional.at, alle Rechte vorbehalten.
