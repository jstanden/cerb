/*
 * CerbUI.QrCode — a self-contained QR Code generator + SVG renderer (plain JS, no jQuery). Replaces the legacy
 * jquery.qrcode plugin. The generator is a clean-room implementation of ISO/IEC 18004 (byte mode): version
 * auto-selection, Reed–Solomon error correction over GF(256), the eight data masks with penalty scoring, and
 * finder/timing/alignment/format placement. Output is one crisp <svg> that scales to any size.
 *
 * QR codes are read by phone cameras, so they render dark-on-white regardless of page theme (do NOT tint with
 * currentColor). Default error-correction level is H (30%) — the density the MFA setup screens have always used.
 *
 * Markup: an empty container the SVG is rendered into.
 *   <div id="qrcode"></div>
 *
 * Usage:
 *   new CerbUI.QrCode(el, { text: 'otpauth://totp/…', size: 192 });
 *   qr.setText('https://cerb.ai');           // re-render
 *   const svg = CerbUI.QrCode.create({ text: 'hi', size: 128 }); // detached <svg>, no container
 *
 * Options: { text, size=192, margin=4 (quiet-zone modules), correctLevel='L'|'M'|'Q'|'H' (default 'H'),
 *            foreground='#000000', background='#ffffff' }.
 */

// --- QR model (private; ISO/IEC 18004) -----------------------------------------------------------------------

// Error-correction levels. `ord` indexes the codeword tables below; `fmt` is the 2-bit format indicator.
const _QR_ECL = {
	L: { ord: 0, fmt: 1 },
	M: { ord: 1, fmt: 0 },
	Q: { ord: 2, fmt: 3 },
	H: { ord: 2 + 1, fmt: 2 },
};

// EC codewords per block, indexed [ecl.ord][version] (version 1..40; index 0 is unused padding).
const _QR_ECC_CODEWORDS_PER_BLOCK = [
	[-1, 7, 10, 15, 20, 26, 18, 20, 24, 30, 18, 20, 24, 26, 30, 22, 24, 28, 30, 28, 28, 28, 28, 30, 30, 26, 28, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30], // L
	[-1, 10, 16, 26, 18, 24, 16, 18, 22, 22, 26, 30, 22, 22, 24, 24, 28, 28, 26, 26, 26, 26, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28], // M
	[-1, 13, 22, 18, 26, 18, 24, 18, 22, 20, 24, 28, 26, 24, 20, 30, 24, 28, 28, 26, 30, 28, 30, 30, 30, 30, 28, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30], // Q
	[-1, 17, 28, 22, 16, 22, 28, 26, 26, 24, 28, 24, 28, 22, 24, 24, 30, 28, 28, 26, 28, 30, 24, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30], // H
];

// Number of error-correction blocks, indexed [ecl.ord][version].
const _QR_NUM_EC_BLOCKS = [
	[-1, 1, 1, 1, 1, 1, 2, 2, 2, 2, 4, 4, 4, 4, 4, 6, 6, 6, 6, 7, 8, 8, 9, 9, 10, 12, 12, 12, 13, 14, 15, 16, 17, 18, 19, 19, 20, 21, 22, 24, 25], // L
	[-1, 1, 1, 1, 2, 2, 4, 4, 4, 5, 5, 5, 8, 9, 9, 10, 10, 11, 13, 14, 16, 17, 17, 18, 20, 21, 23, 25, 26, 28, 29, 31, 33, 35, 37, 38, 40, 43, 45, 47, 49], // M
	[-1, 1, 1, 2, 2, 4, 4, 6, 6, 8, 8, 8, 10, 12, 16, 12, 17, 16, 18, 21, 20, 23, 23, 25, 27, 29, 34, 34, 35, 38, 40, 43, 45, 48, 51, 53, 56, 59, 62, 65, 68], // Q
	[-1, 1, 1, 2, 4, 4, 4, 5, 6, 8, 8, 11, 11, 16, 16, 18, 16, 19, 21, 25, 25, 25, 34, 30, 32, 35, 37, 40, 42, 45, 48, 51, 54, 57, 60, 63, 66, 70, 74, 77, 81], // H
];

