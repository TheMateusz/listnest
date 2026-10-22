# ListNest – roadmap to WordPress.org release

Plugin code lives in `listnest/`. Tick items (`[x]`) as they land.

## Stage 1 – Core & prefixes
- [x] Main class `ListNest_Plugin` (singleton) bootstraps everything; each hook registered exactly once
- [x] Constants: `LISTNEST_VERSION`, `LISTNEST_FILE`, `LISTNEST_PATH`, `LISTNEST_URL`
- [x] WooCommerce active check with admin notice
- [x] Prefix `lnst_` / `lnst-` → `listnest_` / `listnest-` everywhere (functions, meta, post type, AJAX actions, handles, CSS classes, JS config)
- [x] Remove `load_plugin_textdomain()`
- [x] HPOS compatibility declaration; `WC requires at least` / `WC tested up to` headers

## Stage 2 – Security & storage
- [x] Replace `session_start()` / `$_SESSION` with `WC()->session`
- [x] Nonces: add-to-list AJAX, list update form
- [x] `wp_unslash()` + sanitize on every `$_POST` / `$_GET`; sanitize decoded attributes
- [x] `current_user_can( 'edit_post' )` on metabox save; validate option values against a whitelist
- [x] `sanitize_key()` on list key before template lookup
- [x] Only published products (and published variations of that product) can be added; extra rules via `listnest_can_add_product` filter
- [x] AJAX response no longer leaks full list contents

## Stage 3 – Front-end features, templates, styling
- [x] Automatic "Add to list" button on product page (per-list setting), plus `[listnest_button list="..."]`
- [x] Sends quantity; supports multiple buttons per page
- [x] "Change button text" option implemented
- [x] Theme-independent templates (no Bootstrap / company theme classes), product thumbnail, theme override `yourtheme/listnest/…`
- [x] Neutral CSS using custom properties (no brand colours)
- [x] Accessible popup (role=dialog, Esc, focus), no `alert()`
- [x] Attribute labels fixed in default template
- [x] Assets and popup markup load only where needed; list page sets `DONOTCACHEPAGE`

## Stage 4 – Integrations, i18n, packaging
- [x] CF7 integration rewritten as class methods, loaded only when CF7 active, clears list after send
- [x] All user-facing strings English + translatable (PHP & JS); all code comments English
- [x] Remove Loco/Polish admin text; regenerate `languages/listnest.pot` and update `listnest-pl_PL.po/.mo`
- [x] `uninstall.php`
- [x] `readme.txt` in WordPress.org format
- [x] Stale nonce on cached pages: `listnest_refresh_nonce` AJAX action, script refreshes and retries once

## Stage 4b – Built-in inquiry form (no CF7 needed)
- [x] Per-list "Inquiry form" setting: None / Built-in / Contact Form 7; built-in form shown below the list automatically, plus `[listnest_form list="..."]`
- [x] Fields: name, email, phone (optional), message, consent checkbox (editable text, links allowed, default links to privacy policy)
- [x] Settings: recipient(s), subject with `{list}` `{name}` `{site}`, copy to customer
- [x] HTML mail to the shop with product table, Reply-To = customer; optional customer copy; list cleared; thank-you message (PRG redirect)
- [x] Inquiries saved under Lists → Inquiries (read-only, mail sent/failed status, reply link); personal data exporter/eraser; privacy policy text
- [x] Spam/security: nonce, honeypot, per-IP rate limit (hashed IP transient), sanitization, header-injection safe
- [x] Accessible errors per field (aria-invalid/aria-describedby, role=alert summary); works without JS
- [x] Mail table shared with CF7 (`ListNest_Mail`); uninstall removes inquiries; readme, pot, pl_PL updated (131/131)

## Stage 5 – Testing
- [ ] Local WP + WooCommerce + CF7 (XAMPP), `WP_DEBUG` clean
- [ ] Built-in form: send (guest + logged-in), validation errors, honeypot, rate limit, customer copy, Inquiries screen, mail failure status, privacy export/erase
- [ ] Plugin Check (PCP): 0 errors
- [ ] Storefront + Twenty Twenty-Five, simple + variable products, guest + logged-in, PHP 7.4 & 8.x

## Stage 6 – Release
- [ ] WordPress.org account, clean ZIP, submission
- [ ] SVN trunk/tags, assets (icon, banners, screenshots)
- [ ] Fresh public git repo without pre-rename history
