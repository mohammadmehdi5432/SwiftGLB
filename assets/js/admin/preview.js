/**
 * Try3D Admin Preview
 * Single Responsibility: Interactive 3D preview modal lifecycle within WordPress admin.
 * 
 * @package Try3D
 */

(function (root) {
	'use strict';

	/**
	 * @param {Object} config Admin configuration from wp_localize_script.
	 * @constructor
	 */
	function AdminPreview(config) {
		this.config = config || {};
		this.modal = null;
	}

	AdminPreview.prototype.loadViewerScript = function () {
		if (window.customElements && customElements.get('model-viewer')) {
			return Promise.resolve();
		}
		var self = this;
		return new Promise(function (resolve, reject) {
			var script = document.createElement('script');
			script.type = 'module';
			script.src = self.config.viewer;
			script.crossOrigin = 'anonymous';
			script.onload = resolve;
			script.onerror = reject;
			document.head.appendChild(script);
		});
	};

	AdminPreview.prototype.ensureModal = function () {
		if (this.modal) {
			return this.modal;
		}

		var modal = document.createElement('div');
		modal.className = 'try3d-admin-modal';
		modal.hidden = true;
		modal.innerHTML = ''
			+ '<div class="try3d-admin-modal__backdrop"></div>'
			+ '<div class="try3d-admin-modal__box">'
			+ '  <div class="try3d-admin-modal__head">'
			+ '    <h3>' + (this.config.previewTitle || '3D model preview') + '</h3>'
			+ '    <button type="button" class="try3d-admin-modal__close">&times;</button>'
			+ '  </div>'
			+ '  <div class="try3d-admin-modal__body">'
			+ '    <div class="try3d-admin-modal__loader">' + this.config.uploading + '</div>'
			+ '  </div>'
			+ '</div>';

		var self = this;
		modal.querySelector('.try3d-admin-modal__close').addEventListener('click', function () {
			self.close();
		});
		modal.querySelector('.try3d-admin-modal__backdrop').addEventListener('click', function () {
			self.close();
		});

		document.body.appendChild(modal);
		this.modal = modal;
		return modal;
	};

	AdminPreview.prototype.open = function (token) {
		if (!token) return;

		var modal = this.ensureModal();
		var body = modal.querySelector('.try3d-admin-modal__body');
		modal.hidden = false;
		body.innerHTML = '<div class="try3d-admin-modal__loader">Loading the model…</div>';

		var streamUrl = this.config.ajax + '?action=try3d_preview&nonce=' + encodeURIComponent(this.config.preview || '') + '&token=' + encodeURIComponent(token);

		this.loadViewerScript().then(function () {
			body.innerHTML = '';
			var viewer = document.createElement('model-viewer');
			viewer.setAttribute('camera-controls', '');
			viewer.setAttribute('touch-action', 'pan-y');
			viewer.setAttribute('auto-rotate', '');
			viewer.setAttribute('shadow-intensity', '0.85');
			viewer.setAttribute('environment-image', 'neutral');
			viewer.style.width = '100%';
			viewer.style.height = '100%';
			viewer.src = streamUrl;
			body.appendChild(viewer);
		}).catch(function () {
			body.innerHTML = '<div class="try3d-admin-modal__loader" style="color:#d63638;">The preview could not be loaded.</div>';
		});
	};

	AdminPreview.prototype.close = function () {
		if (this.modal) {
			this.modal.hidden = true;
			var body = this.modal.querySelector('.try3d-admin-modal__body');
			if (body) {
				body.innerHTML = '';
			}
		}
	};

	AdminPreview.prototype.isOpen = function () {
		return !!(this.modal && !this.modal.hidden);
	};

	root.Try3D_AdminPreview = AdminPreview;
}(typeof window !== 'undefined' ? window : this));
