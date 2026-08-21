#!/usr/bin/env bash
#
# Build the archive to upload to shared hosting.
#
#   tools/build-release.sh            -> dist/hrrams-YYYYMMDD-HHMM.zip
#
# Excluded: git history, local config (which holds credentials), runtime storage
# contents, build scratch, and the OS/editor noise. The directory structure of
# storage/ is kept so the app has somewhere to write on arrival.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

STAMP="$(date +%Y%m%d-%H%M)"
DIST="$ROOT/dist"
NAME="hrrams-$STAMP"
STAGE="$DIST/$NAME"

rm -rf "$STAGE"
mkdir -p "$STAGE"

echo "Staging release $NAME"

# Application code and assets.
for path in app config database docs public tools README.md; do
  if [ -e "$path" ]; then
    cp -R "$path" "$STAGE/"
  fi
done

# Root .htaccess redirects into public/ when the document root cannot be moved.
[ -f .htaccess ] && cp .htaccess "$STAGE/"

# Empty, writable runtime directories with their HTTP-deny rules intact.
for d in uploads logs backups; do
  mkdir -p "$STAGE/storage/$d"
  printf '*\n!.gitignore\n' > "$STAGE/storage/$d/.gitignore"
done
[ -f storage/.htaccess ] && cp storage/.htaccess "$STAGE/storage/"

# Never ship local credentials, build artefacts, tooling scratch or OS noise.
rm -f  "$STAGE/config/config.local.php"
rm -rf "$STAGE/dist" "$STAGE/docs/"*.docx
find "$STAGE" -name '.DS_Store'   -delete
find "$STAGE" -name '*.swp'       -delete
find "$STAGE" -name '.claude-flow' -type d -prune -exec rm -rf {} +
find "$STAGE" -name '.claude'      -type d -prune -exec rm -rf {} +
find "$STAGE" -name '_*.php'       -delete       # scratch endpoints, if any survived

# A deployment needs its own key; leave a reminder rather than a working default.
cat > "$STAGE/DEPLOY-README.txt" <<'TXT'
HRRAMS deployment archive
=========================

Before this will run you must, in order:

  1. Create a MySQL database and user, then import:
       database/schema.sql   then   database/seed.sql
  2. Copy config/config.php to config/config.local.php and set the database
     credentials, a fresh security.app_key, and your SMTP details.
       Generate the key with:  openssl rand -base64 32
  3. Point the domain's document root at the public/ directory.
  4. Make storage/ writable (755 or 775 depending on the host).
  5. Run the readiness check:  php tools/preflight.php
  6. Delete or re-password every demo account in Administration -> Users.

Full instructions:
  Hostinger      -> docs/HOSTING-HOSTINGER.md   (hPanel; app must live outside public_html)
  Other cPanel   -> docs/HOSTING-CPANEL.md
  VPS / your own -> docs/DEPLOYMENT.md
TXT

( cd "$DIST" && zip -qr "$NAME.zip" "$NAME" -x '*.DS_Store' )
rm -rf "$STAGE"

SIZE="$(du -h "$DIST/$NAME.zip" | cut -f1)"
COUNT="$(unzip -l "$DIST/$NAME.zip" | tail -1 | awk '{print $2}')"

echo
echo "Built  dist/$NAME.zip  ($SIZE, $COUNT files)"
echo
echo "Sanity checks:"

# Read the listing once. Piping into `grep -q` would SIGPIPE unzip, and with
# `set -o pipefail` that turns every check into a false result.
LISTING="$(unzip -l "$DIST/$NAME.zip")"
FAILED=0

contains() { printf '%s\n' "$LISTING" | grep -Fq -- "$1"; }
matches()  { printf '%s\n' "$LISTING" | grep -Eq -- "$1"; }

if contains 'config/config.local.php'; then
  echo "  FAIL  config.local.php is in the archive — it holds credentials"; FAILED=1
else
  echo "  ok    no local config included"
fi

if matches 'storage/(uploads|logs|backups)/[^.]'; then
  echo "  FAIL  runtime files are in the archive"; FAILED=1
else
  echo "  ok    storage/ is empty but present"
fi

if matches '\.claude(-flow)?/'; then
  echo "  FAIL  tooling scratch directories leaked into the archive"; FAILED=1
else
  echo "  ok    no tooling scratch"
fi

for required in \
  public/index.php public/.htaccess public/.user.ini \
  public/hrrams-path.example.php \
  app/Core/App.php app/Core/Smtp.php config/routes.php \
  database/schema.sql database/seed.sql \
  database/schema-cpanel.sql database/seed-cpanel.sql \
  docs/HOSTING-HOSTINGER.md docs/HOSTING-CPANEL.md tools/preflight.php
do
  if contains "$required"; then
    echo "  ok    $required"
  else
    echo "  FAIL  $required is missing"; FAILED=1
  fi
done

echo
if [ "$FAILED" -ne 0 ]; then
  echo "Archive built but FAILED its checks — do not deploy it."
  exit 1
fi
echo "Upload this to the host, extract it, then follow DEPLOY-README.txt."