// GF(256) exp/log tables (primitive polynomial x^8 + x^4 + x^3 + x^2 + 1 = 0x11D) for Reed–Solomon arithmetic.
const _QR_GF_EXP = new Uint8Array(256);
const _QR_GF_LOG = new Uint8Array(256);
(function() {
	let x = 1;
	for(let i = 0; i < 255; i++) {
		_QR_GF_EXP[i] = x;
		_QR_GF_LOG[x] = i;
		x <<= 1;
		if(x & 0x100) x ^= 0x11D;
	}
	_QR_GF_EXP[255] = _QR_GF_EXP[0]; // wrap so exp[log[a]+log[b]] never indexes past the end
})();

function _qrGfMul(a, b) {
	if(a === 0 || b === 0) return 0;
	return _QR_GF_EXP[(_QR_GF_LOG[a] + _QR_GF_LOG[b]) % 255];
}

// Reed–Solomon generator polynomial of the given degree: the product ∏(x - α^i) for i in 0..degree-1.
// `poly` is accumulated low-to-high (poly[0] = constant term, poly[degree] = leading 1); we return it
// reversed to high-to-low (leading 1 first), the order _qrRsRemainder consumes.
function _qrRsGenerator(degree) {
	let poly = [1];
	for(let i = 0; i < degree; i++) {
		// Multiply poly by (x - GF_EXP[i])
		const next = new Array(poly.length + 1).fill(0);
		for(let j = 0; j < poly.length; j++) {
			next[j] ^= _qrGfMul(poly[j], _QR_GF_EXP[i]);
			next[j + 1] ^= poly[j];
		}
		poly = next;
	}
	return poly.reverse();
}

// The `degree` EC codewords for one data block. `generator` is the monic RS generator polynomial
// (length degree+1, leading coefficient 1); the remainder uses only its `degree` low coefficients.
function _qrRsRemainder(data, generator) {
	const degree = generator.length - 1;
	const result = new Array(degree).fill(0);
	for(let i = 0; i < data.length; i++) {
		const factor = data[i] ^ result[0];
		result.shift();
		result.push(0);
		for(let j = 0; j < degree; j++)
			result[j] ^= _qrGfMul(generator[j + 1], factor);
	}
	return result;
}

// Total data modules (bits) available in a version, before dividing into codewords.
function _qrNumRawDataModules(ver) {
	let result = (16 * ver + 128) * ver + 64;
	if(ver >= 2) {
		const numAlign = Math.floor(ver / 7) + 2;
		result -= (25 * numAlign - 10) * numAlign - 55;
		if(ver >= 7) result -= 36;
	}
	return result;
}

// Number of 8-bit data codewords (not counting EC codewords) for a version + EC level.
function _qrNumDataCodewords(ver, ecl) {
	const totalCodewords = Math.floor(_qrNumRawDataModules(ver) / 8);
	const numBlocks = _QR_NUM_EC_BLOCKS[ecl.ord][ver];
	const eccPerBlock = _QR_ECC_CODEWORDS_PER_BLOCK[ecl.ord][ver];
	return totalCodewords - eccPerBlock * numBlocks;
}

// Byte-mode character-count indicator width in bits, by version tier.
function _qrCharCountBits(ver) {
	return (ver <= 9) ? 8 : 16;
}

// Center coordinates of alignment patterns for a version (empty for version 1).
function _qrAlignmentPositions(ver) {
	if(ver === 1) return [];
	const size = ver * 4 + 17;
	const numAlign = Math.floor(ver / 7) + 2;
	const step = (ver === 32) ? 26 : Math.ceil((size - 13) / (numAlign * 2 - 2)) * 2;
	const result = [6];
	for(let pos = size - 7; result.length < numAlign; pos -= step)
		result.splice(1, 0, pos);
	return result;
}

