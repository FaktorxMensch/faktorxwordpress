# Admin-Zugang wiederherstellen (Recovery)

Stichworte zum Wiederfinden: ausgesperrt, locked out, Passwort vergessen, kein Login,
Admin weg, fxm_admin weg, Zugang wiederherstellen, recover, recovery, `fxm-recover.php`,
`fxmadmin-recovery`, `recover_admin`, `fxwp-recover.flag`, `fxwp-promote-next-user.flag`.

Wenn wir auf einer Kundenseite nicht mehr ins WordPress-Backend kommen, gibt es vier Wege.
Die Wege 1–3 führen alle dieselbe Funktion aus: `fxwp_recover_admin_access()` in
[`includes/recover.php`](../includes/recover.php).

## Was die Wiederherstellung tut

- Legt das Konto **`fxmadmin-recovery`** an. Gibt es schon einen Benutzer mit der E-Mail
  `accounts@faktorxmensch.com`, wird stattdessen dessen Passwort zurückgesetzt.
- Gibt dem Konto die Rollen `administrator` und `fxm_admin` (die Rolle `fxm_admin` wird bei
  Bedarf neu angelegt).
- Schickt das neue Passwort **per E-Mail an `accounts@faktorxmensch.com`**. Es wird nirgends
  sonst ausgegeben; im E-Mail-Protokoll der Seite ist es geschwärzt.

## Weg 1 – aus ProjectPilot (kein Serverzugang nötig)

Voraussetzung: Seite ist mit dem Projekt verknüpft (API-Key stimmt), Plugin ist aktiv.

In ProjectPilot: Projekt → **Integration** → Reiter **Benutzer Rollen** →
**Admin-Zugang wiederherstellen**.

Technisch ruft ProjectPilot [`monitor.php`](../monitor.php) mit dem API-Key und
`"recover_admin": true` auf. Ohne gültigen Key passiert nichts.

Fehlt nur die Rolle (Login funktioniert noch), reicht im selben Reiter
„Benutzer Rolle ändern“ → `fxm_admin`.

## Weg 2 – per FTP (wenn ProjectPilot nicht geht)

Für: nicht verknüpft, API-Key falsch, Plugin deaktiviert.

1. Per FTP eine **leere Datei `fxwp-recover.flag`** in den Plugin-Ordner laden
   (`wp-content/plugins/faktorxwordpress/fxwp-recover.flag`).
2. Im Browser öffnen:
   `https://<kundenseite>/wp-content/plugins/faktorxwordpress/admin/fxm-recover.php`
3. Es erscheint „Done.“; das Passwort kommt per E-Mail.

Die Flag-Datei wird beim Aufruf gelöscht – der Aufruf funktioniert genau einmal. Ohne die
Datei antwortet [`admin/fxm-recover.php`](../admin/fxm-recover.php) mit 404. Das ist Absicht:
Früher konnte jeder Besucher diese URL aufrufen.

## Weg 3 – per Shell (SSH)

```bash
php wp-content/plugins/faktorxwordpress/admin/fxm-recover.php
```

## Weg 4 – vorhandenen Benutzer befördern

Für: Es gibt irgendeinen funktionierenden Login, aber keinen Admin / `fxm_admin`.

1. Per FTP eine leere Datei `fxwp-promote-next-user.flag` in den Plugin-Ordner laden.
2. Mit dem Benutzer ins Backend (`/wp-admin`) gehen.

Der nächste eingeloggte Benutzer, der das Backend öffnet, bekommt `administrator` +
`fxm_admin`; die Datei wird gelöscht ([`mods/edit-role-caps.php`](../mods/edit-role-caps.php)).

## Wenn die E-Mail nicht ankommt

Die Kundenseite verschickt dann keine Mails. Weg 4 nutzen oder das Passwort von
`fxmadmin-recovery` über den Hoster / phpMyAdmin setzen.

## Danach aufräumen

Sobald wieder ein normaler fxm-Zugang besteht, das Konto `fxmadmin-recovery` löschen.
