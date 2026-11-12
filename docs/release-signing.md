# Signierte Updates (Release-Signatur)

Stichworte: Update wird nicht installiert, Signatur, `release.sig`, `sign-release.php`,
`FXWP_UPDATE_PUBLIC_KEYS`, `FXWP_SIGNING_KEY`, Integrität, Supply Chain, „was not installed“.

## Wozu

Das Plugin aktualisiert sich auf allen Kundenseiten selbst von GitHub. Ohne Prüfung könnte jeder,
der an das GitHub-Repository kommt (gestohlener Account oder Token), Code auf allen Seiten
ausführen. Deshalb wird jedes Release mit einem **privaten Schlüssel** signiert, der nicht im
Repository liegt. Das Plugin enthält den **öffentlichen Schlüssel** und installiert nur Releases
mit passender Signatur.

Signiert wird der Inhalt (Liste aller Dateien mit SHA-256, gebunden an die Versionsnummer), nicht
die Zip-Datei. Die Signatur liegt als Datei `release.sig` am GitHub-Release.

- Prüfung im Plugin: [`includes/update-signature.php`](../includes/update-signature.php),
  aufgerufen in [`cron/auto_updater-cron.php`](../cron/auto_updater-cron.php)
- Werkzeug: [`tools/sign-release.php`](../tools/sign-release.php)

## Status: noch nicht scharf

Solange `FXWP_UPDATE_PUBLIC_KEYS` in [`includes/config.php`](../includes/config.php) leer ist,
installiert das Plugin Updates **ungeprüft wie bisher**. Scharf wird es mit der Einrichtung unten.

## Einmalige Einrichtung

1. Schlüsselpaar erzeugen (Datei **außerhalb** des Repositories, Backup im Passwort-Manager):

   ```bash
   php tools/sign-release.php keygen ~/.config/fxwp/signing.key
   ```

2. Die ausgegebene Zeile mit dem öffentlichen Schlüssel in `FXWP_UPDATE_PUBLIC_KEYS`
   (`includes/config.php`) eintragen und committen.
3. Dieses Release wie gewohnt mit `fxr` veröffentlichen **und signieren** (siehe unten).

Die Seiten installieren genau dieses Release noch ungeprüft (die alte Version kennt die Prüfung
nicht). Ab dann prüft jede Seite jedes weitere Update.

**Vorher auf einer Staging-Seite testen:** einmal ein signiertes Update durchlaufen lassen, und
einmal eines ohne `release.sig` (muss abgelehnt werden).

## Bei jedem Release

1. `fxr` wie bisher (Version, Changelog, Commit, Tag, Push, GitHub-Release anlegen).
2. Signieren und hochladen:

   ```bash
   php tools/sign-release.php sign v2.9.4 --key ~/.config/fxwp/signing.key --upload
   ```

   `--upload` hängt `release.sig` per `gh` an das GitHub-Release. Das Release muss also existieren.
3. Optional prüfen:

   ```bash
   php tools/sign-release.php verify v2.9.4
   ```

Zwischen Schritt 1 und 2 lehnen Seiten das Update ab und versuchen es in der nächsten Stunde
erneut – das ist harmlos.

### Alternative: GitHub signiert automatisch

Der Workflow [`sign-release.yml`](../.github/workflows/sign-release.yml) signiert jedes
veröffentlichte Release selbst, wenn der private Schlüssel als Repository-Secret
`FXWP_SIGNING_KEY` hinterlegt ist (Inhalt der Schlüsseldatei). Bequemer, aber schwächer: Wer
Schreibrechte am Repository hat, kann den Schlüssel dann benutzen. Ohne das Secret tut der Workflow
nichts außer einem Hinweis.

## Wenn Seiten ein Update nicht installieren

Im Debug-Log der Seite und als Hinweis im Backend steht
`FXWP update to vX.Y.Z was not installed: <Grund>`.

| Grund | Ursache | Lösung |
|---|---|---|
| `release.sig … not available (HTTP 404)` | Release nicht signiert / Datei nicht hochgeladen | Schritt 2 nachholen |
| `signature does not match the downloaded files` | Tag nach dem Signieren verschoben, oder mit falschem Schlüssel signiert | Neu signieren; wenn der Tag nicht verschoben wurde: Repository prüfen! |
| `signature is for vA, not for vB` | `release.sig` am falschen Release | Richtig signieren und hochladen |
| `this PHP cannot verify signatures` | Sehr altes PHP/WordPress ohne sodium | PHP/WordPress der Seite aktualisieren |

## Schlüssel wechseln oder verloren

- **Wechsel:** neuen Schlüssel erzeugen, öffentlichen Schlüssel **zusätzlich** eintragen, ein Release
  mit dem **alten** Schlüssel signieren. Danach mit dem neuen signieren; den alten erst ein Release
  später aus der Liste nehmen.
- **Privater Schlüssel verloren:** Es lässt sich kein Update mehr signieren, die Seiten nehmen keines
  mehr an. Dann muss die neue Version von Hand (FTP / Plugin-Upload) auf die Seiten. Deshalb: Backup.
- **Privater Schlüssel gestohlen:** wie Wechsel, aber den alten Schlüssel sofort entfernen.

## Was die Signatur nicht abdeckt

- Das Theme (`FXWP_THEME_REPO_URI`, Installationshilfe) wird weiterhin ungeprüft vom `main`-Branch geladen.
- Die Erstinstallation des Plugins per Zip-Download.
