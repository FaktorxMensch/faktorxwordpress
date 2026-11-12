## create release
use the release tool at https://github.com/FaktorxMensch/fxr

## Signierte Updates (Release signieren)
Nach `fxr` muss jedes Release signiert werden, sonst installieren die Kundenseiten es nicht (sobald ein
Schlüssel in `FXWP_UPDATE_PUBLIC_KEYS` steht): `php tools/sign-release.php sign vX.Y.Z --key <Datei> --upload`.
Einrichtung, Ablauf und Fehlerfälle: [docs/release-signing.md](docs/release-signing.md).

## Ausgesperrt? Admin-Zugang wiederherstellen (Recovery)
Alle Wege (ProjectPilot-Button, FTP-Flag `fxwp-recover.flag` + `admin/fxm-recover.php`, Shell) stehen in
[docs/admin-recovery.md](docs/admin-recovery.md).

## Einen User zu fxm_admin machen
Datei `fxwp-promote-next-user.flag` ins Plugin-Verzeichnis legen, dann mit dem gewünschten User ins Backend gehen. Der User wird zu `fxm_admin` und die Datei wird gelöscht.

## Tests und Pre-Commit-Hook
Einmal pro Checkout einrichten:

```bash
git config core.hooksPath .githooks
```

Der Hook prüft vor jedem Commit die PHP-Syntax, die Sicherheitsregeln und die Tests. Von Hand:

```bash
sh tests/run.sh
```

Was geprüft wird, steht in [tests/README.md](tests/README.md).
