/**
 * SwiftGLB Admin Master Application
 * Architecture: SOLID & Component-Based JavaScript
 * 
 * Modules:
 * - SwiftGLB_AdminUtils   (Utility formatters & DOM helpers)
 * - SwiftGLB_AdminUploader (File upload, real-time byte & percentage calculation)
 * - SwiftGLB_AdminPreview  (Live 3D preview modal lifecycle)
 * - SwiftGLB_AdminEvents   (Event delegation & drag-and-drop bindings)
 * 
 * @package SwiftGLB
 */

(function (window, document) {
	'use strict';

	if (!window.swiftglbAdmin) {
		return;
	}

	var config = window.swiftglbAdmin;

	// 1. Utilities Module
	var Utils = window.SwiftGLB_AdminUtils || {
		fillTemplate: function (template, values) {
			var text = template || '';
			var list = values.slice();
			text = text.replace(/%(\d+)\$s/g, function (_, index) {
				return list[Number(index) - 1];
			});
			return text.replace(/%s/g, function () {
				return list.shift();
			});
		},
		toPersianDigits: function (n) {
			if (n === null || n === undefined) return '';
			return String(n);
		},
		formatBytes: function (bytes) {
			var units = config || {};
			if (!bytes || bytes <= 0) return units.zeroSize || '0 MB';
			if (bytes < 1024 * 1024) {
				return this.fillTemplate(units.kilobytes || '%s KB', [(bytes / 1024).toFixed(0)]);
			}
			return this.fillTemplate(units.megabytes || '%s MB', [(bytes / (1024 * 1024)).toFixed(1)]);
		},
		closestFile: function (node) {
			return node && node.closest ? node.closest('.swiftglb-file') : null;
		}
	};

	// 2. Uploader Module
	var Uploader = (function () {
		function AdminUploader(cfg) {
			this.config = cfg || {};
		}

		AdminUploader.prototype.setBusy = function (wrap, busy) {
			var button = wrap.querySelector('.swiftglb-upload-btn');
			if (!button) return;
			button.disabled = busy;
			button.textContent = busy ? this.config.uploading : this.config.upload;
		};

		AdminUploader.prototype.showError = function (wrap, message) {
			var slot = wrap.querySelector('.swiftglb-file-error');
			if (slot) {
				slot.textContent = message || '';
			}
		};

		AdminUploader.prototype.getProgressElements = function (wrap) {
			var prog = wrap.querySelector('.swiftglb-upload-progress');
			if (!prog) {
				prog = document.createElement('div');
				prog.className = 'swiftglb-upload-progress';
				prog.innerHTML = ''
					+ '<div class="swiftglb-upload-bar"><div class="swiftglb-upload-fill"></div></div>'
					+ '<div class="swiftglb-upload-meta">'
					+ '  <span class="swiftglb-upload-status">Uploading the file…</span>'
					+ '  <div class="swiftglb-upload-numbers">'
					+ '    <span class="swiftglb-upload-bytes"></span>'
					+ '    <span class="swiftglb-upload-percent">0%</span>'
					+ '  </div>'
					+ '</div>';
				wrap.appendChild(prog);
			}
			return {
				wrap: prog,
				fill: prog.querySelector('.swiftglb-upload-fill'),
				status: prog.querySelector('.swiftglb-upload-status'),
				bytes: prog.querySelector('.swiftglb-upload-bytes'),
				percent: prog.querySelector('.swiftglb-upload-percent')
			};
		};

		AdminUploader.prototype.assignFile = function (wrap, payload) {
			var token = wrap.querySelector('.swiftglb-token');
			var filename = wrap.querySelector('.swiftglb-filename');
			var label = wrap.querySelector('.swiftglb-file-name');
			var remove = wrap.querySelector('.swiftglb-remove-btn');
			var preview = wrap.querySelector('.swiftglb-preview-btn');

			if (token) {
				token.value = payload.token || '';
				token.dispatchEvent(new Event('change', { bubbles: true }));
				token.dispatchEvent(new Event('input', { bubbles: true }));
			}
			if (filename) {
				filename.value = payload.filename || '';
			}
			if (label) {
				label.textContent = payload.token
					? (payload.filename || 'model.glb') + (payload.size ? ' (' + payload.size + ')' : '')
					: this.config.empty;
			}
			if (remove) {
				remove.hidden = !payload.token;
			}
			if (preview) {
				preview.hidden = !payload.token;
			}
		};

		AdminUploader.prototype.upload = function (wrap, file) {
			if (!file) return;

			var self = this;
			var ext = (file.name.split('.').pop() || '').toLowerCase();
			var input = wrap.querySelector('.swiftglb-file-input');
			if (['glb', 'w3d', 'bin'].indexOf(ext) === -1) {
				if (input) {
					input.value = '';
				}
				self.showError(wrap, self.config.badType || self.config.error);
				return;
			}
			if (self.config.max && file.size > self.config.max) {
				if (input) {
					input.value = '';
				}
				self.showError(wrap, self.config.maxMessage || self.config.error);
				return;
			}

			self.showError(wrap, '');
			self.setBusy(wrap, true);

			var prog = self.getProgressElements(wrap);
			prog.wrap.hidden = false;
			prog.fill.style.width = '0%';
			prog.fill.classList.remove('is-processing');
			prog.status.textContent = self.config.sending || 'Sending the file to the server…';
			prog.bytes.textContent = Utils.fillTemplate(self.config.bytesOf || '%1$s of %2$s', [self.config.zeroSize || '0 MB', Utils.formatBytes(file.size)]);
			prog.percent.textContent = '0%';

			var xhr = new XMLHttpRequest();
			xhr.open('POST', self.config.ajax, true);
			xhr.withCredentials = true;

			xhr.upload.addEventListener('progress', function (e) {
				if (e.lengthComputable && e.total > 0) {
					var pct = Math.min(100, Math.round((e.loaded / e.total) * 100));
					prog.fill.style.width = pct + '%';
					prog.percent.textContent = Utils.toPersianDigits(pct) + '%';
					prog.bytes.textContent = Utils.fillTemplate(self.config.bytesOf || '%1$s of %2$s', [Utils.formatBytes(e.loaded), Utils.formatBytes(e.total)]);

					if (pct >= 100) {
						prog.status.textContent = self.config.protecting || 'Protecting the file on the server…';
						prog.fill.classList.add('is-processing');
					}
				}
			});

			xhr.addEventListener('load', function () {
				self.setBusy(wrap, false);
				var input = wrap.querySelector('.swiftglb-file-input');
				if (input) {
					input.value = '';
				}
				prog.wrap.hidden = true;

				try {
					var response = JSON.parse(xhr.responseText);
					if (xhr.status >= 200 && xhr.status < 300 && response && response.success) {
						self.assignFile(wrap, response.data || {});
					} else {
						var msg = (response && response.data && response.data.message) || self.config.failed || self.config.error;
						self.showError(wrap, msg);
					}
				} catch (err) {
					self.showError(wrap, self.config.failed || self.config.error);
				}
			});

			xhr.addEventListener('error', function () {
				self.setBusy(wrap, false);
				prog.wrap.hidden = true;
				self.showError(wrap, self.config.failed || self.config.error);
			});

			xhr.addEventListener('abort', function () {
				self.setBusy(wrap, false);
				prog.wrap.hidden = true;
			});

			var body = new FormData();
			body.append('action', 'swiftglb_upload');
			body.append('nonce', self.config.nonce);
			body.append('file', file);
			xhr.send(body);
		};

		return AdminUploader;
	}());

	// 3. Preview Module
	var Preview = (function () {
		function AdminPreview(cfg) {
			this.config = cfg || {};
			this.modal = null;
		}

		AdminPreview.prototype.loadViewerScript = function () {
			if (window.customElements && customElements.get('model-viewer')) {
				return Promise.resolve();
			}
			if (!this.config.viewer) {
				return Promise.reject(new Error('viewer'));
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
			if (this.modal) return this.modal;

			var modal = document.createElement('div');
			modal.className = 'swiftglb-admin-modal';
			modal.hidden = true;
			modal.innerHTML = ''
				+ '<div class="swiftglb-admin-modal__backdrop"></div>'
				+ '<div class="swiftglb-admin-modal__box">'
				+ '  <div class="swiftglb-admin-modal__head">'
				+ '    <h3>' + (this.config.previewTitle || '3D model preview') + '</h3>'
				+ '    <button type="button" class="swiftglb-admin-modal__close">&times;</button>'
				+ '  </div>'
				+ '  <div class="swiftglb-admin-modal__body">'
				+ '    <div class="swiftglb-admin-modal__loader">' + this.config.uploading + '</div>'
				+ '  </div>'
				+ '</div>';

			var self = this;
			modal.querySelector('.swiftglb-admin-modal__close').addEventListener('click', function () {
				self.close();
			});
			modal.querySelector('.swiftglb-admin-modal__backdrop').addEventListener('click', function () {
				self.close();
			});

			document.body.appendChild(modal);
			this.modal = modal;
			return modal;
		};

		AdminPreview.prototype.open = function (token) {
			if (!token) return;

			var self = this;
			var modal = this.ensureModal();
			var body = modal.querySelector('.swiftglb-admin-modal__body');
			modal.hidden = false;
			body.innerHTML = '';
			var loader = document.createElement('div');
			loader.className = 'swiftglb-admin-modal__loader';
			loader.textContent = this.config.previewLoading || 'Loading the model…';
			body.appendChild(loader);

			var streamUrl = this.config.ajax + '?action=swiftglb_preview&nonce=' + encodeURIComponent(this.config.preview || '') + '&token=' + encodeURIComponent(token);

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
				body.innerHTML = '';
				var failed = document.createElement('div');
				failed.className = 'swiftglb-admin-modal__loader';
				failed.style.color = '#d63638';
				failed.textContent = self.config.previewError || 'The preview could not be loaded.';
				body.appendChild(failed);
			});
		};

		AdminPreview.prototype.close = function () {
			if (this.modal) {
				this.modal.hidden = true;
				var body = this.modal.querySelector('.swiftglb-admin-modal__body');
				if (body) {
					body.innerHTML = '';
				}
			}
		};

		AdminPreview.prototype.isOpen = function () {
			return !!(this.modal && !this.modal.hidden);
		};

		return AdminPreview;
	}());

	// 4. Initialize & Wire Application
	var uploader = new Uploader(config);
	var preview = new Preview(config);

	document.addEventListener('click', function (event) {
		var uploadButton = event.target.closest ? event.target.closest('.swiftglb-upload-btn') : null;
		if (uploadButton) {
			event.preventDefault();
			var wrap = Utils.closestFile(uploadButton);
			var input = wrap && wrap.querySelector('.swiftglb-file-input');
			if (input) {
				input.click();
			}
			return;
		}

		var previewButton = event.target.closest ? event.target.closest('.swiftglb-preview-btn') : null;
		if (previewButton) {
			event.preventDefault();
			var pWrap = Utils.closestFile(previewButton);
			var tokenInput = pWrap && pWrap.querySelector('.swiftglb-token');
			if (tokenInput && tokenInput.value) {
				preview.open(tokenInput.value);
			}
			return;
		}

		var removeButton = event.target.closest ? event.target.closest('.swiftglb-remove-btn') : null;
		if (removeButton) {
			event.preventDefault();
			var fileWrap = Utils.closestFile(removeButton);
			if (fileWrap) {
				uploader.assignFile(fileWrap, {});
				uploader.showError(fileWrap, '');
				var prog = fileWrap.querySelector('.swiftglb-upload-progress');
				if (prog) {
					prog.hidden = true;
				}
			}
			return;
		}

		if (event.target.classList && event.target.classList.contains('swiftglb-shortcode')) {
			event.target.select();
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && preview.isOpen()) {
			preview.close();
		}
	});

	document.addEventListener('change', function (event) {
		if (!event.target.classList || !event.target.classList.contains('swiftglb-file-input')) {
			return;
		}
		var wrap = Utils.closestFile(event.target);
		if (wrap && event.target.files && event.target.files[0]) {
			uploader.upload(wrap, event.target.files[0]);
		}
	});

	document.addEventListener('dragover', function (event) {
		var wrap = Utils.closestFile(event.target);
		if (!wrap) return;
		event.preventDefault();
		wrap.classList.add('is-drag');
	});

	document.addEventListener('dragleave', function (event) {
		var wrap = Utils.closestFile(event.target);
		if (wrap) {
			wrap.classList.remove('is-drag');
		}
	});

	document.addEventListener('drop', function (event) {
		var wrap = Utils.closestFile(event.target);
		if (!wrap) return;
		event.preventDefault();
		wrap.classList.remove('is-drag');
		if (event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0]) {
			uploader.upload(wrap, event.dataTransfer.files[0]);
		}
	});
}(window, document));
