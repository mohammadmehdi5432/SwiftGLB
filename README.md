# SwiftGLB – 3D & AR Product Viewer

WordPress plugin that shows WooCommerce products as interactive 3D models with augmented reality. The viewer and the model download only after a shopper clicks.

The plugin is free and open source (GPLv2 or later). This repository is the source.

## Live demo

The working catalog is [wpglb.site](https://wpglb.site/). Open a product and choose **View in 3D & AR**. The page shows the original model. On an Android phone, augmented reality stays in Chrome and uses that same model. The button stays inactive until the model has loaded.

The source language is English. Strings use the `swiftglb-3d-ar-product-viewer` text domain, so WordPress.org can ship translations for each site language automatically.

## Requirements

* WordPress 6.5 or newer
* PHP 7.4 or newer
* WooCommerce 8.0 or newer

## Shortcode

`[swiftglb_viewer]`

`[swiftglb_viewer id="123" mode="inline"]`

`[woo_3d_viewer]` remains available.

## Translate

On WordPress.org, choose the site language under **Settings → General**. Language packs are installed for this text domain when translators publish them.

From GitHub, translate `swiftglb-3d-ar-product-viewer` with Loco Translate or any gettext tool. Put compiled files in `languages/` as `swiftglb-3d-ar-product-viewer-{locale}.mo`.

## License

GPLv2 or later. See `license.txt`.

The bundled `<model-viewer>` library is Apache License 2.0.