// A byte string -> array of codeword integers, terminator + pad bytes filling the version's data capacity.
function _qrEncodeData(bytes, ver, ecl) {
	const numDataCodewords = _qrNumDataCodewords(ver, ecl);
	const capacityBits = numDataCodewords * 8;

	// Bit buffer as an array of 0/1
	const bits = [];
	const appendBits = (val, len) => {
		for(let i = len - 1; i >= 0; i--) bits.push((val >>> i) & 1);
	};

	appendBits(0x4, 4);                          // byte-mode indicator
	appendBits(bytes.length, _qrCharCountBits(ver)); // character count
	for(let i = 0; i < bytes.length; i++) appendBits(bytes[i], 8);

	// Terminator (up to 4 zero bits), then pad to a byte boundary
	appendBits(0, Math.min(4, capacityBits - bits.length));
	while(bits.length % 8 !== 0) bits.push(0);

	// Pack bits into codewords
	const codewords = [];
	for(let i = 0; i < bits.length; i += 8) {
		let byte = 0;
		for(let j = 0; j < 8; j++) byte = (byte << 1) | bits[i + j];
		codewords.push(byte);
	}
	// Fill remaining capacity with the two standard pad bytes, alternating
	for(let pad = 0xEC; codewords.length < numDataCodewords; pad ^= 0xEC ^ 0x11)
		codewords.push(pad);

	return codewords;
}

// Split data codewords into blocks, append per-block EC codewords, and interleave into the final byte stream.
function _qrInterleaveBlocks(dataCodewords, ver, ecl) {
	const numBlocks = _QR_NUM_EC_BLOCKS[ecl.ord][ver];
	const eccPerBlock = _QR_ECC_CODEWORDS_PER_BLOCK[ecl.ord][ver];
	const totalCodewords = Math.floor(_qrNumRawDataModules(ver) / 8);
	const numShortBlocks = numBlocks - (totalCodewords % numBlocks);
	const shortBlockDataLen = Math.floor(totalCodewords / numBlocks) - eccPerBlock;

	const generator = _qrRsGenerator(eccPerBlock);
	const dataBlocks = [];
	const eccBlocks = [];
	let offset = 0;
	for(let b = 0; b < numBlocks; b++) {
		const dataLen = shortBlockDataLen + (b < numShortBlocks ? 0 : 1);
		const block = dataCodewords.slice(offset, offset + dataLen);
		offset += dataLen;
		dataBlocks.push(block);
		eccBlocks.push(_qrRsRemainder(block, generator));
	}

	// Interleave data codewords column-by-column (short blocks skip their absent last data codeword),
	// then all EC codewords column-by-column.
	const result = [];
	const maxDataLen = shortBlockDataLen + 1;
	for(let i = 0; i < maxDataLen; i++)
		for(let b = 0; b < numBlocks; b++)
			if(i < dataBlocks[b].length) result.push(dataBlocks[b][i]);
	for(let i = 0; i < eccPerBlock; i++)
		for(let b = 0; b < numBlocks; b++)
			result.push(eccBlocks[b][i]);

	return result;
}

// A QR matrix under construction: modules[row][col] (boolean dark) + a parallel isFunction mask.
function _qrNewMatrix(size) {
	const modules = [];
	const isFunction = [];
	for(let r = 0; r < size; r++) {
		modules.push(new Array(size).fill(false));
		isFunction.push(new Array(size).fill(false));
	}
	return { size, modules, isFunction };
}

function _qrSetFunctionModule(m, x, y, dark) {
	if(x < 0 || y < 0 || x >= m.size || y >= m.size) return;
	m.modules[y][x] = dark;
	m.isFunction[y][x] = true;
}

function _qrDrawFinderPattern(m, cx, cy) {
	for(let dy = -4; dy <= 4; dy++) {
		for(let dx = -4; dx <= 4; dx++) {
			const dist = Math.max(Math.abs(dx), Math.abs(dy)); // Chebyshev distance from center
			const x = cx + dx, y = cy + dy;
			if(x >= 0 && x < m.size && y >= 0 && y < m.size)
				_qrSetFunctionModule(m, x, y, dist !== 2 && dist !== 4);
		}
	}
}

function _qrDrawAlignmentPattern(m, cx, cy) {
	for(let dy = -2; dy <= 2; dy++)
		for(let dx = -2; dx <= 2; dx++)
			_qrSetFunctionModule(m, cx + dx, cy + dy, Math.max(Math.abs(dx), Math.abs(dy)) !== 1);
}

