(function(window, document, version, callback) {
	var $jquery = document.createElement('script');
	var j, d;
	var loaded = false;
	if(!(j = window.jQuery) || version > j.fn.jquery || callback(j, loaded)) {
		$jquery.type = 'text/javascript';
		$jquery.src = 'https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js';
		$jquery.onload = $jquery.onreadystatechange = function() {
			if(!(d = this.readyState) || d == 'loaded' || d == 'complete') {
				callback((j = window.jQuery).noConflict(1), loaded = true);
				j($jquery).remove();
			}
		};
		(document.getElementsByTagName("head")[0] || document.documentElement).appendChild($jquery);
	}
})(window, document, '3.2.1', function($, jquery_loaded) {
	var base_url = '{devblocks_url full=true}{/devblocks_url}';
	var $embedder = $('#cerb-portal');
	var $head = $('head');
	var $body = $('body');
	
	var bubble_disable = $embedder.attr('data-bubble') == 'false';
	var bubble_icon_class = $embedder.attr('data-icon-class');
	
	// [TODO] CSS from embed!
	var $css = $('<link rel="stylesheet" type="text/css"/>')
		.attr('href', base_url + 'resource/cerb.bots.portal.widget/css/style.css?v=69')
		.attr('async', 'true')
		;
	
	$css.prependTo($head);
	
	var $window = null;
	var $spinner = $('<div class="cerb-bot-chat-message cerb-bot-chat-left"><div class="cerb-bot-chat-message-bubble"><span class="cerb-ajax-spinner" style="zoom:0.5;-moz-transform:scale(0.5);"></span></div></div>');
	
	if(!bubble_disable) {
		var $chat_button = $('<div/>')
			.attr('id', 'cerb-bot-interaction-button')
			.addClass('cerb-bot-interaction-button')
			.attr('data-cerb-bot-interaction', '')
		;
		
		var $div = $('<div/>');
		
		if(bubble_icon_class) {
			$div.addClass(bubble_icon_class);
		} else {
			$div.addClass('cerb-bot-interaction-button-icon');
		}
		
		$div.appendTo($chat_button);
		
		$body.append($chat_button);
	}
	
	$embedder.on('cerb-bot-trigger', function(e) {
		var interaction = e.interaction;
		var interaction_params = e.interaction_params;
		
		if(null != $window) {
			$window.html('').remove();
			$window = null;
		}
		
		var data = {
			"id": interaction,
			"browser": {
				"url": window.location.href,
				"time": (new Date()).toString()
			},
			"params": interaction_params
		};
		
		$.ajax({
			type: 'get',
			cache: false,
			url: base_url + 'interaction/start',
			xhrFields: { withCredentials: true },
			data: data
		}).done(function(html) {
			$window = $(html).attr('data-cerb-bot-interaction', interaction);
			$body.append($window);
		});
	});
	
	$embedder.on('cerb-bot-close', function(e) {
		e.stopPropagation();
		$window.html('').remove();
		$window = null;
	});
	
	$body.find('[data-cerb-bot-interaction]').click(function(e) {
		e.stopPropagation();
		var $target = $(this);
		var interaction = $target.attr('data-cerb-bot-interaction');
		var interaction_params = {};
		
		$.each(this.attributes, function() {
			if('data-cerb-bot-param-' == this.name.substring(0,20)) {
				interaction_params[this.name.substring(20)] = this.value;
			}
		});
		
		var evt = new $.Event('cerb-bot-trigger');
		evt.interaction = interaction;
		evt.interaction_params = interaction_params;
		$embedder.trigger(evt);
	});
	
	var embedder = $embedder.get()[0];
	embedder.jQuery = $;
	embedder.baseUrl = base_url;
	embedder.audio = null;
	embedder.playAudioUrl = function(url) {
		try {
			if(null == this.audio)
				this.audio = new Audio();
			
			this.audio.src = url;
			this.audio.play();
			
		} catch(e) {
			if(window.console)
				console.log(e);
		}
	}
	
	var event = null;
	if(typeof(Event) === 'function') {
		event = new Event('cerb-bot-ready');
	} else {
		event = document.createEvent('Event');
		event.initEvent('cerb-bot-ready', true, true);
	}
	
	embedder.dispatchEvent(event)
	
	if(window.location.hash) {
		var hash = window.location.hash;
		
		if(hash.substring(0, 5) == '#bot/') {
			hash = hash.substring(4);
			
			var params_pos = hash.indexOf('&');
			var interaction = hash.substring(1, (-1 == params_pos) ? hash.length : params_pos);
			var interaction_query = (-1 == params_pos) ? '' : hash.substring(params_pos+1);
			var interaction_params = {};
	
			if(interaction_query.length > 0) {
				var parts = interaction_query.split('&');
				for(var idx in parts) {
					var keyval = parts[idx].split('=');
					interaction_params[keyval[0]] = decodeURIComponent(keyval[1]);
				}
			}
			
			var evt = new $.Event('cerb-bot-trigger');
			evt.interaction = interaction;
			evt.interaction_params = interaction_params;
			$embedder.trigger(evt);
		}
	}
});