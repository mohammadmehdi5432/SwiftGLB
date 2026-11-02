/**
 * Try3D Admin Utilities
 * 
 * @package Try3D
 */

(function (root) {
	'use strict';

	var AdminUtils = {
		/**
		 * @param {number|string} n Number or string.
		 * @return {string}
		 */
		toPersianDigits: function (n) {
			if (n === null || n === undefined) return '';
			return String(n);
		},

		/**
		 * @param {number} bytes Byte count.
		 * @return {string}
		 */
		formatBytes: function (bytes) {
			if (!bytes || bytes <= 0) {
				return '0 MB';
			}
			if (bytes < 1024 * 1024) {
				return (bytes / 1024).toFixed(0) + ' KB';
			}
			return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
		},

		/**
		 * Find closest .try3d-file container.
		 * 
		 * @param {Element} node Current DOM element.
		 * @return {Element|null} Parent file container or null.
		 */
		closestFile: function (node) {
			return node && node.closest ? node.closest('.try3d-file') : null;
		}
	};

	root.Try3D_AdminUtils = AdminUtils;
}(typeof window !== 'undefined' ? window : this));
