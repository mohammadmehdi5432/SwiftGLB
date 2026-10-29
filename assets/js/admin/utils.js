/**
 * SwiftGLB Admin Utilities
 * 
 * @package SwiftGLB
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
		 * Find closest .swiftglb-file container.
		 * 
		 * @param {Element} node Current DOM element.
		 * @return {Element|null} Parent file container or null.
		 */
		closestFile: function (node) {
			return node && node.closest ? node.closest('.swiftglb-file') : null;
		}
	};

	root.SwiftGLB_AdminUtils = AdminUtils;
}(typeof window !== 'undefined' ? window : this));