function _qrDrawFunctionPatterns(m, ver) {
	const size = m.size;

	// Timing patterns (row/col 6)
	for(let i = 0; i < size; i++) {
		_qrSetFunctionModule(m, 6, i, i % 2 === 0);
		_qrSetFunctionModule(m, i, 6, i % 2 === 0);
	}

	// Finder patterns + their separators (the separators are the dist===4 ring drawn light)
	_qrDrawFinderPattern(m, 3, 3);
	_qrDrawFinderPattern(m, size - 4, 3);
	_qrDrawFinderPattern(m, 3, size - 4);

	// Alignment patterns (skip where they'd collide with a finder)
	const positions = _qrAlignmentPositions(ver);
	const n = positions.length;
	for(let i = 0; i < n; i++) {
		for(let j = 0; j < n; j++) {
			const skipCorner = (i === 0 && j === 0) || (i === 0 && j === n - 1) || (i === n - 1 && j === 0);
			if(!skipCorner) _qrDrawAlignmentPattern(m, positions[i], positions[j]);
		}
	}

	// Dark module (the format-info modules are reserved by the provisional _qrDrawFormatBits pass, which runs
	// before data placement and marks each as a function module — so no separate reserve step is needed here).
	_qrSetFunctionModule(m, 8, size - 8, true);

	if(ver >= 7) {
		// Version information: 18 BCH bits in two 6x3 blocks near the top-right and bottom-left finders
		let rem = ver;
		for(let i = 0; i < 12; i++) rem = (rem << 1) ^ ((rem >>> 11) * 0x1F25);
		const bits = (ver << 12) | rem;
		for(let i = 0; i < 18; i++) {
			const bit = ((bits >>> i) & 1) === 1;
			const a = size - 11 + (i % 3);
			const b = Math.floor(i / 3);
			_qrSetFunctionModule(m, a, b, bit);
			_qrSetFunctionModule(m, b, a, bit);
		}
	}
}

// Place the interleaved codeword bit stream in the zigzag data region (skipping function modules).
function _qrDrawCodewords(m, data) {
	const size = m.size;
	let bitIndex = 0;
	const totalBits = data.length * 8;

	for(let right = size - 1; right >= 1; right -= 2) {
		if(right === 6) right = 5; // skip the vertical timing column
		for(let vert = 0; vert < size; vert++) {
			for(let c = 0; c < 2; c++) {
				const x = right - c;
				const upward = ((right + 1) & 2) === 0;
				const y = upward ? (size - 1 - vert) : vert;
				if(!m.isFunction[y][x] && bitIndex < totalBits) {
					const dark = ((data[bitIndex >>> 3] >>> (7 - (bitIndex & 7))) & 1) === 1;
					m.modules[y][x] = dark;
					bitIndex++;
				}
			}
		}
	}
}

// The eight data-mask conditions.
function _qrMaskCondition(mask, x, y) {
	switch(mask) {
		case 0: return (x + y) % 2 === 0;
		case 1: return y % 2 === 0;
		case 2: return x % 3 === 0;
		case 3: return (x + y) % 3 === 0;
		case 4: return (Math.floor(y / 2) + Math.floor(x / 3)) % 2 === 0;
		case 5: return (x * y) % 2 + (x * y) % 3 === 0;
		case 6: return ((x * y) % 2 + (x * y) % 3) % 2 === 0;
		case 7: return ((x + y) % 2 + (x * y) % 3) % 2 === 0;
	}
	return false;
}

function _qrApplyMask(m, mask) {
	for(let y = 0; y < m.size; y++)
		for(let x = 0; x < m.size; x++)
			if(!m.isFunction[y][x] && _qrMaskCondition(mask, x, y))
				m.modules[y][x] = !m.modules[y][x];
}

// Write the 15-bit format information (EC level + mask) with its BCH error correction.
function _qrDrawFormatBits(m, ecl, mask) {
	const data = (ecl.fmt << 3) | mask;
	let rem = data;
	for(let i = 0; i < 10; i++) rem = (rem << 1) ^ ((rem >>> 9) * 0x537);
	const bits = ((data << 10) | rem) ^ 0x5412;
	const size = m.size;

	// First copy (around the top-left finder)
	for(let i = 0; i <= 5; i++) _qrSetFunctionModule(m, 8, i, ((bits >>> i) & 1) === 1);
	_qrSetFunctionModule(m, 8, 7, ((bits >>> 6) & 1) === 1);
	_qrSetFunctionModule(m, 8, 8, ((bits >>> 7) & 1) === 1);
	_qrSetFunctionModule(m, 7, 8, ((bits >>> 8) & 1) === 1);
	for(let i = 9; i < 15; i++) _qrSetFunctionModule(m, 14 - i, 8, ((bits >>> i) & 1) === 1);

	// Second copy (split between the other two finders)
	for(let i = 0; i < 8; i++) _qrSetFunctionModule(m, size - 1 - i, 8, ((bits >>> i) & 1) === 1);
	for(let i = 8; i < 15; i++) _qrSetFunctionModule(m, 8, size - 15 + i, ((bits >>> i) & 1) === 1);
}

