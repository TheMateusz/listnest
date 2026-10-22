# ListNest – roadmap to WordPress.org release

Plugin code lives in `listnest/`. Tick items (`[x]`) as they land.

## Stage 1 – Core & prefixes
- [ ] Main class `ListNest_Plugin` (singleton) bootstraps everything; each hook registered exactly once
- [ ] Constants: `LISTNEST_VERSION`, `LISTNEST_FILE`, `LISTNEST_PATH`, `LISTNEST_URL`
- [ ] WooCommerce active check with admin notice
- [ ] Prefix `lnst_` / `lnst-` → `listnest_` / `listnest-` everywhere (functions, meta, post type, AJAX actions, handles, CSS classes, JS config)
- [ ] Remove `load_plugin_textdomain()`
- [ ] HPOS compatibility declaration; `WC requires at least` / `WC tested up to` headers

## Stage 2 – Security & storage
- [ ] Replace `session_start()` / `$_SESSION` with `WC()->session`
- [ ] Nonces: add-to-list AJAX, list update form
- [ ] `wp_unslash()` + sanitize on every `$_POST` / `$_GET`; sanitize decoded attributes
- [ ] `current_user_can( 'edit_post' )` on metabox save; validate option values against a whitelist
- [ ] `sanitize_key()` on list key before template lookup
- [ ] Only published, purchasable products can be added
- [ ] AJAX response no longer leaks full list contents

## Stage 3 – Front-end features, templates, styling
- [ ] Automatic "Add to list" button on product page (per-list setting), plus `[listnest_button list="..."]`
- [ ] Sends quantity; supports multiple buttons per page
- [ ] "Change button text" option implemented
- [ ] Theme-independent templates (no Bootstrap / company theme classes), product thumbnail, theme override `yourtheme/listnest/…`
- [ ] Neutral CSS using custom properties (no brand colours)
- [ ] Accessible popup (role=dialog, Esc, focus), no `alert()`
- [ ] Attribute labels fixed in default template

## Stage 4 – Integrations, i18n, packaging
- [ ] CF7 integration rewritten as class methods, loaded only when CF7 active, clears list after send
- [ ] All user-facing strings English + translatable (PHP & JS); all code comments English
- [ ] Remove Loco/Polish admin text; regenerate `languages/listnest.pot` and update `listnest-pl_PL.po/.mo`
- [ ] `uninstall.php`
- [ ] `readme.txt` in WordPress.org format

## Stage 5 – Testing
- [ ] Local WP + WooCommerce + CF7 (XAMPP), `WP_DEBUG` clean
- [ ] Plugin Check (PCP): 0 errors
- [ ] Storefront + Twenty Twenty-Five, simple + variable products, guest + logged-in, PHP 7.4 & 8.x

## Stage 6 – Release
- [ ] WordPress.org account, clean ZIP, submission
- [ ] SVN trunk/tags, assets (icon, banners, screenshots)
- [ ] Fresh public git repo without pre-rename history
