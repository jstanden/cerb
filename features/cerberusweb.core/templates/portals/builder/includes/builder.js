'use strict';

// [TODO] CSRF

function CerbPortal() {
	this.version = '{$cerb_build}';
	this.base_url = '{devblocks_url full=true}{/devblocks_url}';

	this.$body = document.getElementsByTagName('body')[0];
	this.readyFunctions = [];
	this.$spinner = null;
	this.focusableSelector = 'a:not([disabled]), button:not([disabled]), input[type=text]:not([disabled]), textarea:not([disabled]), [tabindex]:not([disabled]):not([tabindex="-1"])';

	this.init();
}

CerbPortal.prototype.ready = function(fn) {
	if('loading' !== document.readyState) {
		fn();
	} else if (document.addEventListener) {
		document.addEventListener('DOMContentLoaded', fn);
	}
}

CerbPortal.prototype.init = function() {
	// Spinner

	var $circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
	$circle.setAttribute('cx', '50');
	$circle.setAttribute('cy', '50');
	$circle.setAttribute('r', '45');

	this.$spinner = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
	this.$spinner.setAttribute('viewBox', '0 0 100 100');
	this.$spinner.style.width = '32px';
	this.$spinner.appendChild($circle);
	this.$spinner.classList.add('cerb-spinner');
}

CerbPortal.prototype.createEvent = function (name, data) {
	return new CustomEvent(name, {
		detail: data
	});
}

CerbPortal.prototype.html = function (el, html) {
	el.innerHTML = html;

	var $scripts = el.querySelectorAll('script');

	for (var i = 0; i < $scripts.length; i++) {
		var $oldScript = $scripts[i];
		var $parent = $oldScript.parentNode;
		var $newScript = document.createElement('script');
		var scriptData = ($oldScript.text || $oldScript.textContent || $oldScript.innerHTML || "");
		$newScript.setAttribute('type', 'text/javascript');
		$newScript.appendChild(document.createTextNode(scriptData));
		$parent.insertBefore($newScript, $oldScript)
		$parent.removeChild($oldScript);
	}
}

CerbPortal.prototype.getSpinner = function() {
	return this.$spinner.cloneNode(true);
}

CerbPortal.prototype.disableSelection = function ($el) {
	$el.style['-webkit-touch-callout'] = 'none';
	$el.style['-webkit-user-select'] = 'none';
	$el.style['-khtml-user-select'] = 'none';
	$el.style['-moz-user-select'] = 'none';
	$el.style['-ms-user-select'] = 'none';
	$el.style['user-select'] = 'none';
}

CerbPortal.prototype.forEach = function (array, callback, scope) {
	for (var i = 0; i < array.length; i++) {
		callback.call(scope, i, array[i]);
	}
}

CerbPortal.prototype.interactionBind = function(interaction_target) {
	var inst = this;
	
	interaction_target.querySelector('.cerb-interaction-panel--close')
		.addEventListener('click', function (e) {
			e.stopPropagation();
			interaction_target.dispatchEvent($$.createEvent('cerb-interaction-event--end'));
		})
	;

	interaction_target.addEventListener('cerb-interaction-event--submit', function (e) {
		e.stopPropagation();
		inst.interactionContinue(interaction_target, true);
	});

	interaction_target.addEventListener('cerb-interaction-event--end', function (e) {
		e.stopPropagation();
		inst.interactionEnd(interaction_target);
	});
}

// [TODO] Allow multiple concurrent targets
// [TODO] This currently does nothing, but would be implemented for sheet + interaction toolbars
CerbPortal.prototype.interactionStart = function(interaction, interaction_params, interaction_style, interaction_target) {
	// Check if the target is already active
	// [TODO] We need to check more than this being null, since we can have multiple targets
	// [TODO] We probably need a DOM attribute for state
	//if(interaction_target)
	//	return;

	if (!interaction)
		return;

	var inst = this;
	var xhttp = new XMLHttpRequest()

	// If we're not given a specific target, use a global panel
	if(null == interaction_target) {
		interaction_target = document.createElement('div');
		interaction_target.className = 'cerb-interaction-panel';

		// if(interaction_style === 'full')
		// 	interaction_target.className += ' cerb-interaction-panel--style-full';

		inst.$body.append(interaction_target);
	}
	
	//if(this.$badge) this.$badge.style.display = 'none';

	var formData = new FormData();
	formData.append('interaction', interaction);

	if(interaction_params)
		formData.append('interaction_params', interaction_params)

	xhttp.onreadystatechange = function () {
		if (4 === this.readyState) {
			if(200 === this.status) {
				if(null == interaction_target)
					return;
				
				inst.html(interaction_target, this.responseText);
				
				inst.interactionBind(interaction_target);

				inst.interactionContinue(interaction_target, false);

			} else { // Not a 200 OK
				//if(inst.$badge) inst.$badge.style.display = 'block';
			}
		}
	};

	xhttp.open('POST', this.base_url + '_interaction/start');
	xhttp.send(formData);
}

CerbPortal.prototype.interactionContinue = function(interaction_target, is_submit) {
	var $form = interaction_target.querySelector('form');
	var $elements = $form.querySelector('.cerb-interaction-panel--form-elements');
	var xhttp = new XMLHttpRequest()
	var inst = this;
	var $spinner = this.getSpinner();

	$elements.appendChild($spinner);

	var formData = new FormData($form);

	if(is_submit)
		formData.append('__submit', 'continue');

	xhttp.onreadystatechange = function () {
		if (4 === this.readyState) {
			if(200 === this.status) {
				$spinner.remove();
				inst.html($elements, this.responseText);

				setTimeout(function () {
					var $el = $elements.querySelector(inst.focusableSelector);
					if ($el) $el.focus();
				}, 0);

			} else if (404 === this.status) {
				$spinner.remove();
				inst.interactionEnd(interaction_target);

			} else {
				$spinner.remove();
				inst.interactionEnd(interaction_target);
			}
		}
	};

	xhttp.open('POST', this.base_url + '_interaction/continue');
	xhttp.send(formData);
}

CerbPortal.prototype.interactionInvoke = function(formData, callback) {
	var xhttp = new XMLHttpRequest()
	
	xhttp.onreadystatechange = function () {
		if (4 === this.readyState) {
			if(200 === this.status) {
				return callback(null, this);

			} else if (404 === this.status) {
				return callback('404', this);

			} else {
				return callback('non-200', this);
			}
		}
	};

	xhttp.open('POST', this.base_url + '_interaction/invoke');
	xhttp.send(formData);
}

CerbPortal.prototype.interactionEnd = function(interaction_target) {
	// [TODO] Check the interaction target type and handle appropriately
	
	//this.$body.removeChild(this.$panel);
	//this.$panel = null;
	//if(this.$badge) this.$badge.style.display = 'block';
}

var $$ = new CerbPortal();