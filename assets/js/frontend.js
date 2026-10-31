(function () {
	'use strict';

	if (!window.swiftglb) {
		return;
	}

	var iconSun = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>';
	var iconMoon = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>';
	var iconRotate = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>';
	var iconReset = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>';
	var iconCamera = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>';
	var iconFullscreen = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>';
	var iconAr = '<svg viewBox="0 -960 960 960" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M440-181 240-296q-19-11-29.5-29T200-365v-230q0-22 10.5-40t29.5-29l200-115q19-11 40-11t40 11l200 115q19 11 29.5 29t10.5 40v230q0 22-10.5 40T720-296L520-181q-19 11-40 11t-40-11Zm0-92v-184l-160-93v185l160 92Zm80 0 160-92v-185l-160 93v184Z"/></svg>';
	var iconQr = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>';

	var config = window.swiftglb;
	var modal = null;
	var lastFocus = null;
	var viewerPromise = null;
	var requestSerial = 0;
	var models = {};
	var arUrls = {};

	function blobUrlFor(entry) {
		if (!entry || !entry.blob) {
			return '';
		}
		if (!entry.blobUrl) {
			entry.blobUrl = URL.createObjectURL(entry.blob);
		}
		entry.shownUrl = entry.blobUrl;
		return entry.blobUrl;
	}

	function releaseBlobUrl(entry) {
		if (!entry || !entry.blobUrl) {
			return;
		}
		URL.revokeObjectURL(entry.blobUrl);
		entry.blobUrl = '';
	}

	function releaseShownUrl(url) {
		if (!url) {
			return;
		}
		Object.keys(models).forEach(function (key) {
			var entry = models[key];
			if (entry && entry.blobUrl === url) {
				releaseBlobUrl(entry);
			}
		});
	}

	function discardModels() {
		Object.keys(models).forEach(function (key) {
			releaseBlobUrl(models[key]);
			delete models[key];
		});
	}
	var isWebXRSupported = false;
	var pendingAutoAr = false;
	var qrPromise = null;
	var qrSerial = 0;

	if (typeof navigator !== 'undefined' && navigator.xr && typeof navigator.xr.isSessionSupported === 'function') {
		navigator.xr.isSessionSupported('immersive-ar').then(function (supported) {
			isWebXRSupported = !!supported;
		}).catch(function () {
			isWebXRSupported = false;
		});
	}

	function isMobileDevice() {
		return /Android|iPhone|iPad|iPod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
	}

	function fillTemplate(template, values) {
		var text = template || '';
		var list = values.slice();
		text = text.replace(/%(\d+)\$s/g, function (_, index) {
			return list[Number(index) - 1];
		});
		return text.replace(/%s/g, function () {
			return list.shift();
		});
	}

	function toPersianDigits(n) {
		if (n === null || n === undefined) return '';
		return String(n);
	}

	function formatBytes(bytes) {
		var units = config.i18n || {};
		if (!bytes || bytes <= 0) {
			return units.zeroSize || '0 MB';
		}
		var mb = bytes / (1024 * 1024);
		if (mb >= 0.1) {
			return fillTemplate(units.megabytes || '%s MB', [mb.toFixed(1)]);
		}
		return fillTemplate(units.kilobytes || '%s KB', [String(Math.round(bytes / 1024))]);
	}

	function boot() {
		var launchers = document.querySelectorAll('.swiftglb-launcher');
		Array.prototype.forEach.call(launchers, prepareLauncher);
		bindVariations();
		watchForGallery();

		if (window.location.search.indexOf('swiftglb_ar=1') !== -1 || window.location.hash.indexOf('swiftglb_ar') !== -1) {
			pendingAutoAr = true;
			var nodes = document.querySelectorAll('.swiftglb-launcher');
			for (var i = 0; i < nodes.length; i++) {
				if (!nodes[i].hidden && !nodes[i].classList.contains('swiftglb-launcher--suppressed')) {
					open(nodes[i]);
					break;
				}
			}
		}
	}

	function prepareLauncher(launcher) {
		launcher.dataset.activeToken = launcher.dataset.token || '';
		launcher.dataset.activeHdToken = launcher.dataset.hdToken || '';
		launcher.dataset.posterOriginal = launcher.dataset.poster || '';
		var mapNode = launcher.querySelector('.swiftglb-map');
		launcher._map = [];
		launcher._picked = {};
		if (mapNode) {
			try {
				launcher._map = JSON.parse(mapNode.textContent || '[]');
			} catch (error) {
				launcher._map = [];
			}
		}
		mount(launcher);
		var button = launcher.querySelector('.swiftglb-pill');
		if (!button) {
			return;
		}
		['pointerenter', 'touchstart'].forEach(function (type) {
			button.addEventListener(type, function () {
				loadViewer();
			}, { once: true, passive: true });
		});
		['pointerdown', 'mousedown', 'touchstart'].forEach(function (type) {
			button.addEventListener(type, function (event) {
				event.stopPropagation();
			});
		});
		button.addEventListener('click', function (event) {
			event.preventDefault();
			event.stopPropagation();
			open(launcher);
		});
		if (mountedTwin(launcher)) {
			launcher.classList.add('swiftglb-launcher--suppressed');
		}
	}

	function sameProduct(left, right) {
		return !!left && !!right && (left.dataset.product || '') === (right.dataset.product || '');
	}

	function mountedTwin(launcher) {
		var nodes = document.querySelectorAll('.swiftglb-launcher.is-mounted');
		for (var i = 0; i < nodes.length; i++) {
			if (nodes[i] !== launcher && sameProduct(nodes[i], launcher)) {
				return nodes[i];
			}
		}
		return null;
	}

	function visibleTwin(launcher) {
		var nodes = document.querySelectorAll('.swiftglb-launcher');
		for (var i = 0; i < nodes.length; i++) {
			var other = nodes[i];
			if (other === launcher || !sameProduct(other, launcher) || other.classList.contains('swiftglb-launcher--suppressed') || other.hidden) {
				continue;
			}
			return other;
		}
		return null;
	}

	function suppressTwins(keeper) {
		var nodes = document.querySelectorAll('.swiftglb-launcher');
		Array.prototype.forEach.call(nodes, function (other) {
			if (other !== keeper && sameProduct(other, keeper)) {
				other.classList.add('swiftglb-launcher--suppressed');
			}
		});
	}

	function watchForGallery() {
		if (!document.body || !document.querySelector('.swiftglb-launcher--overlay:not(.is-mounted)')) {
			return;
		}
		var scheduled = false;
		var observer = new MutationObserver(function () {
			if (scheduled) {
				return;
			}
			scheduled = true;
			window.requestAnimationFrame(function () {
				scheduled = false;
				var pending = document.querySelectorAll('.swiftglb-launcher--overlay:not(.is-mounted)');
				Array.prototype.forEach.call(pending, mount);
				if (!document.querySelector('.swiftglb-launcher--overlay:not(.is-mounted)')) {
					observer.disconnect();
				}
			});
		});
		observer.observe(document.body, { childList: true, subtree: true });
		window.setTimeout(function () {
			observer.disconnect();
			Array.prototype.forEach.call(document.querySelectorAll('.swiftglb-launcher--wait:not(.is-mounted)'), function (launcher) {
				if (visibleTwin(launcher)) {
					launcher.classList.add('swiftglb-launcher--suppressed');
				} else {
					launcher.classList.remove('swiftglb-launcher--wait');
				}
			});
		}, 8000);
	}

	function mount(launcher) {
		if (!launcher.classList.contains('swiftglb-launcher--overlay')) {
			return;
		}
		var root = launcher.closest('.product') || document;
		var selectors = [
			'.woocommerce-product-gallery__wrapper',
			'.woocommerce-product-gallery .woocommerce-product-gallery__image',
			'.wd-gallery .wd-carousel',
			'.product-gallery-slider',
			'.product-gallery',
			'#product-root .aspect-square',
			'.product-single .aspect-square',
			'.product-images',
			'.woocommerce-product-gallery'
		];
		var host = null;
		for (var i = 0; i < selectors.length; i++) {
			host = root.querySelector(selectors[i]);
			if (host) {
				break;
			}
		}
		if (!host && root === document) {
			host = document.querySelector('#product-root .aspect-square, .product-single .aspect-square');
		}
		if (host && host.tagName === 'IMG') {
			host = host.parentElement;
		}
		if (!host || host === launcher || launcher.contains(host)) {
			return;
		}
		if (window.getComputedStyle(host).position === 'static') {
			host.classList.add('swiftglb-gallery-host');
		}
		host.appendChild(launcher);
		launcher.classList.add('is-mounted');
		suppressTwins(launcher);
	}

	function launchersNear(form) {
		var root = form.closest ? form.closest('.product') : null;
		var list = root ? root.querySelectorAll('.swiftglb-launcher') : [];
		if (!list.length) {
			list = document.querySelectorAll('.swiftglb-launcher');
		}
		return Array.prototype.slice.call(list);
	}

	function bindVariations() {
		if (window.jQuery) {
			window.jQuery(document.body).on('found_variation', 'form.variations_form', function (event, variation) {
				launchersNear(this).forEach(function (launcher) {
					var token = (variation && variation.swiftglb_token) || launcher.dataset.token || '';
					var hdToken = (variation && variation.swiftglb_hd_token) || launcher.dataset.hdToken || '';
					launcher.dataset.activeToken = token;
					launcher.dataset.activeHdToken = hdToken;
					launcher.dataset.variationId = variation && variation.variation_id ? String(variation.variation_id) : '';
					launcher.dataset.color = (variation && variation.swiftglb_color) || '';
					if (variation && variation.image && variation.image.full_src) {
						launcher.dataset.poster = variation.image.full_src;
					}
					launcher.hidden = token === '' && hdToken === '';
					if (modal && !modal.hidden && modal._launcher === launcher) {
						refreshOpen(launcher);
					}
				});
			});

			window.jQuery(document.body).on('reset_data', 'form.variations_form', function () {
				launchersNear(this).forEach(function (launcher) {
					launcher._picked = {};
					launcher.dataset.activeToken = launcher.dataset.token || '';
					launcher.dataset.activeHdToken = launcher.dataset.hdToken || '';
					launcher.dataset.variationId = '';
					launcher.dataset.color = '';
					if (launcher.dataset.posterOriginal) {
						launcher.dataset.poster = launcher.dataset.posterOriginal;
					}
					launcher.hidden = launcher.dataset.activeToken === '' && launcher.dataset.activeHdToken === '';
					if (modal && !modal.hidden && modal._launcher === launcher) {
						refreshOpen(launcher);
					}
				});
			});
		}

		document.addEventListener('click', function (event) {
			var button = event.target && event.target.closest ? event.target.closest('button, a, li, [role="radio"], [data-value]') : null;
			if (!button || !isSwatchControl(button)) {
				return;
			}
			var label = (button.getAttribute('title') || button.getAttribute('aria-label') || button.getAttribute('data-value') || button.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
			if (!label || label.length > 80) {
				return;
			}
			Array.prototype.forEach.call(document.querySelectorAll('.swiftglb-launcher'), function (launcher) {
				if (!launcher._map || !launcher._map.length) {
					return;
				}
				var root = launcher.closest('.product') || document.getElementById('product-root') || document.body;
				if (!root.contains(button)) {
					return;
				}
				applySwatch(launcher, label);
			});
		});
	}

	function isSwatchControl(node) {
		if (!node || !node.closest) {
			return false;
		}
		if (node.closest('.swiftglb-modal, .swiftglb-launcher, .swiftglb-qr-modal')) {
			return false;
		}
		if (node.classList.contains('single_add_to_cart_button') || node.classList.contains('qty')) {
			return false;
		}
		if (node.closest('form.cart') && node.type === 'submit') {
			return false;
		}
		if (node.closest('[class*="swatch"], [class*="variable-item"], .cfvsw-swatches-option, .variable-items-wrapper')) {
			return true;
		}
		return node.hasAttribute('data-value') && !!node.closest('.variations, form.variations_form');
	}

	function applySwatch(launcher, label) {
		var hits = launcher._map.filter(function (item) {
			return (item.labels || []).some(function (value) {
				return String(value).toLowerCase() === label;
			});
		});
		if (!hits.length) {
			return;
		}
		var picked = launcher._picked || {};
		picked[label] = true;
		Object.keys(picked).forEach(function (key) {
			if (key === label) {
				return;
			}
			var still = hits.some(function (item) {
				return (item.labels || []).some(function (value) {
					return String(value).toLowerCase() === key;
				});
			});
			if (!still) {
				delete picked[key];
			}
		});
		launcher._picked = picked;
		var match = launcher._map.find(function (item) {
			return Object.keys(picked).every(function (key) {
				return (item.labels || []).some(function (value) {
					return String(value).toLowerCase() === key;
				});
			});
		}) || hits[0];
		launcher.dataset.activeToken = match.token || launcher.dataset.token || '';
		launcher.dataset.activeHdToken = match.hdToken || launcher.dataset.hdToken || '';
		launcher.dataset.variationId = match.id ? String(match.id) : '';
		launcher.dataset.color = match.color || '';
		if (match.image) {
			launcher.dataset.poster = match.image;
		}
		launcher.hidden = launcher.dataset.activeToken === '' && launcher.dataset.activeHdToken === '';
		if (modal && !modal.hidden && modal._launcher === launcher) {
			refreshOpen(launcher);
		}
	}

	function configFrom(launcher) {
		return {
			productId: launcher.dataset.product || '',
			token: launcher.dataset.activeToken || launcher.dataset.token || '',
			hdToken: launcher.dataset.activeHdToken || launcher.dataset.hdToken || '',
			variationId: launcher.dataset.variationId || '',
			color: launcher.dataset.color || '',
			orbit: launcher.dataset.orbit === '1',
			fixed: launcher.dataset.fixed === '1',
			autorotate: launcher.dataset.autorotate === '1',
			hdAuto: launcher.dataset.hdAuto === '1',
			poster: launcher.dataset.poster || '',
			title: launcher.dataset.title || ''
		};
	}

	function ensureModal() {
		if (modal) {
			return modal;
		}
		modal = document.createElement('div');
		modal.className = 'swiftglb-modal';
		modal.hidden = true;
		modal.setAttribute('role', 'dialog');
		modal.setAttribute('aria-modal', 'true');
		modal.innerHTML = ''
			+ '<div class="swiftglb-modal__panel">'
			+ '  <div class="swiftglb-topbar">'
			+ '    <button type="button" class="swiftglb-close" data-close="1" aria-label="' + config.i18n.close + '">&times;</button>'
			+ '    <div class="swiftglb-toolbar" role="toolbar">'
			+ '      <div class="swiftglb-switch" role="group" aria-label="' + config.i18n.day + ' / ' + config.i18n.night + '">'
			+ '        <button type="button" data-scene="light" aria-label="' + config.i18n.day + '">' + iconSun + '</button>'
			+ '        <button type="button" data-scene="dark" aria-label="' + config.i18n.night + '">' + iconMoon + '</button>'
			+ '      </div>'
			+ '      <button type="button" class="swiftglb-tool-btn swiftglb-tool-rotate" aria-label="' + (config.i18n.autoRotate || 'Rotate') + '">' + iconRotate + '</button>'
			+ '      <button type="button" class="swiftglb-tool-btn swiftglb-tool-reset" aria-label="' + (config.i18n.resetView || 'Reset') + '">' + iconReset + '</button>'
			+ '      <button type="button" class="swiftglb-tool-btn swiftglb-tool-snapshot" aria-label="' + (config.i18n.snapshot || 'Photo') + '">' + iconCamera + '</button>'
			+ '      <button type="button" class="swiftglb-tool-btn swiftglb-tool-fullscreen" aria-label="' + (config.i18n.fullscreen || 'Full screen') + '">' + iconFullscreen + '</button>'
			+ '    </div>'
			+ '  </div>'
			+ '  <div class="swiftglb-hd-status" hidden>'
			+ '    <span class="swiftglb-hd-status__label"></span>'
			+ '    <span class="swiftglb-hd-status__track" aria-hidden="true"><span class="swiftglb-hd-status__fill"></span></span>'
			+ '  </div>'
			+ '  <div class="swiftglb-stage">'
			+ '    <div class="swiftglb-stage__viewer"></div>'
			+ '    <div class="swiftglb-stage__loader">'
			+ '      <div class="swiftglb-loader-card">'
			+ '        <div class="swiftglb-loader-icon" aria-hidden="true">'
			+ '          <svg viewBox="0 0 64 64" width="44" height="44" fill="none">'
			+ '            <path d="M32 6 L58 20 L32 34 L6 20 Z" fill="rgba(59, 130, 246, 0.22)" stroke="#3b82f6" stroke-width="2.5" stroke-linejoin="round"/>'
			+ '            <path d="M6 20 L32 34 L32 58 L6 44 Z" fill="rgba(37, 99, 235, 0.38)" stroke="#2563eb" stroke-width="2.5" stroke-linejoin="round"/>'
			+ '            <path d="M32 34 L58 20 L58 44 L32 58 Z" fill="rgba(29, 78, 216, 0.55)" stroke="#1d4ed8" stroke-width="2.5" stroke-linejoin="round"/>'
			+ '          </svg>'
			+ '        </div>'
			+ '        <h4 class="swiftglb-loader-text"></h4>'
			+ '        <div class="swiftglb-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span class="swiftglb-progress__bar"></span></div>'
			+ '        <div class="swiftglb-loader-meta">'
			+ '          <span class="swiftglb-loader-detail"></span>'
			+ '          <span class="swiftglb-loader-percent">0%</span>'
			+ '        </div>'
			+ '      </div>'
			+ '    </div>'
			+ '  </div>'
			+ '  <button type="button" class="swiftglb-ar" hidden></button>'
			+ '  <div class="swiftglb-pop" hidden>'
			+ '    <p class="swiftglb-pop__title"></p>'
			+ '    <p class="swiftglb-pop__text"></p>'
			+ '    <div class="swiftglb-pop__actions">'
			+ '      <button type="button" class="swiftglb-retry" hidden></button>'
			+ '      <button type="button" class="swiftglb-pop__ok" data-close="1"></button>'
			+ '    </div>'
			+ '  </div>'
			+ '  <div class="swiftglb-qr-modal" hidden>'
			+ '    <div class="swiftglb-qr-card">'
			+ '      <button type="button" class="swiftglb-qr-close" aria-label="' + config.i18n.close + '">&times;</button>'
			+ '      <h4 class="swiftglb-qr-title">' + (config.i18n.qrTitle || 'View in augmented reality') + '</h4>'
			+ '      <p class="swiftglb-qr-desc">' + (config.i18n.qrDesc || 'Scan this code with your phone camera:') + '</p>'
			+ '      <div class="swiftglb-qr-frame"></div>'
			+ '      <a class="swiftglb-qr-fallback" hidden></a>'
			+ '    </div>'
			+ '  </div>'
			+ '</div>';

		var arButton = modal.querySelector('.swiftglb-ar');
		arButton.innerHTML = (isMobileDevice() ? iconAr : iconQr) + '<span>' + config.i18n.ar + '</span>';
		modal.querySelector('.swiftglb-loader-text').textContent = config.i18n.loadingModel || config.i18n.loading;
		modal.querySelector('.swiftglb-retry').textContent = config.i18n.retry;
		modal.querySelector('.swiftglb-pop__ok').textContent = config.i18n.dismiss;

		var isDark = document.documentElement.classList.contains('dark') || document.body.classList.contains('dark') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
		modal.dataset.scene = isDark ? 'dark' : 'light';
		markScene(modal.dataset.scene);

		modal.addEventListener('click', function (event) {
			var sceneButton = event.target && event.target.closest ? event.target.closest('button[data-scene]') : null;
			if (sceneButton) {
				setScene(sceneButton.getAttribute('data-scene'));
				return;
			}
			var closeButton = event.target && event.target.closest ? event.target.closest('[data-close="1"]') : null;
			if (closeButton) {
				close();
			}
		});

		modal.querySelector('.swiftglb-retry').addEventListener('click', function () {
			if (modal._launcher) {
				hideNotice();
				load(configFrom(modal._launcher));
			}
		});

		modal.querySelector('.swiftglb-ar').addEventListener('click', function () {
			if (this.disabled || !modal || modal.dataset.arReady !== '1') {
				return;
			}
			startAR();
		});

		modal.querySelector('.swiftglb-tool-rotate').addEventListener('click', function () {
			var viewer = modal._viewer;
			if (!viewer) return;
			var rotating = viewer.hasAttribute('auto-rotate');
			if (rotating) {
				viewer.removeAttribute('auto-rotate');
				this.classList.remove('is-on');
			} else {
				viewer.setAttribute('auto-rotate', '');
				this.classList.add('is-on');
			}
		});

		modal.querySelector('.swiftglb-tool-reset').addEventListener('click', function () {
			var viewer = modal._viewer;
			if (!viewer) return;
			viewer.cameraOrbit = '0deg 75deg 105%';
			viewer.cameraTarget = 'auto auto auto';
			viewer.fieldOfView = 'auto';
			if (modal._launcher) {
				applyCameraLimits(viewer, configFrom(modal._launcher));
			}
		});

		modal.querySelector('.swiftglb-tool-snapshot').addEventListener('click', function () {
			var viewer = modal._viewer;
			if (!viewer || typeof viewer.toDataURL !== 'function') return;
			try {
				var dataUrl = viewer.toDataURL('image/png');
				var link = document.createElement('a');
				var cfg = modal._launcher ? configFrom(modal._launcher) : {};
				link.download = (cfg.title || 'product-3d') + '.png';
				link.href = dataUrl;
				document.body.appendChild(link);
				link.click();
				link.remove();
			} catch (e) {}
		});

		modal.querySelector('.swiftglb-tool-fullscreen').addEventListener('click', function () {
			toggleFullscreen();
		});

		modal.querySelector('.swiftglb-qr-close').addEventListener('click', function () {
			modal.querySelector('.swiftglb-qr-modal').hidden = true;
		});

		modal.querySelector('.swiftglb-qr-modal').addEventListener('click', function (e) {
			if (e.target === this) {
				this.hidden = true;
			}
		});

		document.addEventListener('keydown', onKeydown);
		document.body.appendChild(modal);
		return modal;
	}

	function toggleFullscreen() {
		if (!document.fullscreenElement && !document.webkitFullscreenElement) {
			if (modal.requestFullscreen) {
				modal.requestFullscreen();
			} else if (modal.webkitRequestFullscreen) {
				modal.webkitRequestFullscreen();
			}
		} else {
			if (document.exitFullscreen) {
				document.exitFullscreen();
			} else if (document.webkitExitFullscreen) {
				document.webkitExitFullscreen();
			}
		}
	}

	function onKeydown(event) {
		if (!modal || modal.hidden) {
			return;
		}
		if (event.key === 'Escape') {
			event.preventDefault();
			var qr = modal.querySelector('.swiftglb-qr-modal');
			if (qr && !qr.hidden) {
				qr.hidden = true;
				return;
			}
			close();
			return;
		}
		if (event.key !== 'Tab') {
			return;
		}
		var items = modal.querySelectorAll('button:not([hidden]), model-viewer');
		if (!items.length) {
			return;
		}
		var first = items[0];
		var last = items[items.length - 1];
		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		}
	}

	function open(launcher) {
		var cfg = configFrom(launcher);
		if (!/^[a-f0-9]{32}$/.test(cfg.token)) {
			return;
		}
		ensureModal();
		modal._launcher = launcher;
		modal.hidden = false;
		modal.setAttribute('aria-label', cfg.title || config.i18n.day);
		hideNotice();
		document.documentElement.classList.add('swiftglb-open');
		lastFocus = document.activeElement;
		modal.querySelector('.swiftglb-close').focus();
		if (!canRender3D()) {
			pendingAutoAr = false;
			showNotice('unsupported');
			return;
		}
		if ((modal.dataset.token === cfg.token || modal.dataset.baseToken === cfg.token) && modal._viewer && modal.dataset.currentQuality !== 'failed') {
			applyScene(modal._viewer, cfg);
			modal.querySelector('.swiftglb-stage').classList.add('is-ready');
			modal.dataset.sdReady = '1';
			maybeEnableAr();
			continueAutoAr();
			if (cfg.hdToken && modal.dataset.hdUrlReady !== '1' && modal._hdPromise) {
				applyHdWhenReady(modal._hdPromise, cfg, modal._loadSerial || requestSerial);
			}
			return;
		}
		load(cfg);
	}

	function refreshOpen(launcher) {
		var cfg = configFrom(launcher);
		if (!/^[a-f0-9]{32}$/.test(cfg.token)) {
			close();
			return;
		}
		if ((modal.dataset.token === cfg.token || modal.dataset.baseToken === cfg.token) && modal._viewer) {
			applyColor(modal._viewer, cfg.color);
			return;
		}
		load(cfg);
	}

	function close() {
		if (!modal) {
			return;
		}
		modal.hidden = true;
		setHdStatus(false);
		document.documentElement.classList.remove('swiftglb-open');
		if (document.fullscreenElement || document.webkitFullscreenElement) {
			if (document.exitFullscreen) {
				document.exitFullscreen();
			} else if (document.webkitExitFullscreen) {
				document.webkitExitFullscreen();
			}
		}
		if (lastFocus && typeof lastFocus.focus === 'function') {
			lastFocus.focus();
		}
	}

	function load(cfg) {
		var serial = ++requestSerial;
		var stage = modal.querySelector('.swiftglb-stage');
		var text = modal.querySelector('.swiftglb-loader-text');
		var ar = modal.querySelector('.swiftglb-ar');
		stage.classList.remove('is-ready', 'is-error');
		hideNotice();
		setHdStatus(false);
		text.textContent = config.i18n.loadingModel || config.i18n.loading;
		setProgress(5, 0, 0);
		clearViewer();
		modal.dataset.currentQuality = 'sd';
		modal.dataset.sdReady = '';
		modal.dataset.needsHd = '';
		modal.dataset.hdUrlReady = '';
		modal.dataset.baseToken = cfg.token;
		modal._loadSerial = serial;
		modal._hdPercent = 8;
		modal._hdPromise = null;
		setArReady(false);

		if (/^[a-f0-9]{32}$/.test(cfg.hdToken)) {
			modal.dataset.needsHd = '1';
			var hdCfg = {
				productId: cfg.productId,
				token: cfg.hdToken,
				variationId: cfg.variationId
			};
			if (models[cfg.hdToken] && models[cfg.hdToken].blob && freshArUrl(cfg.hdToken)) {
				modal._hdPercent = 100;
				modal._hdPromise = Promise.resolve(models[cfg.hdToken]);
			} else if (models[cfg.hdToken] && models[cfg.hdToken].blob) {
				modal._hdPromise = fetchModel(hdCfg, null, true).then(function (meta) {
					rememberArUrl(cfg.hdToken, meta && meta.arUrl);
					models[cfg.hdToken].arUrl = (meta && meta.arUrl) || '';
					return models[cfg.hdToken];
				});
			} else {
				modal._hdPromise = fetchModel(hdCfg, function (value) {
					modal._hdPercent = Math.max(8, Math.min(96, Math.round(value || 0)));
					if (modal && !modal.hidden && serial === requestSerial && modal.dataset.currentQuality !== 'hd') {
						setHdStatus(true, config.i18n.loadingHd || 'Downloading the original model…', modal._hdPercent);
					}
				}).then(function (downloaded) {
					models[cfg.hdToken] = downloaded;
					rememberArUrl(cfg.hdToken, downloaded && downloaded.arUrl);
					return downloaded;
				});
			}
		}
		modal._hdHooked = '';

		var rotBtn = modal.querySelector('.swiftglb-tool-rotate');
		if (rotBtn) {
			rotBtn.classList.toggle('is-on', !!cfg.autorotate);
		}

		var cached = models[cfg.token];
		var readyModel;
		if (cached && cached.blob && freshArUrl(cfg.token)) {
			readyModel = Promise.resolve(cached);
		} else if (cached && cached.blob) {
			readyModel = fetchModel(cfg, null, true).then(function (meta) {
				rememberArUrl(cfg.token, meta && meta.arUrl);
				cached.arUrl = (meta && meta.arUrl) || cached.arUrl || '';
				return cached;
			});
		} else {
			readyModel = fetchModel(cfg).then(function (downloaded) {
				models[cfg.token] = downloaded;
				rememberArUrl(cfg.token, downloaded && downloaded.arUrl);
				return downloaded;
			});
		}

		Promise.all([readyModel, loadViewer()]).then(function (results) {
			var downloaded = results[0];
			if (serial !== requestSerial) {
				return;
			}
			var viewer = document.createElement('model-viewer');
			viewer.setAttribute('camera-controls', '');
			viewer.setAttribute('touch-action', 'pan-y');
			viewer.setAttribute('pan-sensitivity', '0');
			viewer.setAttribute('disable-pan', '');
			viewer.setAttribute('interaction-prompt', 'none');
			viewer.setAttribute('environment-image', 'legacy');
			viewer.setAttribute('tone-mapping', 'commerce');
			viewer.setAttribute('exposure', '1');
			viewer.setAttribute('shadow-intensity', '1');
			viewer.setAttribute('shadow-softness', '0.55');
			viewer.setAttribute('ar', '');
			viewer.setAttribute('ar-modes', 'webxr quick-look');
			viewer.setAttribute('alt', cfg.title || '');
			viewer.setAttribute('ar-scale', cfg.fixed ? 'fixed' : 'auto');
			viewer.setAttribute('bounds', 'tight');
			viewer.setAttribute('dir', 'ltr');
			viewer.setAttribute('camera-target', 'auto auto auto');
			viewer.setAttribute('camera-orbit', '0deg 75deg 105%');

			if (cfg.autorotate) {
				viewer.setAttribute('auto-rotate', '');
				viewer.setAttribute('auto-rotate-delay', '800');
				viewer.setAttribute('rotation-per-second', '25deg');
				var rotBtn = modal.querySelector('.swiftglb-tool-rotate');
				if (rotBtn) rotBtn.classList.add('is-on');
			}

			applyCameraLimits(viewer, cfg);

			viewer.addEventListener('progress', function (event) {
				var amount = event.detail && typeof event.detail.totalProgress === 'number' ? event.detail.totalProgress : 0;
				setProgress(Math.max(88, Math.min(99, Math.round(88 + amount * 11))));
			});

			viewer.addEventListener('load', function () {
				if (serial !== requestSerial) {
					return;
				}
				var first = viewer.dataset.swiftglbBooted !== '1';
				viewer.dataset.swiftglbBooted = '1';
				applyColor(viewer, modal._launcher ? configFrom(modal._launcher).color : cfg.color);
				applySceneLook(viewer);
				stage.classList.add('is-ready');
				if (!cfg.hdToken) {
					var fresh = freshArUrl(cfg.token);
					if (fresh) {
						modal.dataset.arUrl = fresh;
					}
				}
				maybeEnableAr();
				if (!first) {
					if (modal.dataset.token === cfg.hdToken) {
						modal.dataset.currentQuality = 'hd';
						setHdStatus(false);
						maybeEnableAr();
						continueAutoAr();
					}
					return;
				}
				setProgress(100);
				releaseShownUrl(String(viewer.getAttribute('src') || viewer.src || ''));
				modal.dataset.blobUrl = '';
				modal.dataset.sdReady = '1';
				maybeEnableAr();
				continueAutoAr();
			});

			viewer.addEventListener('error', function () {
				if (serial !== requestSerial) {
					return;
				}
				if (viewer.dataset.swiftglbBooted === '1' && cfg.hdToken && modal.dataset.token === cfg.hdToken) {
					var sdEntry = models[cfg.token];
					modal.dataset.currentQuality = 'sd';
					modal.dataset.token = cfg.token;
					modal._hdHooked = '';
					setHdStatus(false);
					if (sdEntry) {
						viewer.src = blobUrlFor(sdEntry);
					}
					return;
				}
				fail();
			});

			viewer.addEventListener('ar-status', function (event) {
				var status = event.detail && event.detail.status;
				if (status === 'failed') {
					showArError();
					return;
				}
				if (status !== 'not-presenting' || !modal) {
					return;
				}
				var entry = models[modal.dataset.token];
				if (!entry || !entry.blob) {
					return;
				}
				var current = String(viewer.src || '');
				if (entry.shownUrl && current === entry.shownUrl) {
					return;
				}
				var url = blobUrlFor(entry);
				if (!url || current === url) {
					return;
				}
				modal.dataset.blobUrl = url;
				viewer.src = url;
			});

			modal.querySelector('.swiftglb-stage__viewer').appendChild(viewer);
			modal._viewer = viewer;
			modal.dataset.token = cfg.token;
			modal.dataset.blobUrl = blobUrlFor(downloaded);
			if (!cfg.hdToken) {
				rememberArUrl(cfg.token, freshArUrl(cfg.token) || downloaded.arUrl || '');
				modal.dataset.arUrl = freshArUrl(cfg.token);
				scheduleArRefresh(cfg.token, serial);
			}
			applySceneLook(viewer);
			viewer.src = modal.dataset.blobUrl;
			if (cfg.hdToken && modal._hdPromise) {
				applyHdWhenReady(modal._hdPromise, cfg, serial);
			}
		}).catch(function () {
			if (serial === requestSerial) {
				fail();
			}
		});

		function fail() {
			pendingAutoAr = false;
			stage.classList.add('is-error');
			ar.hidden = true;
			showNotice('error');
		}
	}

	function applyCameraLimits(viewer, cfg) {
		if (cfg && cfg.orbit) {
			viewer.setAttribute('min-camera-orbit', 'auto 0deg 30%');
			viewer.setAttribute('max-camera-orbit', 'auto 90deg 300%');
			return;
		}
		viewer.setAttribute('min-camera-orbit', 'auto 0deg 30%');
		viewer.setAttribute('max-camera-orbit', 'auto 180deg 300%');
	}

	function canRender3D() {
		try {
			var canvas = document.createElement('canvas');
			return !!(window.WebGLRenderingContext && (canvas.getContext('webgl2') || canvas.getContext('webgl') || canvas.getContext('experimental-webgl')));
		} catch (error) {
			return false;
		}
	}

	function showNotice(kind) {
		if (!modal) {
			return;
		}
		var pop = modal.querySelector('.swiftglb-pop');
		var title = modal.querySelector('.swiftglb-pop__title');
		var text = modal.querySelector('.swiftglb-pop__text');
		var retry = modal.querySelector('.swiftglb-retry');
		if (kind === 'ar-unsupported') {
			title.textContent = config.i18n.arUnsupportedTitle || config.i18n.unsupportedTitle;
			text.textContent = config.i18n.arUnsupported || config.i18n.unsupported;
			retry.hidden = true;
		} else if (kind === 'unsupported') {
			title.textContent = config.i18n.unsupportedTitle;
			text.textContent = config.i18n.unsupported;
			retry.hidden = true;
		} else {
			title.textContent = config.i18n.errorTitle;
			text.textContent = config.i18n.error;
			retry.hidden = false;
		}
		pop.hidden = false;
	}

	function hideNotice() {
		var pop = modal && modal.querySelector('.swiftglb-pop');
		if (pop) {
			pop.hidden = true;
		}
	}

	function markScene(mode) {
		Array.prototype.forEach.call(modal.querySelectorAll('.swiftglb-switch [data-scene]'), function (button) {
			var on = button.getAttribute('data-scene') === mode;
			button.classList.toggle('is-on', on);
			button.setAttribute('aria-pressed', on ? 'true' : 'false');
		});
	}

	function setScene(mode) {
		modal.dataset.scene = mode === 'dark' ? 'dark' : 'light';
		markScene(modal.dataset.scene);
		if (modal._viewer) {
			applySceneLook(modal._viewer);
		}
	}

	function applySceneLook(viewer) {
		var dark = modal.dataset.scene === 'dark';
		viewer.setAttribute('environment-image', 'legacy');
		viewer.setAttribute('tone-mapping', 'commerce');
		viewer.setAttribute('exposure', dark ? '0.62' : '1');
		viewer.setAttribute('shadow-intensity', dark ? '0.5' : '1');
		viewer.setAttribute('shadow-softness', dark ? '0.85' : '0.55');
	}

	function clearViewer() {
		if (modal._arRefreshTimer) {
			window.clearTimeout(modal._arRefreshTimer);
			modal._arRefreshTimer = 0;
		}
		if (modal._viewer) {
			modal._viewer.remove();
			modal._viewer = null;
		}
		modal.dataset.token = '';
		modal.dataset.blobUrl = '';
		modal.dataset.arUrl = '';
	}

	function setHdStatus(visible, label, percent) {
		if (!modal) {
			return;
		}
		var status = modal.querySelector('.swiftglb-hd-status');
		if (!status) {
			return;
		}
		status.hidden = !visible;
		var text = status.querySelector('.swiftglb-hd-status__label');
		var fill = status.querySelector('.swiftglb-hd-status__fill');
		if (text && label) {
			text.textContent = label;
		}
		if (fill) {
			fill.style.width = Math.max(0, Math.min(100, Math.round(percent || 0))) + '%';
		}
	}

	function setArReady(ready) {
		var ar = modal && modal.querySelector('.swiftglb-ar');
		if (!modal || !ar) {
			return;
		}
		ar.hidden = false;
		ar.disabled = !ready;
		ar.classList.toggle('is-waiting', !ready);
		ar.setAttribute('aria-disabled', ready ? 'false' : 'true');
		if (ready) {
			ar.removeAttribute('title');
		} else {
			ar.title = config.i18n.arWaiting || 'Augmented reality starts when the model has loaded';
		}
		modal.dataset.arReady = ready ? '1' : '';
	}

	function rememberArUrl(token, url) {
		if (!token || !url || url.indexOf('https://') !== 0) {
			return;
		}
		arUrls[token] = { url: url, at: Date.now() };
	}

	function freshArUrl(token) {
		var item = token ? arUrls[token] : null;
		if (!item || Date.now() - item.at > 20 * 60 * 1000) {
			return '';
		}
		return item.url;
	}

	function maybeEnableAr() {
		if (!modal) {
			return;
		}
		var needsHd = modal.dataset.needsHd === '1';
		var shown = needsHd ? modal.dataset.currentQuality === 'hd' : modal.dataset.sdReady === '1';
		var url = modal.dataset.arUrl || '';
		var ready = shown && url.indexOf('https://') === 0;
		setArReady(ready);
	}

	function scheduleArRefresh(token, serial) {
		if (!modal || !token) {
			return;
		}
		window.clearTimeout(modal._arRefreshTimer);
		modal._arRefreshTimer = window.setTimeout(function () {
			refreshArUrl(token, serial);
		}, 15 * 60 * 1000);
	}

	function refreshArUrl(token, serial) {
		if (!modal || modal.hidden || serial !== requestSerial || !token || !modal._launcher) {
			return;
		}
		var base = configFrom(modal._launcher);
		fetchModel({
			productId: base.productId,
			token: token,
			variationId: base.variationId
		}, null, true).then(function (meta) {
			if (!modal || modal.hidden || serial !== requestSerial) {
				return;
			}
			rememberArUrl(token, meta && meta.arUrl);
			var fresh = freshArUrl(token);
			if (fresh && models[token]) {
				models[token].arUrl = fresh;
			}
			if (fresh && (modal.dataset.token === token || (!modal.dataset.needsHd && modal.dataset.baseToken === token))) {
				modal.dataset.arUrl = fresh;
				maybeEnableAr();
			}
			scheduleArRefresh(token, serial);
		}).catch(function () {
			if (modal && serial === requestSerial) {
				scheduleArRefresh(token, serial);
			}
		});
	}

	function applyHdWhenReady(promise, cfg, serial) {
		if (!promise || !modal || !cfg || !cfg.hdToken) {
			return;
		}
		setHdStatus(true, config.i18n.loadingHd || 'Downloading the original model…', modal._hdPercent || 8);
		if (modal._hdHooked === cfg.hdToken) {
			return;
		}
		modal._hdHooked = cfg.hdToken;
		promise.then(function (downloaded) {
			if (!modal || modal.hidden || serial !== requestSerial || !modal._viewer) {
				if (modal && modal._hdHooked === cfg.hdToken) {
					modal._hdHooked = '';
				}
				return;
			}
			rememberArUrl(cfg.hdToken, freshArUrl(cfg.hdToken) || (downloaded && downloaded.arUrl) || '');
			modal.dataset.arUrl = freshArUrl(cfg.hdToken);
			modal.dataset.hdUrlReady = modal.dataset.arUrl ? '1' : '';
			scheduleArRefresh(cfg.hdToken, serial);
			if (downloaded && downloaded.blob) {
				modal.dataset.token = cfg.hdToken;
				modal.dataset.blobUrl = blobUrlFor(downloaded);
				setHdStatus(true, config.i18n.rendering || 'Preparing the high-quality model…', 100);
				modal._viewer.src = modal.dataset.blobUrl;
			} else {
				modal._hdHooked = '';
				setHdStatus(false);
				maybeEnableAr();
			}
		}).catch(function () {
			if (modal && serial === requestSerial) {
				modal._hdHooked = '';
				setHdStatus(false);
			}
		});
	}

	function fetchModel(cfg, onProgress, headersOnly) {
		return requestModel(cfg, false, onProgress, headersOnly).catch(function () {
			return requestModel(cfg, true, onProgress, headersOnly);
		});
	}

	function requestModel(cfg, useAjax, onProgress, headersOnly) {
		function report(value, received, total) {
			if (typeof onProgress === 'function') {
				onProgress(value, received, total);
			} else {
				setProgress(value, received, total);
			}
		}
		var url = useAjax ? new URL(config.ajax, window.location.href) : new URL(config.rest + cfg.token, window.location.href);
		if (useAjax) {
			url.searchParams.set('action', 'swiftglb_model');
			url.searchParams.set('token', cfg.token);
			url.searchParams.set('nonce', config.view);
		}
		url.searchParams.set('p', cfg.productId);
		url.searchParams.set('product_id', cfg.productId);
		if (cfg.variationId) {
			url.searchParams.set('v', cfg.variationId);
			url.searchParams.set('variation_id', cfg.variationId);
		}

		return window.fetch(url.toString(), {
			method: 'GET',
			credentials: 'same-origin',
			headers: {
				'X-WP-Nonce': config.nonce,
				'X-SwiftGLB-Nonce': config.view
			}
		}).then(function (response) {
			var type = response.headers.get('Content-Type') || '';
			if (!response.ok || type.indexOf('json') !== -1) {
				throw new Error('model');
			}
			var arUrl = response.headers.get('X-SwiftGLB-AR-URL') || '';
			if (headersOnly) {
				if (response.body && typeof response.body.cancel === 'function') {
					response.body.cancel();
				}
				if (!response.ok || !arUrl) {
					throw new Error('model');
				}
				report(100, 0, 0);
				return { arUrl: arUrl };
			}
			var total = Number(response.headers.get('Content-Length')) || 0;
			if (!response.body || !response.body.getReader) {
				return response.blob().then(function (blob) {
					var blobSize = blob.size || total;
					report(88, blobSize, blobSize);
					return { blob: blob, blobUrl: URL.createObjectURL(blob), arUrl: arUrl };
				});
			}
			var reader = response.body.getReader();
			var chunks = [];
			var received = 0;
			function pump() {
				return reader.read().then(function (result) {
					if (result.done) {
						var blob = new Blob(chunks, { type: 'model/gltf-binary' });
						report(88, received, total || received);
						return { blob: blob, blobUrl: URL.createObjectURL(blob), arUrl: arUrl };
					}
					chunks.push(result.value);
					received += result.value.length;
					if (total) {
						report(Math.min(88, Math.round((received / total) * 88)), received, total);
					} else {
						report(Math.min(85, Math.round(received / (1024 * 100))), received, 0);
					}
					return pump();
				});
			}
			return pump();
		});
	}

	function loadViewer() {
		if (window.customElements && customElements.get('model-viewer')) {
			pointDraco();
			return Promise.resolve();
		}
		if (!config.viewer) {
			return Promise.reject(new Error('viewer'));
		}
		if (!viewerPromise) {
			viewerPromise = new Promise(function (resolve, reject) {
				var script = document.createElement('script');
				var settled = false;
				function finish() {
					if (settled) {
						return;
					}
					settled = true;
					pointDraco();
					resolve();
				}
				script.type = 'module';
				script.async = true;
				script.src = config.viewer;
				script.crossOrigin = 'anonymous';
				script.onerror = function () {
					if (!settled) {
						settled = true;
						reject(new Error('viewer'));
					}
				};
				if (window.customElements) {
					customElements.whenDefined('model-viewer').then(finish);
				}
				script.onload = finish;
				document.head.appendChild(script);
			});
		}
		return viewerPromise;
	}

	function pointDraco() {
		if (!config.draco) {
			return;
		}
		window.ModelViewerElement = window.ModelViewerElement || {};
		window.ModelViewerElement.dracoDecoderLocation = config.draco;
		var ctor = window.customElements && customElements.get('model-viewer');
		if (!ctor) {
			return;
		}
		try {
			ctor.dracoDecoderLocation = config.draco;
		} catch (error) {
			return;
		}
	}

	function applyScene(viewer, cfg) {
		applyColor(viewer, cfg.color);
		viewer.setAttribute('ar-scale', cfg.fixed ? 'fixed' : 'auto');
		applyCameraLimits(viewer, cfg);
		applySceneLook(viewer);
	}

	function applyColor(viewer, hex) {
		if (!viewer || !viewer.model || !viewer.model.materials) {
			return;
		}
		var match = /^#([0-9a-f]{6})$/i.exec(hex || '');
		var factors = null;
		if (match) {
			var value = parseInt(match[1], 16);
			factors = [
				srgbToLinear(((value >> 16) & 255) / 255),
				srgbToLinear(((value >> 8) & 255) / 255),
				srgbToLinear((value & 255) / 255),
				1
			];
		}
		Array.prototype.forEach.call(viewer.model.materials, function (material) {
			if (!material.pbrMetallicRoughness || typeof material.pbrMetallicRoughness.setBaseColorFactor !== 'function') {
				return;
			}
			if (!factors) {
				material.pbrMetallicRoughness.setBaseColorFactor([1, 1, 1, 1]);
				return;
			}
			material.pbrMetallicRoughness.setBaseColorFactor(factors);
		});
	}

	function srgbToLinear(channel) {
		if (channel <= 0.04045) {
			return channel / 12.92;
		}
		return Math.pow((channel + 0.055) / 1.055, 2.4);
	}

	function continueAutoAr() {
		if (!pendingAutoAr || !modal) {
			return;
		}
		if (!isMobileDevice()) {
			pendingAutoAr = false;
			return;
		}
		if (modal.dataset.arReady !== '1') {
			return;
		}
		pendingAutoAr = false;
		var ar = modal.querySelector('.swiftglb-ar');
		if (ar) {
			ar.hidden = false;
			ar.classList.add('is-attention');
		}
		if (ar && typeof ar.focus === 'function') {
			ar.focus();
		}
	}

	function startAR() {
		var viewer = modal && modal._viewer;
		var arUrl = modal ? (modal.dataset.arUrl || '') : '';
		if (!viewer) {
			showArError();
			return;
		}

		// If user is on Desktop, show the scan-with-phone QR code!
		if (!isMobileDevice()) {
			showDesktopQr();
			return;
		}

		if (typeof viewer.activateAR !== 'function') {
			showArError();
			return;
		}

		if (arUrl.indexOf('https://') !== 0) {
			showArError();
			return;
		}

		var cfg = modal._launcher ? configFrom(modal._launcher) : {};
		viewer.setAttribute('ar-scale', cfg.fixed ? 'fixed' : 'auto');

		// Chrome WebXR presents the model already on the page. Scene Viewer is not in ar-modes, so this cannot open the Google app.
		try {
			viewer.activateAR();
		} catch (e) {
			showArError();
		}
	}

	function showDesktopQr() {
		var qrModal = modal && modal.querySelector('.swiftglb-qr-modal');
		var frame = modal && modal.querySelector('.swiftglb-qr-frame');
		if (!qrModal || !frame) return;

		var targetUrl = window.location.href;
		if (targetUrl.indexOf('#') !== -1) {
			targetUrl = targetUrl.split('#')[0];
		}
		targetUrl += (targetUrl.indexOf('?') === -1 ? '?' : '&') + 'swiftglb_ar=1';

		var generation = ++qrSerial;
		var fallback = modal.querySelector('.swiftglb-qr-fallback');
		frame.textContent = '';
		if (fallback) {
			fallback.hidden = true;
			fallback.removeAttribute('href');
			fallback.textContent = '';
		}
		function showFallback() {
			if (generation !== qrSerial || !fallback) {
				return;
			}
			frame.textContent = '';
			fallback.hidden = false;
			fallback.href = targetUrl;
			fallback.textContent = targetUrl;
		}
		loadQr().then(function () {
			if (generation !== qrSerial) {
				return;
			}
			frame.textContent = '';
			var canvas = document.createElement('canvas');
			canvas.className = 'swiftglb-qr-canvas';
			if (window.Saz3dQr && window.Saz3dQr.draw(canvas, targetUrl)) {
				frame.appendChild(canvas);
				return;
			}
			showFallback();
		}).catch(showFallback);

		qrModal.hidden = false;
	}

	function loadQr() {
		if (window.Saz3dQr) {
			return Promise.resolve();
		}
		if (!config.qr) {
			return Promise.reject(new Error('qr'));
		}
		if (!qrPromise) {
			qrPromise = new Promise(function (resolve, reject) {
				var script = document.createElement('script');
				script.src = config.qr;
				script.onload = function () {
					resolve();
				};
				script.onerror = function () {
					qrPromise = null;
					reject(new Error('qr'));
				};
				document.head.appendChild(script);
			});
		}
		return qrPromise;
	}

	function showArError() {
		showNotice('ar-unsupported');
	}

	function setProgress(value, received, total) {
		if (!modal) {
			return;
		}
		var amount = Math.max(0, Math.min(100, Math.round(value || 0)));
		var bar = modal.querySelector('.swiftglb-progress');
		var fill = modal.querySelector('.swiftglb-progress__bar');
		var percent = modal.querySelector('.swiftglb-loader-percent');
		var detail = modal.querySelector('.swiftglb-loader-detail');
		var text = modal.querySelector('.swiftglb-loader-text');

		if (bar) {
			bar.setAttribute('aria-valuenow', String(amount));
		}
		if (fill) {
			fill.style.width = amount + '%';
			if (amount >= 88 && amount < 100) {
				fill.classList.add('is-processing');
			} else {
				fill.classList.remove('is-processing');
			}
		}
		if (percent) {
			percent.textContent = String(amount) + '%';
		}
		if (detail) {
			if (received && total && total > 0) {
				detail.textContent = fillTemplate(config.i18n.bytesOf || '%1$s of %2$s', [formatBytes(received), formatBytes(total)]);
			} else if (amount >= 100) {
				detail.textContent = config.i18n.rendering || 'Preparing the view…';
			} else if (amount >= 88) {
				detail.textContent = config.i18n.rendering || 'Rendering the 3D model…';
			} else if (received) {
				detail.textContent = fillTemplate(config.i18n.downloaded || '%s downloaded', [formatBytes(received)]);
			} else {
				detail.textContent = config.i18n.connecting || 'Connecting…';
			}
		}
		if (text && amount >= 88) {
			text.textContent = config.i18n.rendering || 'Preparing the 3D view…';
		}
	}

	window.addEventListener('pagehide', discardModels);

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
}());
