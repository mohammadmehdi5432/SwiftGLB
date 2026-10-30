=== SwiftGLB – 3D & AR Product Viewer ===
Contributors: mohammadmehdi5432
Tags: woocommerce, 3d, product, gallery, ecommerce
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show WooCommerce products in 3D and augmented reality. The model downloads only after the shopper clicks.

== Description ==

SwiftGLB adds a 3D and augmented-reality viewer to WooCommerce products without changing the theme.

On the product edit screen, a **3D & AR** tab sits beside Inventory and Shipping. Upload a GLB file there. SwiftGLB stores a protected copy and does not leave the original model in the public uploads folder. The product page shows one button. The viewer script and the model download only after a shopper clicks that button.

= Features =

* Interactive 3D view with orbit controls
* Augmented reality on supported phones
* QR code on desktop, generated in the browser, so a shopper can open the same product on a phone
* Live preview in the product editor
* Full screen, auto-rotate, reset view, snapshot, and day/night lighting
* Optional smaller model plus a high-quality model
* Simple, variable, and subscription products
* Per-variation model or material color
* Shortcode for custom product layouts: [[swiftglb_viewer]]

The older shortcode [[woo_3d_viewer]] still works.

= Translations =

Every shopper-facing and admin string uses the `swiftglb-3d-ar-product-viewer` text domain. After this plugin is approved on WordPress.org, translations are delivered automatically for the language selected in **Settings → General**. Translation editors can contribute at [translate.wordpress.org](https://translate.wordpress.org/).

Before approval, or on a site that installs the plugin from GitHub, use a translation plugin such as Loco Translate and choose the text domain `swiftglb-3d-ar-product-viewer`.

= Storage =

SwiftGLB tries to keep model files outside the web root. When that is not possible, Apache is blocked with `.htaccess`. Nginx ignores that file, so the plugin shows an administrator notice with a `deny` rule for the model folder only.

= External services =

Android augmented reality can open Google Scene Viewer. That happens only after the shopper taps the AR button. Scene Viewer then requests the model from this store through a short-lived signed URL. SwiftGLB does not send customer accounts or orders to Google, and it does not load the viewer from a third-party CDN.

= Credits =

* [model-viewer](https://github.com/google/model-viewer) is bundled under the Apache License 2.0.
* The Draco decoder used for compressed GLB files is bundled under its upstream license.
* The QR code is drawn locally in the browser.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the `swiftglb` folder to `/wp-content/plugins/`, or install the zip from **Plugins → Add New → Upload Plugin**.
3. Activate **SwiftGLB – 3D & AR Product Viewer**.
4. Edit a product, open the **3D & AR** tab, and upload a `.glb` file.
5. For shoes or furniture, enable **Hide the underside of the product**.
6. Optional: enable auto-rotate, or set a custom button label.

For a page built outside the normal product template, add [[swiftglb_viewer]] or [[swiftglb_viewer id="123"]].

== Frequently Asked Questions ==

= Which file types can I upload? =

GLB is the format to export from your 3D tool. A protected `.w3d` or `.bin` file exported by this plugin can also be imported onto the same site.

= Does the model load for every visitor immediately? =

No. The product page shows a button. The 3D library and the model download after that click.

= How do I translate the plugin? =

Set the site language in **Settings → General**. On WordPress.org, approved translations are installed automatically. Until a language pack exists, translate the `swiftglb-3d-ar-product-viewer` text domain with Loco Translate or another gettext tool.

= What if I use Nginx? =

If PHP cannot move the files outside the web root, copy the `location` rule from the admin notice into the site Nginx configuration. Do not deny the whole site.

== Changelog ==

= 1.1.1 =
* The mobile augmented-reality button stays on one line, with a larger icon.

= 1.1.0 =
* The high-quality model downloads in the background after the standard model opens, then replaces it. The HD switch is gone.
* A small progress line at the top of the viewer shows that download.
* Phone viewers no longer show the auto-rotate and reset buttons.
* The desktop QR card is a plain code, without the blue scan button or the address beside it.

= 1.0.0 =
* First public release of SwiftGLB – 3D & AR Product Viewer.
* English source strings with the `swiftglb-3d-ar-product-viewer` text domain for WordPress translations.
* Protected GLB storage, product-page viewer, augmented reality, and desktop QR code.

== Upgrade Notice ==

= 1.1.0 =
High-quality models now replace the standard model automatically. Refresh the product page after updating.

= 1.0.0 =
First release. WooCommerce is required.
