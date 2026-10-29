/**
 * Local QR renderer for desktop AR handoff.
 * Byte mode, error correction M, versions 1–40.
 *
 * @package SwiftGLB
 */
(function (root, factory) {
	var api = factory();
	if (typeof module === 'object' && module.exports) {
		module.exports = api;
	}
	root.Saz3dQr = api;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
	'use strict';

	var CODEWORDS = [0, 26, 44, 70, 100, 134, 172, 196, 242, 292, 346, 404, 466, 532, 581, 655, 733, 815, 901, 991, 1085, 1156, 1258, 1364, 1474, 1588, 1706, 1828, 1921, 2051, 2185, 2323, 2465, 2611, 2761, 2876, 3034, 3196, 3362, 3532, 3706];
	var EC_BLOCKS = [1, 1, 1, 2, 2, 4, 4, 4, 5, 5, 5, 8, 9, 9, 10, 10, 11, 13, 14, 16, 17, 17, 18, 20, 21, 23, 25, 26, 28, 29, 31, 33, 35, 37, 38, 40, 43, 45, 47, 49];
	var EC_WORDS = [10, 16, 26, 36, 48, 64, 72, 88, 110, 130, 150, 176, 198, 216, 240, 280, 308, 338, 364, 416, 442, 476, 504, 560, 588, 644, 700, 728, 784, 812, 868, 924, 980, 1036, 1064, 1120, 1204, 1260, 1316, 1372];
	var EXP = new Uint8Array(512);
	var LOG = new Uint8Array(256);

	(function () {
		var x = 1;
		var i;
		for (i = 0; i < 255; i++) {
			EXP[i] = x;
			LOG[x] = i;
			x <<= 1;
			if (x & 256) {
				x ^= 0x11d;
			}
		}
		for (i = 255; i < 512; i++) {
			EXP[i] = EXP[i - 255];
		}
	}());

	function gfMul(a, b) {
		if (a === 0 || b === 0) {
			return 0;
		}
		return EXP[LOG[a] + LOG[b]];
	}

	function polyMul(p1, p2) {
		var coeff = new Uint8Array(p1.length + p2.length - 1);
		var i;
		var j;
		for (i = 0; i < p1.length; i++) {
			for (j = 0; j < p2.length; j++) {
				coeff[i + j] ^= gfMul(p1[i], p2[j]);
			}
		}
		return coeff;
	}

	function polyMod(dividend, divisor) {
		var result = new Uint8Array(dividend);
		var offset;
		var i;
		var coeff;
		while (result.length - divisor.length >= 0) {
			coeff = result[0];
			for (i = 0; i < divisor.length; i++) {
				result[i] ^= gfMul(divisor[i], coeff);
			}
			offset = 0;
			while (offset < result.length && result[offset] === 0) {
				offset++;
			}
			result = result.slice(offset);
		}
		return result;
	}

	function ecPolynomial(degree) {
		var poly = new Uint8Array([1]);
		var i;
		for (i = 0; i < degree; i++) {
			poly = polyMul(poly, new Uint8Array([1, EXP[i]]));
		}
		return poly;
	}

	function rsEncode(data, degree) {
		var gen = ecPolynomial(degree);
		var padded = new Uint8Array(data.length + degree);
		var remainder;
		var start;
		var buff;
		padded.set(data);
		remainder = polyMod(padded, gen);
		start = degree - remainder.length;
		if (start > 0) {
			buff = new Uint8Array(degree);
			buff.set(remainder, start);
			return buff;
		}
		return remainder;
	}

	function utf8(text) {
		var encoded = unescape(encodeURIComponent(text));
		var out = [];
		var i;
		for (i = 0; i < encoded.length; i++) {
			out.push(encoded.charCodeAt(i) & 255);
		}
		return out;
	}

	function bchDigit(n) {
		var digit = 0;
		while (n !== 0) {
			digit++;
			n >>>= 1;
		}
		return digit;
	}

	function formatBits(mask) {
		var rem = mask << 10;
		while (bchDigit(rem) - 11 >= 0) {
			rem ^= 0x537 << (bchDigit(rem) - 11);
		}
		return ((mask << 10) | rem) ^ 0x5412;
	}

	function versionBits(version) {
		var rem = version << 12;
		while (bchDigit(rem) - 13 >= 0) {
			rem ^= 0x1f25 << (bchDigit(rem) - 13);
		}
		return (version << 12) | rem;
	}

	function chooseVersion(length) {
		var version;
		var countBits;
		var capacity;
		for (version = 1; version <= 40; version++) {
			countBits = version <= 9 ? 8 : 16;
			capacity = Math.floor(((CODEWORDS[version] - EC_WORDS[version - 1]) * 8 - 4 - countBits) / 8);
			if (length <= capacity) {
				return version;
			}
		}
		return 0;
	}

	function dataBytes(version, payload) {
		var total = CODEWORDS[version];
		var ecTotal = EC_WORDS[version - 1];
		var capacityBits = (total - ecTotal) * 8;
		var bits = [];
		var countBits = version <= 9 ? 8 : 16;
		var i;
		var bit;
		var value;

		function put(number, length) {
			for (bit = length - 1; bit >= 0; bit--) {
				bits.push((number >>> bit) & 1);
			}
		}

		put(0x4, 4);
		put(payload.length, countBits);
		for (i = 0; i < payload.length; i++) {
			put(payload[i], 8);
		}
		for (i = 0; i < 4 && bits.length < capacityBits; i++) {
			bits.push(0);
		}
		while (bits.length % 8 !== 0) {
			bits.push(0);
		}
		value = 0xec;
		while (bits.length < capacityBits) {
			put(value, 8);
			value = value === 0xec ? 0x11 : 0xec;
		}

		var bytes = new Uint8Array(bits.length / 8);
		for (i = 0; i < bytes.length; i++) {
			bytes[i] = (bits[i * 8] << 7) | (bits[i * 8 + 1] << 6) | (bits[i * 8 + 2] << 5) | (bits[i * 8 + 3] << 4) | (bits[i * 8 + 4] << 3) | (bits[i * 8 + 5] << 2) | (bits[i * 8 + 6] << 1) | bits[i * 8 + 7];
		}
		return bytes;
	}

	function codewords(version, payload) {
		var raw = dataBytes(version, payload);
		var total = CODEWORDS[version];
		var ecTotal = EC_WORDS[version - 1];
		var dataTotal = total - ecTotal;
		var blocks = EC_BLOCKS[version - 1];
		var group2 = total % blocks;
		var group1 = blocks - group2;
		var data1 = Math.floor(dataTotal / blocks);
		var data2 = data1 + 1;
		var ecCount = Math.floor(total / blocks) - data1;
		var dc = [];
		var ec = [];
		var offset = 0;
		var max = 0;
		var b;
		var size;
		var i;
		var r;
		var out = new Uint8Array(total);
		var index = 0;

		for (b = 0; b < blocks; b++) {
			size = b < group1 ? data1 : data2;
			dc[b] = raw.slice(offset, offset + size);
			ec[b] = rsEncode(dc[b], ecCount);
			offset += size;
			if (size > max) {
				max = size;
			}
		}
		for (i = 0; i < max; i++) {
			for (r = 0; r < blocks; r++) {
				if (i < dc[r].length) {
					out[index++] = dc[r][i];
				}
			}
		}
		for (i = 0; i < ecCount; i++) {
			for (r = 0; r < blocks; r++) {
				out[index++] = ec[r][i];
			}
		}
		return out;
	}

	function alignmentCoords(version) {
		if (version === 1) {
			return [];
		}
		var count = Math.floor(version / 7) + 2;
		var size = version * 4 + 17;
		var step = size === 145 ? 26 : Math.ceil((size - 13) / (2 * count - 2)) * 2;
		var pos = [size - 7];
		var i;
		for (i = 1; i < count - 1; i++) {
			pos[i] = pos[i - 1] - step;
		}
		pos.push(6);
		return pos.reverse();
	}

	function Matrix(size) {
		this.size = size;
		this.data = new Uint8Array(size * size);
		this.reserved = new Uint8Array(size * size);
	}

	Matrix.prototype.set = function (row, col, value, reserved) {
		var index = row * this.size + col;
		this.data[index] = value ? 1 : 0;
		if (reserved) {
			this.reserved[index] = 1;
		}
	};

	Matrix.prototype.get = function (row, col) {
		return this.data[row * this.size + col];
	};

	function buildMatrix(version, words) {
		var size = version * 4 + 17;
		var matrix = new Matrix(size);
		var coords;
		var positions;
		var i;
		var j;
		var r;
		var c;
		var row;
		var col;

		[[0, 0], [size - 7, 0], [0, size - 7]].forEach(function (pos) {
			for (r = -1; r <= 7; r++) {
				if (pos[0] + r < 0 || pos[0] + r >= size) {
					continue;
				}
				for (c = -1; c <= 7; c++) {
					if (pos[1] + c < 0 || pos[1] + c >= size) {
						continue;
					}
					var on = (r >= 0 && r <= 6 && (c === 0 || c === 6)) || (c >= 0 && c <= 6 && (r === 0 || r === 6)) || (r >= 2 && r <= 4 && c >= 2 && c <= 4);
					matrix.set(pos[0] + r, pos[1] + c, on, true);
				}
			}
		});

		for (r = 8; r < size - 8; r++) {
			var timing = r % 2 === 0;
			matrix.set(r, 6, timing, true);
			matrix.set(6, r, timing, true);
		}

		coords = alignmentCoords(version);
		positions = [];
		for (i = 0; i < coords.length; i++) {
			for (j = 0; j < coords.length; j++) {
				if ((i === 0 && j === 0) || (i === 0 && j === coords.length - 1) || (i === coords.length - 1 && j === 0)) {
					continue;
				}
				positions.push([coords[i], coords[j]]);
			}
		}
		positions.forEach(function (pos) {
			for (r = -2; r <= 2; r++) {
				for (c = -2; c <= 2; c++) {
					matrix.set(pos[0] + r, pos[1] + c, r === -2 || r === 2 || c === -2 || c === 2 || (r === 0 && c === 0), true);
				}
			}
		});

		for (i = 0; i < 9; i++) {
			matrix.set(8, i, 0, true);
			matrix.set(i, 8, 0, true);
		}
		for (i = 0; i < 8; i++) {
			matrix.set(size - 1 - i, 8, 0, true);
			matrix.set(8, size - 1 - i, 0, true);
		}
		matrix.set(size - 8, 8, 1, true);

		if (version >= 7) {
			for (i = 0; i < 18; i++) {
				row = Math.floor(i / 3);
				col = (i % 3) + size - 11;
				matrix.set(row, col, 0, true);
				matrix.set(col, row, 0, true);
			}
		}

		var byteIndex = 0;
		var bitIndex = 7;
		var upward = true;
		for (col = size - 1; col > 0; col -= 2) {
			if (col === 6) {
				col--;
			}
			for (i = 0; i < size; i++) {
				row = upward ? size - 1 - i : i;
				for (c = 0; c < 2; c++) {
					var xx = col - c;
					if (matrix.reserved[row * size + xx]) {
						continue;
					}
					var dark = false;
					if (byteIndex < words.length) {
						dark = ((words[byteIndex] >>> bitIndex) & 1) === 1;
					}
					matrix.set(row, xx, dark, false);
					bitIndex--;
					if (bitIndex < 0) {
						byteIndex++;
						bitIndex = 7;
					}
				}
			}
			upward = !upward;
		}

		return matrix;
	}

	function maskAt(pattern, row, col) {
		switch (pattern) {
			case 0: return (row + col) % 2 === 0;
			case 1: return row % 2 === 0;
			case 2: return col % 3 === 0;
			case 3: return (row + col) % 3 === 0;
			case 4: return (Math.floor(row / 2) + Math.floor(col / 3)) % 2 === 0;
			case 5: return ((row * col) % 2) + ((row * col) % 3) === 0;
			case 6: return (((row * col) % 2) + ((row * col) % 3)) % 2 === 0;
			default: return (((row * col) % 3) + ((row + col) % 2)) % 2 === 0;
		}
	}

	function applyMask(matrix, pattern) {
		var size = matrix.size;
		var row;
		var col;
		for (row = 0; row < size; row++) {
			for (col = 0; col < size; col++) {
				if (matrix.reserved[row * size + col]) {
					continue;
				}
				if (maskAt(pattern, row, col)) {
					matrix.data[row * size + col] ^= 1;
				}
			}
		}
	}

	function writeFormat(matrix, mask) {
		var size = matrix.size;
		var bits = formatBits(mask);
		var i;
		var mod;
		for (i = 0; i < 15; i++) {
			mod = ((bits >> i) & 1) === 1;
			if (i < 6) {
				matrix.set(i, 8, mod, true);
			} else if (i < 8) {
				matrix.set(i + 1, 8, mod, true);
			} else {
				matrix.set(size - 15 + i, 8, mod, true);
			}
			if (i < 8) {
				matrix.set(8, size - i - 1, mod, true);
			} else if (i < 9) {
				matrix.set(8, 15 - i - 1 + 1, mod, true);
			} else {
				matrix.set(8, 15 - i - 1, mod, true);
			}
		}
		matrix.set(size - 8, 8, 1, true);
	}

	function writeVersion(matrix, version) {
		if (version < 7) {
			return;
		}
		var size = matrix.size;
		var bits = versionBits(version);
		var i;
		var row;
		var col;
		var mod;
		for (i = 0; i < 18; i++) {
			row = Math.floor(i / 3);
			col = (i % 3) + size - 11;
			mod = ((bits >> i) & 1) === 1;
			matrix.set(row, col, mod, true);
			matrix.set(col, row, mod, true);
		}
	}

	function penalty(matrix) {
		var size = matrix.size;
		var points = 0;
		var row;
		var col;
		var sameCol;
		var sameRow;
		var lastCol;
		var lastRow;
		var module;
		var bitsCol;
		var bitsRow;
		var dark = 0;
		var total = size * size;

		for (row = 0; row < size; row++) {
			sameCol = 0;
			sameRow = 0;
			lastCol = null;
			lastRow = null;
			for (col = 0; col < size; col++) {
				module = matrix.get(row, col);
				if (module === lastCol) {
					sameCol++;
				} else {
					if (sameCol >= 5) {
						points += 3 + (sameCol - 5);
					}
					lastCol = module;
					sameCol = 1;
				}
				module = matrix.get(col, row);
				if (module === lastRow) {
					sameRow++;
				} else {
					if (sameRow >= 5) {
						points += 3 + (sameRow - 5);
					}
					lastRow = module;
					sameRow = 1;
				}
			}
			if (sameCol >= 5) {
				points += 3 + (sameCol - 5);
			}
			if (sameRow >= 5) {
				points += 3 + (sameRow - 5);
			}
		}

		for (row = 0; row < size - 1; row++) {
			for (col = 0; col < size - 1; col++) {
				var sum = matrix.get(row, col) + matrix.get(row, col + 1) + matrix.get(row + 1, col) + matrix.get(row + 1, col + 1);
				if (sum === 0 || sum === 4) {
					points += 3;
				}
			}
		}

		for (row = 0; row < size; row++) {
			bitsCol = 0;
			bitsRow = 0;
			for (col = 0; col < size; col++) {
				bitsCol = ((bitsCol << 1) & 0x7ff) | matrix.get(row, col);
				if (col >= 10 && (bitsCol === 0x5d0 || bitsCol === 0x05d)) {
					points += 40;
				}
				bitsRow = ((bitsRow << 1) & 0x7ff) | matrix.get(col, row);
				if (col >= 10 && (bitsRow === 0x5d0 || bitsRow === 0x05d)) {
					points += 40;
				}
			}
		}

		for (row = 0; row < size; row++) {
			for (col = 0; col < size; col++) {
				dark += matrix.get(row, col);
			}
		}
		points += Math.abs(Math.ceil((dark * 100 / total) / 5) - 10) * 10;
		return points;
	}

	function modules(text) {
		if (!text) {
			return null;
		}
		var payload = utf8(String(text));
		var version = chooseVersion(payload.length);
		if (!version) {
			return null;
		}
		var matrix = buildMatrix(version, codewords(version, payload));
		var best = 0;
		var bestScore = Infinity;
		var pattern;
		var score;
		writeVersion(matrix, version);
		for (pattern = 0; pattern < 8; pattern++) {
			writeFormat(matrix, pattern);
			applyMask(matrix, pattern);
			score = penalty(matrix);
			applyMask(matrix, pattern);
			if (score < bestScore) {
				bestScore = score;
				best = pattern;
			}
		}
		writeFormat(matrix, best);
		applyMask(matrix, best);

		var grid = [];
		var row;
		var col;
		for (row = 0; row < matrix.size; row++) {
			grid[row] = [];
			for (col = 0; col < matrix.size; col++) {
				grid[row][col] = matrix.get(row, col);
			}
		}
		return grid;
	}

	function draw(canvas, text) {
		var grid = modules(text);
		var context;
		var count;
		var scale;
		var quiet;
		var size;
		var row;
		var col;
		if (!grid || !canvas || typeof canvas.getContext !== 'function') {
			return false;
		}
		context = canvas.getContext('2d');
		if (!context) {
			return false;
		}
		count = grid.length;
		scale = Math.max(2, Math.floor(220 / (count + 8)));
		quiet = 4 * scale;
		size = count * scale + quiet * 2;
		canvas.width = size;
		canvas.height = size;
		context.fillStyle = '#ffffff';
		context.fillRect(0, 0, size, size);
		context.fillStyle = '#111111';
		for (row = 0; row < count; row++) {
			for (col = 0; col < count; col++) {
				if (grid[row][col]) {
					context.fillRect(quiet + col * scale, quiet + row * scale, scale, scale);
				}
			}
		}
		return true;
	}

	return {
		modules: modules,
		draw: draw
	};
}));
