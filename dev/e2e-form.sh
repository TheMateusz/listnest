#!/usr/bin/env bash
# Development only: end-to-end checks of the built-in inquiry form against wp-env.
# Usage: bash dev/e2e-form.sh   (run from the repository root, wp-env started)
set -u
export MSYS_NO_PATHCONV=1  # Git Bash on Windows: do not rewrite "/probki/" into a Windows path
BASE="${BASE:-http://localhost:8888}"
TMP="$(mktemp -d)"
command -v cygpath >/dev/null && TMP="$(cygpath -m "$TMP")"  # native curl needs a Windows path
command -v cygpath >/dev/null && TMP="$(cygpath -m "$TMP")"
JAR="$TMP/cookies"
PASS=0
FAIL=0

ok()   { echo "  PASS  $1"; PASS=$((PASS + 1)); }
bad()  { echo "  FAIL  $1"; FAIL=$((FAIL + 1)); }
check() { if eval "$2"; then ok "$1"; else bad "$1"; fi; }
wpcli() { wp-env run cli wp "$@" 2>/dev/null | tr -d '\r'; }

product_id() { wpcli post list --post_type=product --name="$1" --field=ID | head -1; }
variation_id() { wpcli post list --post_type=product_variation --post_parent="$1" --field=ID --orderby=ID --order=ASC | head -1; }

add_to_list() { # $1 product id, $2 variation id (or 0), $3 qty
    local page nonce
    page="$(curl -s -b "$JAR" -c "$JAR" "$BASE/product/kubek-prosty/")"
    nonce="$(echo "$page" | grep -o '"nonce":"[a-f0-9]*"' | head -1 | cut -d'"' -f4)"
    curl -s -b "$JAR" -c "$JAR" "$BASE/wp-admin/admin-ajax.php" \
        --data-urlencode "action=listnest_add_to_list" \
        --data-urlencode "security=$nonce" \
        --data-urlencode "list_key=probki" \
        --data-urlencode "product_id=$1" \
        --data-urlencode "variation_id=$2" \
        --data-urlencode "quantity=$3" \
        --data-urlencode 'attributes={"attribute_rozmiar":"L"}'
}

form_fields() { # prints nonce and referer of the inquiry form on /probki/
    curl -s -b "$JAR" -c "$JAR" "$BASE/probki/" > "$TMP/list.html"
    FORM_NONCE="$(grep -o 'name="listnest_form_nonce" value="[a-f0-9]*"' "$TMP/list.html" | cut -d'"' -f4)"
}

submit() { # extra curl args...
    curl -s -o "$TMP/resp.html" -D "$TMP/headers" -b "$JAR" -c "$JAR" "$BASE/probki/" \
        --data-urlencode "listnest_form_list=probki" \
        --data-urlencode "_wp_http_referer=/probki/" \
        "$@"
    LOCATION="$(grep -i '^location:' "$TMP/headers" | tr -d '\r' | cut -d' ' -f2-)"
}

mail_count() { wp-env run cli bash -c 'cat wp-content/listnest-mail.log 2>/dev/null | wc -l' 2>/dev/null | tr -d '\r '; }

echo "Setup"
wpcli option delete listnest_dev_mail_fail >/dev/null
wpcli transient delete --all >/dev/null
SIMPLE="$(product_id kubek-prosty)"
VARIABLE="$(product_id koszulka-wariantowa)"
VARIATION="$(variation_id "$VARIABLE")"
echo "  simple=$SIMPLE variable=$VARIABLE variation=$VARIATION"

echo "1. Add to list (guest)"
R="$(add_to_list "$SIMPLE" 0 2)"
check "simple product added" '[[ "$R" == *"\"success\":true"* ]]'
R="$(add_to_list "$VARIABLE" "$VARIATION" 1)"
check "variation added" '[[ "$R" == *"\"success\":true"* ]]'
form_fields
check "form shown below the list" '[[ -n "$FORM_NONCE" ]]'
check "list shows the chosen 'any' attribute" 'grep -q "Rozmiar" "$TMP/list.html"'

echo "2. Validation"
submit --data-urlencode "listnest_form_nonce=$FORM_NONCE" --data-urlencode "listnest_name=" --data-urlencode "listnest_email=nope"
check "no redirect on invalid data" '[[ -z "$LOCATION" ]]'
check "email error shown" 'grep -q "Please enter a valid email address" "$TMP/resp.html"'
check "aria-invalid set" 'grep -q "aria-invalid=\"true\"" "$TMP/resp.html"'
check "form action keeps the anchor" 'grep -q "#listnest-form-probki" "$TMP/resp.html"'
submit --data-urlencode "listnest_form_nonce=$FORM_NONCE" --data-urlencode "listnest_name=A" --data-urlencode "listnest_email=a@example.com" --data-urlencode "listnest_phone=abc" --data-urlencode "listnest_consent=1"
check "invalid phone rejected" 'grep -q "Please enter a valid phone number" "$TMP/resp.html"'