// Penalty score of the current (masked) matrix — lower is better. Implements the four ISO rules.
function _qrPenaltyScore(m) {
	const size = m.size;
	const mod = m.modules;
	let score = 0;

	// Rule 1: runs of >=5 same-color modules in each row and column
	for(let y = 0; y < size; y++) {
		let runColor = false, runLen = 0;
		for(let x = 0; x < size; x++) {
			if(mod[y][x] === runColor) {
				runLen++;
				if(runLen === 5) score += 3;
				else if(runLen > 5) score += 1;
			} else { runColor = mod[y][x]; runLen = 1; }
		}
	}
	for(let x = 0; x < size; x++) {
		let runColor = false, runLen = 0;
		for(let y = 0; y < size; y++) {
			if(mod[y][x] === runColor) {
				runLen++;
				if(runLen === 5) score += 3;
				else if(runLen > 5) score += 1;
			} else { runColor = mod[y][x]; runLen = 1; }
		}
	}

	// Rule 2: 2x2 blocks of the same color
	for(let y = 0; y < size - 1; y++)
		for(let x = 0; x < size - 1; x++)
			if(mod[y][x] === mod[y][x + 1] && mod[y][x] === mod[y + 1][x] && mod[y][x] === mod[y + 1][x + 1])
				score += 3;

	// Rule 3: finder-like 1:1:3:1:1 patterns (dark:light run) in rows and columns
	const hasFinder = (line, i) => {
		// pattern 1011101 preceded or followed by 0000 (4 light modules)
		return line[i] && !line[i + 1] && line[i + 2] && line[i + 3] && line[i + 4] && !line[i + 5] && line[i + 6];
	};
	for(let y = 0; y < size; y++) {
		const row = mod[y];
		for(let x = 0; x <= size - 7; x++) {
			if(hasFinder(row, x)) {
				if(x >= 4 && !row[x - 1] && !row[x - 2] && !row[x - 3] && !row[x - 4]) score += 40;
				if(x + 10 <= size && !row[x + 7] && !row[x + 8] && !row[x + 9] && !row[x + 10]) score += 40;
			}
		}
	}
	for(let x = 0; x < size; x++) {
		const col = [];
		for(let y = 0; y < size; y++) col.push(mod[y][x]);
		for(let y = 0; y <= size - 7; y++) {
			if(hasFinder(col, y)) {
				if(y >= 4 && !col[y - 1] && !col[y - 2] && !col[y - 3] && !col[y - 4]) score += 40;
				if(y + 10 <= size && !col[y + 7] && !col[y + 8] && !col[y + 9] && !col[y + 10]) score += 40;
			}
		}
	}

	// Rule 4: proportion of dark modules deviating from 50%
	let dark = 0;
	for(let y = 0; y < size; y++)
		for(let x = 0; x < size; x++)
			if(mod[y][x]) dark++;
	const total = size * size;
	const k = Math.floor((Math.abs(dark * 20 - total * 10) + total - 1) / total) - 1; // ceil(|pct-50|/5)-...
	score += k * 10;

	return score;
}

