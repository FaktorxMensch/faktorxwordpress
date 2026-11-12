# Tests

```bash
sh tests/run.sh
```

Runs before every commit once the hook is enabled (`git config core.hooksPath .githooks`).
Needs only `php`; with `php-cgi` installed the web-request tests run too.

| Step | File | What it checks |
|---|---|---|
| PHP syntax | `run.sh` | `php -l` on every PHP file |
| Security rules | `security-lint.php` | Source-text rules: REST routes have a permission check, AJAX / admin-post handlers check nonce and capability, no new `eval`, no new directly callable files, no hard-coded backup path, no request data or API key in logs or pages, pinned CDN versions |
| Unit tests | `unit.php` | Single functions against WordPress stubs (`lib/wp-stubs.php`): mail redaction, backup folder migration, self-update, options panel, shortcode form, installer, Schreibwerkstatt notice, debug log widget, update dashboard |
| Entry points | `entrypoints.php` | Runs `monitor.php` and `admin/fxm-recover.php` for real inside a fake site (`lib/fake-wp-load.php`): key check, role change, admin recovery, the one-time flag |
| Signed updates | `signature.php` | A release is only accepted with a matching signature: changed, added or removed files, a signature for another version, a foreign key and broken signature files are all rejected; the signing tool never writes a key into the repository |

Not covered (needs a real WordPress): the backup job itself, S3 upload, plugin/theme
installation, the self-updater's download and unpack (the signature check itself is covered), and how pages look in the browser.

These files are not shipped to customer sites (`.gitattributes`, `export-ignore`; the same goes for `tools/`, `docs/` and `.github/`), and every
PHP file here refuses to run over the web.