echo "3. Bad nonce"
BEFORE="$(mail_count)"
submit --data-urlencode "listnest_form_nonce=deadbeef" --data-urlencode "listnest_name=A" --data-urlencode "listnest_email=a@example.com" --data-urlencode "listnest_consent=1"
check "expired-session error shown" 'grep -q "Your session has expired" "$TMP/resp.html"'
check "no mail sent" '[[ "$(mail_count)" == "$BEFORE" ]]'
check "entered values kept" 'grep -q "value=\"a@example.com\"" "$TMP/resp.html"'

echo "4. Honeypot"
BEFORE="$(mail_count)"
submit --data-urlencode "listnest_form_nonce=$FORM_NONCE" --data-urlencode "listnest_name=Bot" --data-urlencode "listnest_email=bot@example.com" --data-urlencode "listnest_consent=1" --data-urlencode "listnest_website=http://spam"
check "bot redirected to thank-you" '[[ "$LOCATION" == *"listnest_sent=probki"* ]]'
check "no mail sent for bot" '[[ "$(mail_count)" == "$BEFORE" ]]'
form_fields
check "list kept after bot submission" '[[ -n "$FORM_NONCE" ]]'

echo "5. Successful submission"
BEFORE="$(mail_count)"
INQ_BEFORE="$(wpcli post list --post_type=listnest_inquiry --format=count)"
submit --data-urlencode "listnest_form_nonce=$FORM_NONCE" --data-urlencode "listnest_name=Anna Nowak" --data-urlencode "listnest_email=anna@example.com" --data-urlencode "listnest_message=Test E2E" --data-urlencode "listnest_consent=1"
check "redirect back to the list page" '[[ "$LOCATION" == "$BASE/probki/?listnest_sent=probki#listnest-form-probki" ]]'
check "2 mails (shop + copy)" '[[ "$(mail_count)" == "$((BEFORE + 2))" ]]'
check "inquiry stored" '[[ "$(wpcli post list --post_type=listnest_inquiry --format=count)" == "$((INQ_BEFORE + 1))" ]]'
curl -s -b "$JAR" -c "$JAR" "$BASE/probki/?listnest_sent=probki" > "$TMP/thanks.html"
check "thank-you message shown" 'grep -q "Your inquiry has been received" "$TMP/thanks.html"'
check "list cleared" 'grep -q "No products on the list" "$TMP/thanks.html"'
check "form hidden after sending" '! grep -q "name=\"listnest_form_nonce\"" "$TMP/thanks.html"'

echo "6. Empty list"
submit --data-urlencode "listnest_form_nonce=$FORM_NONCE" --data-urlencode "listnest_name=Anna" --data-urlencode "listnest_email=anna@example.com" --data-urlencode "listnest_consent=1"
check "empty list rejected" 'grep -q "Your list is empty" "$TMP/resp.html"'

echo "7. Mail failure is recorded"
wpcli option update listnest_dev_mail_fail 1 >/dev/null
add_to_list "$SIMPLE" 0 1 >/dev/null
form_fields
submit --data-urlencode "listnest_form_nonce=$FORM_NONCE" --data-urlencode "listnest_name=Failed Mail" --data-urlencode "listnest_email=fail@example.com" --data-urlencode "listnest_consent=1"
wpcli option delete listnest_dev_mail_fail >/dev/null
LAST="$(wpcli post list --post_type=listnest_inquiry --orderby=ID --order=DESC --field=ID | head -1)"
check "customer still sees thank-you (inquiry stored)" '[[ "$LOCATION" == *"listnest_sent=probki"* ]]'
check "status 'failed' stored" '[[ "$(wpcli post meta get "$LAST" _listnest_mail_status)" == "failed" ]]'

echo "8. Rate limit (5 per 10 min)"
for i in 1 2 3 4 5; do
    add_to_list "$SIMPLE" 0 1 >/dev/null
    form_fields
    submit --data-urlencode "listnest_form_nonce=$FORM_NONCE" --data-urlencode "listnest_name=Rate $i" --data-urlencode "listnest_email=rate@example.com" --data-urlencode "listnest_consent=1"
done
check "submission over the limit rejected" 'grep -q "Too many inquiries" "$TMP/resp.html"'
wpcli transient delete --all >/dev/null

echo "9. List update redirect"
add_to_list "$SIMPLE" 0 1 >/dev/null
curl -s -b "$JAR" -c "$JAR" "$BASE/probki/" > "$TMP/list.html"
LNONCE="$(grep -o 'name="listnest_list_nonce" value="[a-f0-9]*"' "$TMP/list.html" | cut -d'"' -f4)"
curl -s -o /dev/null -D "$TMP/headers" -b "$JAR" -c "$JAR" "$BASE/probki/" \
    --data-urlencode "listnest_list_key=probki" --data-urlencode "listnest_list_nonce=$LNONCE" \
    --data-urlencode "_wp_http_referer=/probki/" --data-urlencode "listnest_update_list=1"
LOCATION="$(grep -i '^location:' "$TMP/headers" | tr -d '\r' | cut -d' ' -f2-)"
check "update returns to the list page" '[[ "$LOCATION" == "$BASE/probki/?listnest_list_updated=1" ]]'

rm -rf "$TMP"
echo
echo "Passed: $PASS  Failed: $FAIL"
[[ "$FAIL" == 0 ]]
