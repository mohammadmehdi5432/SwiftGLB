/**
 * SwiftGLB Admin Event Binder
 * Single Responsibility: Coordinating user interactions, drag & drop, and DOM event delegation.
 * 
 * @package SwiftGLB
 */

(function (root) {
	'use strict';

	var utils = root.SwiftGLB_AdminUtils;

	/**
	 * @param {SwiftGLB_AdminUploader} uploader Uploader instance.
	 * @param {SwiftGLB_AdminPreview}  preview  Preview controller instance.
	 * @constructor
	 */
	function AdminEvents(uploader, preview) {
		this.uploader = uploader;
		this.preview = preview;
	}

	AdminEvents.prototype.bind = function () {
		var self = this;

		document.addEventListener('click', function (event) {
			var uploadButton = event.target.closest ? event.target.closest('.swiftglb-upload-btn') : null;
			if (uploadButton) {
				event.preventDefault();
				var wrap = utils.closestFile(uploadButton);
				var input = wrap && wrap.querySelector('.swiftglb-file-input');
				if (input) {
					input.click();
				}
				return;
			}

			var previewButton = event.target.closest ? event.target.closest('.swiftglb-preview-btn') : null;
			if (previewButton) {
				event.preventDefault();
				var pWrap = utils.closestFile(previewButton);
				var tokenInput = pWrap && pWrap.querySelector('.swiftglb-token');
				if (tokenInput && tokenInput.value) {
					self.preview.open(tokenInput.value);
				}
				return;
			}

			var removeButton = event.target.closest ? event.target.closest('.swiftglb-remove-btn') : null;
			if (removeButton) {
				event.preventDefault();
				var fileWrap = utils.closestFile(removeButton);
				if (fileWrap) {
					self.uploader.assignFile(fileWrap, {});
					self.uploader.showError(fileWrap, '');
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
			if (event.key === 'Escape' && self.preview.isOpen()) {
				self.preview.close();
			}
		});

		document.addEventListener('change', function (event) {
			if (!event.target.classList || !event.target.classList.contains('swiftglb-file-input')) {
				return;
			}
			var wrap = utils.closestFile(event.target);
			if (wrap && event.target.files && event.target.files[0]) {
				self.uploader.upload(wrap, event.target.files[0]);
			}
		});

		document.addEventListener('dragover', function (event) {
			var wrap = utils.closestFile(event.target);
			if (!wrap) return;
			event.preventDefault();
			wrap.classList.add('is-drag');
		});

		document.addEventListener('dragleave', function (event) {
			var wrap = utils.closestFile(event.target);
			if (wrap) {
				wrap.classList.remove('is-drag');
			}
		});

		document.addEventListener('drop', function (event) {
			var wrap = utils.closestFile(event.target);
			if (!wrap) return;
			event.preventDefault();
			wrap.classList.remove('is-drag');
			if (event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0]) {
				self.uploader.upload(wrap, event.dataTransfer.files[0]);
			}
		});
	};

	root.SwiftGLB_AdminEvents = AdminEvents;
}(typeof window !== 'undefined' ? window : this));
