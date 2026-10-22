=== ListNest – Product Lists & Quote Requests for WooCommerce ===
Contributors: mateuszmatusiak
Tags: woocommerce, quote, wishlist, product list, request a quote
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let customers collect products into custom lists for quote requests, samples or wishlists, and send them to you through Contact Form 7.

== Description ==

ListNest adds "Add to list" buttons to your WooCommerce store. Customers collect products (including variations, with their selected attributes and quantities) into lists that you define, review them on a list page, and send them to you with a Contact Form 7 form. No checkout, no prices, no account required.

You can create as many lists as you need, each with its own button and behaviour.

**Typical uses**

* **Quote requests** – customers build a list of products and ask for a price offer.
* **Samples** – let customers pick the product samples they would like to receive.
* **Wishlists and enquiry lists** – a simple list of products to come back to or ask about.

**Features**

* Unlimited lists, managed under *Lists* in the admin area. The list slug is the list key.
* Automatic "Add to list" button on single product pages (per-list setting), or place it anywhere with a shortcode.
* Works with simple and variable products; the selected variation, attributes and quantity are stored.
* Three actions after adding a product: redirect to a page, turn the button into a link (e.g. "See list"), or show an accessible confirmation popup.
* List page with thumbnails, attributes, quantity editing and item removal.
* Contact Form 7 integration: a form tag that attaches the list to the form (with a read-only summary) and a mail tag that inserts the list into the email as an HTML table or plain text. The list is cleared after the message has been sent.
* Theme-independent templates that can be overridden in your theme, and neutral CSS with custom properties.
* Lists are stored in the WooCommerce customer session: works for guests and logged-in customers.
* Translation ready (Polish translation included).
* Compatible with WooCommerce High-Performance Order Storage (HPOS).

**Shortcodes**

* `[listnest list_key="my-list"]` – displays the list page (products, quantities, update and remove).
* `[listnest_button list="my-list"]` – displays the "Add to list" button for the current product. Use `product_id="123"` to target a specific product.

**Contact Form 7 tags**

* Form tag: `[listnest_products list:my-list]` – attaches the list to the form and shows a summary of its products. The form cannot be sent while the list is empty.
* Mail tag: `[listnest_products]` – inserts the list into the email body (an HTML table when "Use HTML content type" is enabled, plain text otherwise).

**Developers**

Filters:

* `listnest_can_add_product` – `( bool $can_add, WC_Product $product, WC_Product_Variation|null $variation )` – allow or deny adding a product.
* `listnest_max_quantity` – `( int $max )` – maximum quantity per item (default 9999).
* `listnest_max_items` – `( int $max )` – maximum number of distinct items per list (default 100).
* `listnest_show_auto_button` – `( bool $show, string $list_key, WC_Product $product )` – show the automatic button for a list on a product page.
* `listnest_button_label` – `( string $label, string $list_key, WC_Product $product )` – button label.
* `listnest_button_classes` – `( string[] $classes, string $list_key, WC_Product $product )` – button CSS classes.
* `listnest_script_config` – `( array $config )` – front-end script configuration (`ListNestConfig`).
* `listnest_locate_template` – `( string $located, string[] $template_names, string $list_key )` – resolved template path.
* `listnest_cf7_require_items` – `( bool $require, string $list_key, WPCF7_ContactForm|null $form )` – block sending the form while the list is empty (default true).
* `listnest_cf7_clear_after_send` – `( bool $clear, string $list_key, WPCF7_ContactForm $form )` – clear the list after the mail has been sent (default true).
* `listnest_cf7_mail_output` – `( string $output, string $list_key, array $rows, bool $html )` – list inserted by the mail tag.
* `listnest_cf7_form_summary_html` – `( string $html, string $list_key, array $rows )` – list summary shown inside the form.

JavaScript: a bubbling `listnest:added` event is dispatched on the button after a product has been added (`event.detail`: `listKey`, `productId`, `variationId`, `quantity`, `response`).

**Privacy**

ListNest does not send any data to external services. List contents are kept in the WooCommerce customer session on your own server and are only sent by email when the customer submits your Contact Form 7 form.

== Installation ==

1. Make sure WooCommerce is installed and active. Install Contact Form 7 if you want customers to send their lists.
2. Upload the `listnest` folder to `/wp-content/plugins/`, or install the plugin through the *Plugins* screen in WordPress.
3. Activate the plugin through the *Plugins* screen.
4. Go to *Lists → Add New List*, enter a title and publish. The list slug (e.g. `samples`) is the list key.
5. In the list options, enable "Show button on product pages" or use `[listnest_button list="samples"]`, and choose what happens after a product is added.
6. Create a page with `[listnest list_key="samples"]` to display the list.
7. Optional: add `[listnest_products list:samples]` to a Contact Form 7 form and `[listnest_products]` to its mail body, then place the form on the list page.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes. The list post type is always available in the admin area, but the buttons, the list page and the Contact Form 7 integration require WooCommerce.

= How do I send a list with Contact Form 7? =

Add the form tag `[listnest_products list:your-list-key]` anywhere in the form. It adds the list to the submission and shows the customer a summary of the products. Then add the mail tag `[listnest_products]` to the *Mail* tab. Enable "Use HTML content type" to receive the list as a table. After the message has been sent, the list is cleared (see the `listnest_cf7_clear_after_send` filter).

= How do I override the templates? =

Copy the file from `wp-content/plugins/listnest/templates/` to `wp-content/themes/your-theme/listnest/` and edit it there. Available templates: `list-table.php`, `add-button.php` and `popup.php`. You can also target a single list with `{list-key}-list-table.php` or `{list-key}-add-button.php`. Child themes are checked before parent themes.

= The list page or the form shows another visitor's products or an old list. What should I do? =

Lists are personal, so pages that display them must not be served from a full-page cache. ListNest sets the `DONOTCACHEPAGE` constant on pages with the list shortcode or the Contact Form 7 form tag, which most caching plugins respect. If your caching plugin or server cache ignores it, exclude the list page and the form page from caching manually. "Add to list" buttons keep working on cached product pages.

= Where are the lists stored? Is any data sent elsewhere? =

Lists are stored in the WooCommerce customer session (the same mechanism as the cart), so no account is needed and the data expires together with the session. Nothing is sent to external services; the list only leaves your site as part of the email sent by your Contact Form 7 form.

= What happens when I uninstall the plugin? =

All lists you created (and their settings) are deleted. Customers' session data expires on its own.

== Screenshots ==

1. List settings in the admin area: button, action after adding a product and popup texts.
2. "Add to list" button on a variable product page.
3. Confirmation popup after a product has been added.
4. List page with thumbnails, attributes and quantities.
5. Contact Form 7 form with the list summary.
6. Email with the list as an HTML table.

== Changelog ==

= 0.1.0 =
* Initial release.

== Upgrade Notice ==

= 0.1.0 =
Initial release.
