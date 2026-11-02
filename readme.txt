=== Try3D – 3D & AR Product Viewer ===
Contributors: swiftglb, mohammadmehdi5432
Tags: woocommerce, 3d, product, gallery, ecommerce
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.11
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show WooCommerce products in 3D and augmented reality. The model downloads only after the shopper clicks.

== Description ==

Try3D adds a 3D and augmented-reality viewer to WooCommerce products without changing the theme.

On the product edit screen, a **3D & AR** tab sits beside Inventory and Shipping. Upload a GLB file there. Try3D stores a protected copy and does not leave the original model in the public uploads folder. The product page shows one button. The viewer script and the model download only after a shopper clicks that button.

A live catalog is at [try3d.site](https://try3d.site/). The plugin does not contact that site, does not require an account, and works on a store that never opens it.

= Features =

* Interactive 3D view with orbit controls
* Augmented reality on supported phones
* QR code on desktop, generated in the browser, so a shopper can open the same product on a phone
* Live preview in the product editor
* Full screen, auto-rotate, reset view, snapshot, and day/night lighting
* Optional smaller model plus a high-quality model
* Simple, variable, and subscription products
* Per-variation model or material color
* Shortcode for custom product layouts: [[try3d_viewer]]

The older shortcodes [[swiftglb_viewer]] and [[woo_3d_viewer]] still work.

= Translations =

Every shopper-facing and admin string uses the `try3d-viewer` text domain. After this plugin is approved on WordPress.org, translations are delivered automatically for the language selected in **Settings → General**. Translation editors can contribute at [translate.wordpress.org](https://translate.wordpress.org/).

Before approval, or on a site that installs the plugin from GitHub, use a translation plugin such as Loco Translate and choose the text domain `try3d-viewer`.

= Storage =

Try3D tries to keep model files outside the web root. When that is not possible, Apache is blocked with `.htaccess`. Nginx ignores that file, so the plugin shows an administrator notice with a `deny` rule for the model folder only.

= External services =

Android augmented reality can open Google Scene Viewer. That happens only after the shopper taps the AR button. Scene Viewer then requests the model from this store through a short-lived signed URL. Try3D does not send customer accounts or orders to Google, and it does not load the viewer from a third-party CDN.

[try3d.site](https://try3d.site/) is only a demo catalog. Installing this plugin does not sign the store up for anything there.

= Credits =

* [model-viewer](https://github.com/google/model-viewer) is bundled under the Apache License 2.0.
* The Draco decoder used for compressed GLB files is bundled under its upstream license.
* The QR code is drawn locally in the browser.

== Installation ==

1. Install and activate WooCommerce.
2. Install the zip from **Plugins → Add New → Upload Plugin**, or upload the plugin folder to `/wp-content/plugins/`.
3. Activate **Try3D – 3D & AR Product Viewer**.
4. Edit a product, open the **3D & AR** tab, and upload a `.glb` file.
5. For shoes or furniture, enable **Hide the underside of the product**.
6. Optional: enable auto-rotate, or set a custom button label.

For a page built outside the normal product template, add [[try3d_viewer]] or [[try3d_viewer id="123"]].

== Frequently Asked Questions ==

= Which file types can I upload? =

GLB is the format to export from your 3D tool. A protected `.w3d` or `.bin` file exported by this plugin can also be imported onto the same site.

= Does the model load for every visitor immediately? =

No. The product page shows a button. The 3D library and the model download after that click.

= How do I translate the plugin? =

Set the site language in **Settings → General**. On WordPress.org, approved translations are installed automatically. Until a language pack exists, translate the `try3d-viewer` text domain with Loco Translate or another gettext tool.

= What if I use Nginx? =

If PHP cannot move the files outside the web root, copy the `location` rule from the admin notice into the site Nginx configuration. Do not deny the whole site.

== Changelog ==

= 1.1.11 =
* The public name is Try3D. The translation text domain is try3d-viewer.
* The shortcode [[try3d_viewer]] is available. Older shortcodes still work.
* Saving a variation checks the WooCommerce nonce before writing model data.
* The Nginx storage notice is shown only to administrators, on the Plugins screen and product screens.

= 1.1.10 =
* Product lighting uses a studio setup so materials keep their color and depth.

= 1.1.9 =
* Augmented reality stays in Chrome instead of opening the Google app.

= 1.1.8 =
* The original model is shown on the page again. Augmented reality opens from a short-lived link.

= 1.1.7 =
* Augmented reality keeps the original model and waits until that model is ready.

= 1.1.6 =
* The frosted download notice stays on desktop. Phones keep the previous size.

= 1.1.5 =
* The high-quality download notice is larger, with a frosted-glass background, and says the original model is downloading.

= 1.1.4 =
* The viewer close button now closes the model. A click on it was treated as a lighting switch.

= 1.1.3 =
* Removed the viewer, auto-rotate, and underside options from the product screen.

= 1.1.2 =
* Admin text is left-aligned, and the 3D panel no longer slides under the product-data tabs.

= 1.1.1 =
* The mobile augmented-reality button stays on one line, with a larger icon.

= 1.1.0 =
* The high-quality model downloads in the background after the standard model opens, then replaces it. The HD switch is gone.
* A small progress line at the top of the viewer shows that download.
* Phone viewers no longer show the auto-rotate and reset buttons.
* The desktop QR card is a plain code, without the blue scan button or the address beside it.

= 1.0.0 =
* First public release of Try3D – 3D & AR Product Viewer.
* English source strings with the `try3d-viewer` text domain for WordPress translations.
* Protected GLB storage, product-page viewer, augmented reality, and desktop QR code.

== Upgrade Notice ==

= 1.1.11 =
Public name is Try3D. Existing product models stay in place. Refresh the product editor after updating.

= 1.1.10 =
Studio lighting and in-browser augmented reality. Refresh the product page after updating.

= 1.1.0 =
High-quality models now replace the standard model automatically. Refresh the product page after updating.

= 1.0.0 =
First release. WooCommerce is required.
