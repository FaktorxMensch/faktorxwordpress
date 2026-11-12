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

## Status: scharf seit v3.0.0

In `FXWP_UPDATE_PUBLIC_KEYS` ([`includes/config.php`](../includes/config.php)) steht ein öffentlicher
Schlüssel. Seiten mit v3.0.0 oder neuer installieren nur noch signierte Releases. (Mit leerer Liste
würde das Plugin Updates ungeprüft installieren.)

## Einmalige Einrichtung

1. Schlüsselpaar erzeugen (Datei **außerhalb** des Repositories, Backup in 1Password als „FXWP Signing Key“):

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

Einfach `fxr` ausführen. Das Release-Tool erledigt die Signatur mit:

1. **Vor jeder Änderung** prüft es, ob der Schlüssel auf diesem Rechner liegt
   (`~/.config/fxwp/signing.key`) und zu `FXWP_UPDATE_PUBLIC_KEYS` passt. Wenn nicht, bricht es ab
   und sagt, was zu tun ist – Version, Commit und Tag bleiben unberührt.
2. Nach Commit, Tag und Push legt es das GitHub-Release an (ohne Rückfrage, weil die Signatur
   daran hängt), signiert, lädt `release.sig` hoch und prüft die Signatur.

Meldet `fxr` am Ende „NICHT gültig signiert“, steht der Befehl zum Nachholen direkt dabei.

Von Hand geht es weiterhin:

```bash
php tools/sign-release.php sign v3.0.1 --key ~/.config/fxwp/signing.key --out /tmp/release.sig --upload
php tools/sign-release.php verify v3.0.1 --sig /tmp/release.sig
```

`release.sig` gehört nicht ins Repository (steht in `.gitignore`).

## Neuer Rechner / neue Person

Der private Schlüssel liegt in **keinem** Repository – auch nicht im `fxr`-Repository. Wer an GitHub
kommt, soll gerade nicht signieren können. Er wird von Hand kopiert:

1. In 1Password den Eintrag **„FXWP Signing Key“** öffnen, Schlüssel kopieren (eine Zeile).
2. Ablegen und schützen:

   ```bash
   mkdir -p ~/.config/fxwp
   pbpaste > ~/.config/fxwp/signing.key
   chmod 600 ~/.config/fxwp/signing.key
   ```

3. Prüfen (im Plugin-Ordner; keine Ausgabe und Status 0 heißt: passt):

   ```bash
   php tools/sign-release.php preflight --key ~/.config/fxwp/signing.key; echo $?
   ```

Anderer Ablageort: Umgebungsvariable `FXWP_SIGNING_KEY_FILE` setzen.

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
