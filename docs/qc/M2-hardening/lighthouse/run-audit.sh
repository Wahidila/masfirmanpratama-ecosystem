#!/bin/bash
# Lighthouse M2 mobile audit — production-like (nginx + php-fpm via Docker, gzip + cache).
# Run from /root/malang-creative/_active/masfirmanpratama/docs/qc/M2/lighthouse/.
set -uo pipefail

OUT_DIR="$(cd "$(dirname "$0")" && pwd)"
BASE_URL="${BASE_URL:-http://127.0.0.1:3002}"
CHROME_PATH="${CHROME_PATH:-/root/.cache/ms-playwright/chromium-1223/chrome-linux64/chrome}"
export CHROME_PATH

ROUTES=(
  "home|/"
  "produk-list|/produk"
  "produk-buku|/produk/10-keajaiban-pikiran"
  "produk-kelas|/produk/kelas-amc-reguler"
  "cart|/cart"
  "checkout|/checkout"
)

LABEL="${1:-m2}"

for entry in "${ROUTES[@]}"; do
  name="${entry%%|*}"
  path="${entry##*|}"
  url="${BASE_URL}${path}"
  echo ">>> ${LABEL} ${name} ${url}"
  npx --yes lighthouse "$url" \
    --quiet \
    --form-factor=mobile \
    --screenEmulation.mobile=true \
    --only-categories=performance,accessibility,best-practices,seo \
    --output=json --output=html \
    --output-path="${OUT_DIR}/${LABEL}-${name}" \
    --chrome-flags="--headless=new --no-sandbox --disable-dev-shm-usage" \
    --max-wait-for-load=45000 \
    2>&1 | tail -3
done
