=== ListNest ===
Contributors: mateuszmatusiak
Tags: woocommerce, quote, wishlist, product list, request a quote
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let customers collect products into lists for quote requests, samples or wishlists, and send them with a built-in form or Contact Form 7.

== Description ==

ListNest adds "Add to list" buttons to your WooCommerce store. Customers collect products (including variations, with their selected attributes and quantities) into lists that you define, review them on a list page, and send them to you with the built-in inquiry form or a Contact Form 7 form. No checkout, no prices, no account required.

You can create as many lists as you need, each with its own button and behaviour.

**Typical uses**

* **Quote requests** - customers build a list of products and ask for a price offer.
* **Samples** - let customers pick the product samples they would like to receive.
* **Wishlists and enquiry lists** - a simple list of products to come back to or ask about.

**Features**

* Unlimited lists, managed under *Lists* in the admin area. The list slug is the list key.
* Automatic "Add to list" button on single product pages (per-list setting), or place it anywhere with a shortcode.
* Works with simple and variable products; the selected variation, attributes and quantity are stored.
* Three actions after adding a product: redirect to a page, turn the button into a link (e.g. "See list"), or show an accessible confirmation popup.
* List page with thumbnails, attributes, quantity editing and item removal.
* Built-in inquiry form (no other plugins needed): name, email, phone, message and a consent checkbox. The shop receives an HTML email with the product table and Reply-To set to the customer; the customer can get a copy.
* Every inquiry is also saved under *Lists → Inquiries*, so nothing is lost when your site's email delivery fails. Supports the WordPress personal data export and erase tools.
* Spam protection without CAPTCHA or external services: nonce, honeypot field and a per-IP rate limit.
* Contact Form 7 integration: a form tag that attaches the list to the form (with a read-only summary) and a mail tag that inserts the list into the email as an HTML table or plain text. The list is cleared after the message has been sent.
* Theme-independent templates that can be overridden in your theme, and neutral CSS with custom properties.
* Lists are stored in the WooCommerce customer session: works for guests and logged-in customers.
* Translation ready (Polish translation included).
* Setup help, configurable inquiry retention and opt-in data deletion on uninstall.
* Compatible with WooCommerce High-Performance Order Storage (HPOS).

**Shortcodes**

* `[listnest list_key="my-list"]` - displays the list page (products, quantities, update and remove).
* `[listnest_button list="my-list"]` - displays the "Add to list" button for the current product. Use `product_id="123"` to target a specific product.
* `[listnest_form list="my-list"]` - displays the built-in inquiry form. Not needed when the list's "Inquiry form" setting is "Built-in form": the form then appears below the list automatically.

**Contact Form 7 tags**

* Form tag: `[listnest_products list:my-list]` - attaches the list to the form and shows a summary of its products. The form cannot be sent while the list is empty.
* Mail tag: `[listnest_products]` - inserts the list into the email body (an HTML table when "Use HTML content type" is enabled, plain text otherwise).

**Developers**

Filters:

* `listnest_can_add_product` - `( bool $can_add, WC_Product $product, WC_Product_Variation|null $variation )` - allow or deny adding a product.
* `listnest_max_quantity` - `( int $max )` - maximum quantity per item (default 9999).
* `listnest_max_items` - `( int $max )` - maximum number of distinct items per list (default 100).
* `listnest_show_auto_button` - `( bool $show, string $list_key, WC_Product $product )` - show the automatic button for a list on a product page.
* `listnest_button_label` - `( string $label, string $list_key, WC_Product $product )` - button label.
* `listnest_button_classes` - `( string[] $classes, string $list_key, WC_Product $product )` - button CSS classes.
* `listnest_script_config` - `( array $config )` - front-end script configuration (`ListNestConfig`).
* `listnest_locate_template` - `( string $located, string[] $template_names, string $list_key )` - resolved template path.
* `listnest_cf7_require_items` - `( bool $require, string $list_key, WPCF7_ContactForm|null $form )` - block sending the form while the list is empty (default true).
* `listnest_cf7_clear_after_send` - `( bool $clear, string $list_key, WPCF7_ContactForm $form )` - clear the list after the mail has been sent (default true).
* `listnest_cf7_mail_output` - `( string $output, string $list_key, array $rows, bool $html )` - list inserted by the mail tag.
* `listnest_cf7_form_summary_html` - `( string $html, string $list_key, array $rows )` - list summary shown inside the form.
* `listnest_form_mail` - `( array $args, string $type, array $data )` - mail sent by the built-in form (`$type`: `shop` or `customer`; `$args`: `to`, `subject`, `message`, `headers`).
* `listnest_form_validation_errors` - `( array $errors, array $values )` - validation errors of the built-in form.
* `listnest_form_consent_text` - `( string $html, array $settings )` - consent checkbox label; return an empty string to remove the checkbox.
* `listnest_form_success_message` - `( string $message, string $list_key )` - message shown after sending.
* `listnest_form_rate_limit` - `( array $limit )` - `max` inquiries per IP (default 5, 0 disables) within `window` seconds (default 600).
* `listnest_form_store_inquiries` - `( bool $store, array $data )` - save inquiries under Lists → Inquiries (default true).
* `listnest_inquiries_capability` - `( string $capability )` - capability required to view inquiries (default `manage_woocommerce`).

