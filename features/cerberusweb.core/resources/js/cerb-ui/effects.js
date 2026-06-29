/*
 * CerbUI.effects — small one-shot animation helpers (lowercase: a utility, not a component). These
 * replace the cosmetic jQuery-UI effects API (.effect('highlight'|'transfer'|'pulsate', …)) with CSS
 * animations. The styling lives in cerb-ui/_effects.scss (flash keyframe + .cerb-ui-transfer ghost);
 * the pulse reuses the cerb-u-anim-pulse utility keyframe.
 *
 * Every helper is reduced-motion aware — under prefers-reduced-motion it skips the animation and runs
 * the onEnd callback synchronously, so any chained behavior (pulse → click, remove a node, reveal it)
 * still happens. Each also arms a setTimeout fallback so a dropped animationend/transitionend never
 * strands the callback.
 *
 *   CerbUI.effects.flash(el, {onEnd})            — brief background flash (validation cue / anchor jump)
 *   CerbUI.effects.transfer(fromEl, toEl, {onEnd, duration})  — fly a ghost box from one el to another
 *   CerbUI.effects.pulse(el, {times, onEnd})     — pulse the element a few times, then onEnd
 */
CerbUI.effects = CerbUI.effects || {};

(function() {
	const reduceMotion = function() {
		return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	};

	// Run cb once, on the first matching event OR a safety timeout (whichever fires first).
	const once = function(el, event, timeoutMs, cb) {
		let done = false;
		const finish = function() {
			if(done) return;
			done = true;
			el.removeEventListener(event, finish);
			clearTimeout(timer);
			cb();
		};
		const timer = setTimeout(finish, timeoutMs);
		el.addEventListener(event, finish);
	};

	CerbUI.effects.flash = function(el, opts = {}) {
		el = (el instanceof jQuery) ? el[0] : el;
		const onEnd = typeof opts.onEnd === 'function' ? opts.onEnd : null;

		if(!el) { if(onEnd) onEnd(); return; }

		if(reduceMotion()) { if(onEnd) onEnd(); return; }

		el.classList.remove('cerb-ui-flash');
		void el.offsetWidth; // restart if already flashing
		el.classList.add('cerb-ui-flash');

		once(el, 'animationend', 1200, function() {
			el.classList.remove('cerb-ui-flash');
			if(onEnd) onEnd();
		});
	};

	CerbUI.effects.transfer = function(fromEl, toEl, opts = {}) {
		fromEl = (fromEl instanceof jQuery) ? fromEl[0] : fromEl;
		toEl = (toEl instanceof jQuery) ? toEl[0] : toEl;
		const onEnd = typeof opts.onEnd === 'function' ? opts.onEnd : null;
		const duration = opts.duration || 500;

		if(!fromEl || !toEl) { if(onEnd) onEnd(); return; }

		if(reduceMotion()) { if(onEnd) onEnd(); return; }

		const from = fromEl.getBoundingClientRect();
		const to = toEl.getBoundingClientRect();

		const ghost = document.createElement('div');
		ghost.className = 'cerb-ui-transfer';
		ghost.style.position = 'fixed';
		ghost.style.boxSizing = 'border-box';
		ghost.style.zIndex = '1000000';
		ghost.style.pointerEvents = 'none';
		ghost.style.top = from.top + 'px';
		ghost.style.left = from.left + 'px';
		ghost.style.width = from.width + 'px';
		ghost.style.height = from.height + 'px';
		ghost.style.transition = 'top ' + duration + 'ms ease, left ' + duration + 'ms ease, width ' + duration + 'ms ease, height ' + duration + 'ms ease';
		document.body.appendChild(ghost);

		void ghost.offsetWidth; // commit the start rect before transitioning

		ghost.style.top = to.top + 'px';
		ghost.style.left = to.left + 'px';
		ghost.style.width = to.width + 'px';
		ghost.style.height = to.height + 'px';

		once(ghost, 'transitionend', duration + 200, function() {
			if(ghost.parentNode) ghost.parentNode.removeChild(ghost);
			if(onEnd) onEnd();
		});
	};

	CerbUI.effects.pulse = function(el, opts = {}) {
		el = (el instanceof jQuery) ? el[0] : el;
		const onEnd = typeof opts.onEnd === 'function' ? opts.onEnd : null;
		const times = opts.times || 3;
		const eachMs = opts.duration || 300;

		if(!el) { if(onEnd) onEnd(); return; }

		if(reduceMotion()) { if(onEnd) onEnd(); return; }

		// Reuse the cerb-u-anim-pulse keyframe with a bounded iteration count.
		el.style.animation = 'cerb-u-anim-pulse ' + eachMs + 'ms ease-in-out ' + times;

		once(el, 'animationend', (eachMs * times) + 200, function() {
			el.style.animation = '';
			if(onEnd) onEnd();
		});
	};
})();
