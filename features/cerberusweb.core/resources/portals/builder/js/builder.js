'use strict';

function CerbPortal() {
	this.readyFunctions = [];
}

CerbPortal.prototype.ready = function(fn) {
	if(document.readyState != 'loading') {
		fn();
	} else if (document.addEventListener) {
		document.addEventListener('DOMContentLoaded', fn);
	} else {
		document.attachEvent('onreadystatechange', function() {
			if(document.readyState != 'loading')
				fn();
		});
	}
}

CerbPortal.prototype.createEvent = function(name) {
	var event = null;
	
	if(window.CustomEvent) {
		event = new CustomEvent(name);
	} else {
		event = document.createEvent('Event');
		event.initCustomEvent(name, true, true);
	}
	
	return event;
}

CerbPortal.prototype.html = function(el, html) {
	el.innerHTML = html;
	
	var $scripts = el.querySelectorAll('script');
	
	for(var i = 0; i < $scripts.length; i++) {
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

CerbPortal.prototype.forEach = function(array, callback, scope) {
	for(var i = 0; i < array.length; i++) {
		callback.call(scope, i, array[i]);
	}
}

var $$ = new CerbPortal();