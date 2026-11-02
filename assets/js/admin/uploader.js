/**
 * Try3D Admin Uploader
 * Single Responsibility: Handling chunked progress file uploads and field assignments.
 * 
 * @package Try3D
 */

(function (root) {
	'use strict';

	var utils = root.Try3D_AdminUtils;

	/**
	 * @param {Object} config Admin configuration from wp_localize_script.
	 * @constructor
	 */
	function AdminUploader(config) {
		this.config = config || {};
	}

	AdminUploader.prototype.setBusy = function (wrap, busy) {
		var button = wrap.querySelector('.try3d-upload-btn');
		if (!button) return;
		button.disabled = busy;
		button.textContent = busy ? this.config.uploading : this.config.upload;
	};

	AdminUploader.prototype.showError = function (wrap, message) {
		var slot = wrap.querySelector('.try3d-file-error');
		if (slot) {
			slot.textContent = message || '';
		}
	};

	AdminUploader.prototype.getProgressElements = function (wrap) {
		var prog = wrap.querySelector('.try3d-upload-progress');
		if (!prog) {
			prog = document.createElement('div');
			prog.className = 'try3d-upload-progress';
			prog.innerHTML = ''
				+ '<div class="try3d-upload-bar"><div class="try3d-upload-fill"></div></div>'
				+ '<div class="try3d-upload-meta">'
				+ '  <span class="try3d-upload-status">Uploading the file…</span>'
				+ '  <div class="try3d-upload-numbers">'
				+ '    <span class="try3d-upload-bytes"></span>'
				+ '    <span class="try3d-upload-percent">0%</span>'
				+ '  </div>'
				+ '</div>';
			wrap.appendChild(prog);
		}
		return {
			wrap: prog,
			fill: prog.querySelector('.try3d-upload-fill'),
			status: prog.querySelector('.try3d-upload-status'),
			bytes: prog.querySelector('.try3d-upload-bytes'),
			percent: prog.querySelector('.try3d-upload-percent')
		};
	};

	AdminUploader.prototype.assignFile = function (wrap, payload) {
		var token = wrap.querySelector('.try3d-token');
		var filename = wrap.querySelector('.try3d-filename');
		var label = wrap.querySelector('.try3d-file-name');
		var remove = wrap.querySelector('.try3d-remove-btn');
		var preview = wrap.querySelector('.try3d-preview-btn');

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
		var input = wrap.querySelector('.try3d-file-input');
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
		prog.status.textContent = 'Sending the file to the server…';
		prog.bytes.textContent = '0 MB of ' + utils.formatBytes(file.size);
		prog.percent.textContent = '0%';

		var xhr = new XMLHttpRequest();
		xhr.open('POST', self.config.ajax, true);
		xhr.withCredentials = true;

		xhr.upload.addEventListener('progress', function (e) {
			if (e.lengthComputable && e.total > 0) {
				var pct = Math.min(100, Math.round((e.loaded / e.total) * 100));
				prog.fill.style.width = pct + '%';
				prog.percent.textContent = utils.toPersianDigits(pct) + '%';
				prog.bytes.textContent = utils.formatBytes(e.loaded) + ' of ' + utils.formatBytes(e.total);

				if (pct >= 100) {
					prog.status.textContent = 'Protecting the file on the server…';
					prog.fill.classList.add('is-processing');
				}
			}
		});

		xhr.addEventListener('load', function () {
			self.setBusy(wrap, false);
			var input = wrap.querySelector('.try3d-file-input');
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
		body.append('action', 'try3d_upload');
		body.append('nonce', self.config.nonce);
		body.append('file', file);
		xhr.send(body);
	};

	root.Try3D_AdminUploader = AdminUploader;
}(typeof window !== 'undefined' ? window : this));