// Full pipeline: text -> chosen version -> matrix with best mask. Returns { size, modules }.
function _qrEncode(text, ecl) {
	const bytes = _qrToUtf8Bytes(text);

	// Pick the smallest version that fits (char-count width may change between tiers)
	let ver = 0;
	for(let v = 1; v <= 40; v++) {
		const capacityBits = _qrNumDataCodewords(v, ecl) * 8;
		const neededBits = 4 + _qrCharCountBits(v) + bytes.length * 8;
		if(neededBits <= capacityBits) { ver = v; break; }
	}
	if(ver === 0) throw new Error('CerbUI.QrCode: data too long to encode');

	const dataCodewords = _qrEncodeData(bytes, ver, ecl);
	const allCodewords = _qrInterleaveBlocks(dataCodewords, ver, ecl);

	const size = ver * 4 + 17;

	// Try all 8 masks; keep the lowest-penalty result
	let best = null, bestScore = Infinity;
	for(let mask = 0; mask < 8; mask++) {
		const m = _qrNewMatrix(size);
		_qrDrawFunctionPatterns(m, ver);
		_qrDrawFormatBits(m, ecl, mask); // provisional (so penalty sees finished format bits)
		_qrDrawCodewords(m, allCodewords);
		_qrApplyMask(m, mask);
		_qrDrawFormatBits(m, ecl, mask);
		const score = _qrPenaltyScore(m);
		if(score < bestScore) { bestScore = score; best = m; }
	}
	return { size: best.size, modules: best.modules };
}

// UTF-8 encode (otpauth URIs are ASCII, but be correct for any text).
function _qrToUtf8Bytes(str) {
	if(typeof TextEncoder !== 'undefined') return Array.from(new TextEncoder().encode(str));
	const out = [];
	for(let i = 0; i < str.length; i++) {
		let c = str.charCodeAt(i);
		if(c < 0x80) out.push(c);
		else if(c < 0x800) { out.push(0xC0 | (c >> 6), 0x80 | (c & 0x3F)); }
		else { out.push(0xE0 | (c >> 12), 0x80 | ((c >> 6) & 0x3F), 0x80 | (c & 0x3F)); }
	}
	return out;
}

// --- Component ------------------------------------------------------------------------------------------------

const _QR_SVG_NS = 'http://www.w3.org/2000/svg';

CerbUI.QrCode = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.QrCode._instances.get(el); }

	// Build a detached <svg> for the given options (no container). Also used internally by instances.
	static create(options = {}) {
		const size = options.size || 192;
		const margin = (options.margin != null) ? options.margin : 4;
		const foreground = options.foreground || '#000000';
		const background = options.background || '#ffffff';
		const level = _QR_ECL[(options.correctLevel || 'H').toUpperCase()] || _QR_ECL.H;

		const qr = _qrEncode(String(options.text != null ? options.text : ''), level);
		const dim = qr.size + margin * 2;

		// One <path> of unit squares for all dark modules
		let d = '';
		for(let y = 0; y < qr.size; y++)
			for(let x = 0; x < qr.size; x++)
				if(qr.modules[y][x]) d += 'M' + (x + margin) + ',' + (y + margin) + 'h1v1h-1z';

		const svg = document.createElementNS(_QR_SVG_NS, 'svg');
		svg.setAttribute('xmlns', _QR_SVG_NS);
		svg.setAttribute('width', size);
		svg.setAttribute('height', size);
		svg.setAttribute('viewBox', '0 0 ' + dim + ' ' + dim);
		svg.setAttribute('shape-rendering', 'crispEdges');
		svg.setAttribute('role', 'img');

		const bg = document.createElementNS(_QR_SVG_NS, 'rect');
		bg.setAttribute('width', dim);
		bg.setAttribute('height', dim);
		bg.setAttribute('fill', background);
		svg.appendChild(bg);

		const path = document.createElementNS(_QR_SVG_NS, 'path');
		path.setAttribute('d', d);
		path.setAttribute('fill', foreground);
		svg.appendChild(path);

		return svg;
	}

	constructor(el, options = {}) {
		el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!el) return;
		this.el = el;
		this.options = options;
		CerbUI.QrCode._instances.set(el, this);
		this.render();
	}

	render() {
		if(!this.el) return this;
		const svg = CerbUI.QrCode.create(this.options);
		this.el.textContent = '';
		this.el.appendChild(svg);
		this.svg = svg;
		return this;
	}

	setText(text) {
		this.options.text = text;
		return this.render();
	}

	destroy() {
		if(this.el) {
			this.el.textContent = '';
			CerbUI.QrCode._instances.delete(this.el);
		}
	}
};

// Expose the pure encoder for headless testing (Node harness) without a DOM.
// Returns { size, modules } for a text + EC level name ('L'|'M'|'Q'|'H').
CerbUI.QrCode._encodeMatrix = function(text, levelName) {
	return _qrEncode(String(text), _QR_ECL[(levelName || 'H').toUpperCase()] || _QR_ECL.H);
};
