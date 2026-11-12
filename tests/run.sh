#!/bin/sh
# Runs every automatic check of the plugin. Used by the pre-commit hook
# (.githooks/pre-commit) and by hand:  sh tests/run.sh
#
#   1. PHP syntax of every PHP file
#   2. security rules on the source text      (tests/security-lint.php)
#   3. unit tests against WordPress stubs      (tests/unit.php)
#   4. real runs of monitor.php / fxm-recover  (tests/entrypoints.php)
#   5. release signing and its verification    (tests/signature.php)
#
# Needs only the php command line binary. With php-cgi installed, step 4 also
# tests web requests (JSON body, the browser gate of fxm-recover.php).
set -u
cd "$(dirname "$0")/.." || exit 1

if ! command -v php >/dev/null 2>&1; then
    echo "tests/run.sh: php not found -- cannot check anything." >&2
    exit 1
fi

failed=0

echo "== 1/5 PHP syntax"
syntax_errors=0
list="${TMPDIR:-/tmp}/fxwp-php-files.$$"
# every PHP file except dependencies
find . -name '*.php' -not -path './.git/*' -not -path './vendor/*' -not -path './node_modules/*' > "$list"
count=$(wc -l < "$list" | tr -d ' ')
while IFS= read -r file; do
    if ! out=$(php -l "$file" 2>&1); then
        echo "$out"
        syntax_errors=$((syntax_errors + 1))
    fi
done < "$list"
rm -f "$list"
if [ "$syntax_errors" -gt 0 ]; then
    echo "   $syntax_errors of $count file(s) with syntax errors"
    failed=1
else
    echo "   $count files ok"
fi

echo "== 2/5 Security rules"
php tests/security-lint.php || failed=1

echo "== 3/5 Unit tests"
php tests/unit.php || failed=1

echo "== 4/5 Entry points (monitor.php, fxm-recover.php)"
php tests/entrypoints.php || failed=1

echo "== 5/5 Signed updates"
php tests/signature.php || failed=1

echo
if [ "$failed" -ne 0 ]; then
    echo "CHECKS FAILED -- see above."
    exit 1
fi
echo "All checks passed."