Actions:

* `listnest_inquiry_received` - `( array $data, int $inquiry_id, bool $sent )` - fires after the built-in form has been sent.

JavaScript: a bubbling `listnest:added` event is dispatched on the button after a product has been added (`event.detail`: `listKey`, `productId`, `variationId`, `quantity`, `response`).

**Privacy**

ListNest does not send any data to external services. List contents are kept in the WooCommerce customer session on your own server and are only sent by email when the customer submits the inquiry form. Inquiries sent with the built-in form are stored in your WordPress database (name, email, phone, message, products and the consent text); they can be exported or erased with *Tools → Export / Erase Personal Data*, and ListNest suggests a paragraph for your privacy policy. The rate limit keeps only a hashed IP address in a short-lived transient.

== Installation ==

1. Make sure WooCommerce 9.0 or newer is installed and active.
2. Upload the `listnest` folder to `/wp-content/plugins/`, or install the plugin through the *Plugins* screen in WordPress.
3. Activate the plugin through the *Plugins* screen.
4. Go to *Lists → Add New List*, enter a title and publish. The list slug (e.g. `samples`) is the list key.
5. In the list options, enable "Show button on product pages" or use `[listnest_button list="samples"]`, and choose what happens after a product is added.
6. Create a page with `[listnest list_key="samples"]` to display the list.
7. Under "Inquiry form", choose "Built-in form" to show a form below the list, and set the recipient address. Received inquiries appear under *Lists → Inquiries*.
8. Prefer Contact Form 7? Choose "Contact Form 7", add `[listnest_products list:samples]` to a CF7 form and `[listnest_products]` to its mail body, then place the form on the list page.
9. Open *Lists → Setup and privacy* for shortcode examples, automatic inquiry retention and uninstall preferences. Do not change a list slug after placing its shortcode on a page without updating that shortcode and its CF7 tags.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes. The list post type is always available in the admin area, but the buttons, the list page and the Contact Form 7 integration require WooCommerce.

= Do I need Contact Form 7? =

No. Set "Inquiry form" to "Built-in form" in the list options and the form appears below the list. Contact Form 7 is only needed if you prefer to build the form with it.

= I don't receive the inquiry emails. =

Every inquiry sent with the built-in form is saved under *Lists → Inquiries*, and the list shows whether the email to the shop was sent. If emails are not delivered, your server's mail function is probably blocked or marked as spam; install an SMTP plugin and send a test email.

= How do I send a list with Contact Form 7? =

Add the form tag `[listnest_products list:your-list-key]` anywhere in the form. It adds the list to the submission and shows the customer a summary of the products. Then add the mail tag `[listnest_products]` to the *Mail* tab. Enable "Use HTML content type" to receive the list as a table. After the message has been sent, the list is cleared (see the `listnest_cf7_clear_after_send` filter).

= How do I override the templates? =

Copy the file from `wp-content/plugins/listnest/templates/` to `wp-content/themes/your-theme/listnest/` and edit it there. Available templates: `list-table.php`, `add-button.php`, `inquiry-form.php` and `popup.php`. You can also target a single list with `{list-key}-list-table.php`, `{list-key}-add-button.php` or `{list-key}-inquiry-form.php`. Child themes are checked before parent themes.

= The list page or the form shows another visitor's products or an old list. What should I do? =

Lists are personal, so pages that display them must not be served from a full-page cache. ListNest sends no-cache headers before rendering known list/form pages and sets `DONOTCACHEPAGE`. An existing server/CDN cache can serve a page before WordPress runs: always exclude list and form pages from your full-page cache. Purge existing cached copies after configuration. "Add to list" buttons refresh an expired nonce on cached product pages.

= Where are the lists stored? Is any data sent elsewhere? =

Lists are stored in the WooCommerce customer session (the same mechanism as the cart), so no account is needed and the data expires together with the session. Nothing is sent to external services; the list only leaves your site as part of the inquiry email.

= What happens when I uninstall the plugin? =

Lists and inquiries are preserved by default. To permanently remove them on uninstall, first enable the deletion checkbox under *Lists → Setup and privacy*. Plugin settings and scheduled cleanup jobs are removed either way. Customers' session data and rate-limit transients expire on their own.

= Can old inquiries be removed automatically? =

Set a retention period under *Lists → Setup and privacy*. Zero keeps inquiries until manually deleted. Cleanup runs daily via WP-Cron and removes up to 100 expired inquiries per run, including inquiries in Trash. Configure reliable cron execution on low-traffic sites. This does not delete email copies from mailboxes.

= Does a sent status guarantee email delivery? =

No. It indicates that the site's mail system accepted the message. Check the saved inquiry in the dashboard and configure SMTP if required. A failed notification does not discard a successfully saved inquiry.

== Changelog ==

= 1.0.0 =
* Initial public release with product lists, built-in inquiries and Contact Form 7 support.
* Validated product variants, private product protection, role checks and duplicate-submission locking.
* Setup help, configurable inquiry retention and opt-in uninstall data deletion.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
