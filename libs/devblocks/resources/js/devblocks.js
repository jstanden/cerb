function DevblocksClass() {
	this.audio = null;
	
	this.getBrowserName = function() {
		if (navigator.userAgent.indexOf("Opera") !== -1 || navigator.userAgent.indexOf('OPR') !== -1) {
			return 'opera';
		} else if (navigator.userAgent.indexOf("Edg") !== -1) {
			return 'edge';
		} else if (navigator.userAgent.indexOf("Chrome") !== -1) {
			return 'chrome';
		} else if (navigator.userAgent.indexOf("Safari") !== -1) {
			return 'safari';
		} else if (navigator.userAgent.indexOf("Firefox") !== -1) {
			return 'firefox';
		} else if (navigator.userAgent.indexOf("MSIE") !== -1 || document.documentMode) {
			return 'msie';
		} else {
			return '';
		}
	}
	
	this.playAudioUrl = function(url) {
		try {
			if(null == this.audio)
				this.audio = new Audio();
			
			if(undefined == url || null == url || 0 == url.length)
				return;
			
			this.audio.src = url;
			this.audio.play();
			
		} catch(e) {
			if(window.console)
				console.log(e);
		}
	};

	this.getSpinner = function(float) {
		if(float) {
			return $('<svg class="cerb-spinner cerb-float" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="45"/></svg>');
		} else {
			return $('<svg class="cerb-spinner" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="45"/></svg>');
		}
	};
	
	this.getObjectKeyByPath = function(o, path) {
		path = path.split('.');
		var value = o[path.shift()];
		while(value && path.length)
			value = value[path.shift()];
		return value;
	};
	
	// Source: http://stackoverflow.com/a/16693578
	this.uniqueId = function(prefix) {
		return '' + (prefix ? prefix : '') + (Math.random().toString(16)+"000000000").substr(2,8);
	};
	
	/* Source: http://bytes.com/forum/thread90068.html */
	// [TODO] Does this matter with caret.js anymore?
	this.getSelectedText = function() {
		if (window.getSelection) { // recent Mozilla
			var selectedString = window.getSelection();
		} else if (document.all) { // MSIE 4+
			var selectedString = document.selection.createRange().text;
		} else if (document.getSelection) { //older Mozilla
			var selectedString = document.getSelection();
		}
		
		return selectedString;
	};

	this.formDisableSubmit = function($frm) {
		if(!($frm instanceof jQuery))
			return;
		
		$frm.each(function() {
			this.onsubmit = function() { return false; };
		});
	};
	
	this.onClickRemoveParent = function(e) {
		e.stopPropagation();
		$(this).parent().remove();
	}

	this.onClickExternalLink = function(e) {
		e.stopPropagation();
		let link = $(this).attr('data-cerb-external-link');
		genericAjaxPopup('externalLink','c=security&a=renderLinkPopup&url=' + encodeURIComponent(link),null,true);
		return false;
	}

	this.getFormEnabledCheckboxValues = function(form_id,element_name) {
		return $("#" + form_id + " INPUT[name='" + element_name + "']:checked")
		.map(function() {
			return $(this).val();
		})
		.get()
		.join(',')
		;
	};
	
	this.saveAjaxTabForm = function($frm) {
		genericAjaxPost($frm, '', null, function(json) {
			Devblocks.clearAlerts();
			
			if(json && typeof json == 'object') {
				if(json.error) {
					Devblocks.createAlertError(json.error);
					
				} else {
					$frm.fadeTo('fast', 0.2);
					
					if (json.message) {
						Devblocks.createAlert(json.message, 'note');
					}
					
					var funcReloadTab = function() {
						// Fade in the form (if the tab isn't Ajax)
						$frm.fadeTo('fast', 1.0);
						
						// Reload the tab
						var $tabs = $frm.closest('.ui-tabs');
						var tabId = $tabs.tabs("option", "active");
						$tabs.tabs("load", tabId);
					};
					
					setTimeout(funcReloadTab, 750);
				}
			}
		});
	};
	
	this.saveAjaxForm = function($frm, options) {
		genericAjaxPost($frm, '', null, function(json) {
			Devblocks.handleAjaxFormResponse($frm, json, options);
		});
	};
	
	this.handleAjaxFormResponse = function($frm, json, options) {
		Devblocks.clearAlerts();
		
		if(typeof options != 'object')
			options = {};
		
		if(json && typeof json == 'object') {
			if(json.error) {
				Devblocks.createAlertError(json.error);
				
			} else {
				$frm.fadeTo('fast', 0.2);
				
				if(json.message) {
					Devblocks.createAlert(json.message, 'note');
				}
				
				if(typeof options.success == 'function') {
					options.success(json);
				}
				
				var funcShowForm = function() {
					$frm.fadeTo('fast', 1.0);
				};
				
				setTimeout(funcShowForm, 750);
			}
		}
	};
	
	this.clearAlerts = function() {
		var $alerts = $('#cerb-alerts');
		$alerts.children().remove();
	};
	
	this.createAlertError = function(message) {
		return this.createAlert(message, 'error', 0);
	};
	
	this.createAlert = function(message, style, duration) {
		if(undefined == message)
			return;
		
		if(undefined == duration)
			duration = 2500;
		
		message = message
			.replaceAll('&quot;', '"')
		;
		
		var $alerts = $('#cerb-alerts');
		
		var $alert = $('<div/>')
			.addClass('cerb-alert')
			.text(message)
			.hide()
			.appendTo($alerts)
			;
		
		$('<span class="cerb-alert-close"><span class="cerb-icons cerb-icon-circle-remove"></span></span>')
			.on('click', function(e) {
				var $alert = $(this).closest('.cerb-alert');
				
				$alert.effect('slide',{ direction:'up', mode:'hide' }, 500, function() {
					$alert.remove();
				});
			})
			.appendTo($alert)
			;
		
		if(style != undefined)
			$alert
				.addClass('cerb-alert-' + style)
				;
		
		$alert.delay(0).effect('slide',{ direction:'up', mode:'show' }, 250);
		
		if(parseInt(duration) > 0) {
			$alert.delay(duration).effect('slide',{ direction:'up', mode:'hide' }, 500, function() {
				$alert.remove();
			});
		}
		
		return $alert;
	};
	
	this.showError = function(target, message, animate) {
		var $html = $('<div class="ui-widget"/>')
			.append(
				$('<div class="ui-state-error ui-corner-all" style="padding:0 0.5em;margin:0.5em;"/>')
				.append(
					$('<p/>').text(message)
						.prepend($('<span class="cerb-icons cerb-icon-circle-exclamation-mark" style="margin-right:5px;"></span>'))
				)
			)
		;
		
		var $status = $(target).html($html).show();
		
		animate = (null == animate || false !== animate);
		if(animate)
			$status.effect('slide',{ direction:'up', mode:'show' },250);
		
		return $status;
	};
	
	this._tooltip = undefined;
	
	// Anchored callout: a floating panel pinned to a DOM element, with an arrow pointing at it,
	// dismissed by click or outside-click. Optional jQuery-UI-style my/at position the panel/arrow;
	// omitted, it defaults to pointing at the target's top-middle (and flips/slides to stay on-screen).
	this.tooltip = function(target, message, my, at) {
		if(undefined === message)
			return;
		
		let el = $(target)[0];
		if(!el)
			return;
		
		if(undefined === this._tooltip)
			this._tooltip = new CerbUI.Tooltip( { gap: 0 } );
		
		// Pass the message as a text node (jQuery UI escaped the title; keep that posture).
		this._tooltip.anchor(document.createTextNode(message), el, { my: my, at: at, interactive: true });
	}
	
	this.interactionWorkerPostActions = function(eventData, editor) {
		if('object' != typeof eventData.return)
			return;
		
		if(eventData.return.hasOwnProperty('snippet') 
			&& 'object' == typeof editor
			&& 'function' == typeof editor.insertSnippet
		) {
			editor.insertSnippet(eventData.return['snippet']);
			editor.focus();
		}
		
		if(eventData.return.hasOwnProperty('command')
			&& 'object' == typeof eventData.return.command
			&& 'object' == typeof editor
			&& 'function' == typeof editor.execCommand
		) {
			let editor_command = eventData.return.command;

			if('editor_commands' === editor_command.name) {
				editor.execCommand('openCommandPalette');
			}
		}
		
		if(eventData.return.hasOwnProperty('alert')) {
			Devblocks.createAlert(eventData.return['alert']);
		}
		
		if(
			eventData.return.hasOwnProperty('search')
			&& 'object' == typeof eventData.return.search
			&& eventData.return.search.hasOwnProperty('record_type')
			&& eventData.return.search.hasOwnProperty('query')
		) {
			$('<div/>')
				.attr('data-context', eventData.return.search.record_type)
				.attr('data-query', eventData.return.search.query)
				.cerbSearchTrigger()
				.on('cerb-search-opened', function(e) {
					e.stopPropagation();
					$(this).remove();
				})
				.click()
			;
		}

		// Open links in a new tab
		if(eventData.return.hasOwnProperty('open_link')) {
			var a = document.createElement('a');
			a.style.display = 'none';
			document.body.appendChild(a);
			a.href = eventData.return['open_link'];
			a.target = '_blank';
			a.click();
			a.remove();
		}
		
		// Open URLs in the same tab
		if(
			eventData.return.hasOwnProperty('open_url')
			&& eventData.return['open_url'].startsWith('http')
		) {
			window.document.location = eventData.return['open_url'];
		}
		
		if(
			eventData.return.hasOwnProperty('callout')
			&& eventData.return.callout.hasOwnProperty('selector')
		) {
			let $target = $(eventData.return.callout.selector);
			
			if(!$target.visible()) {
				$target[0].scrollIntoView();
			}
			
			let message = eventData.return.callout.message;
			let my = eventData.return.callout.my;
			let at = eventData.return.callout.at;
			Devblocks.tooltip($target, message, my, at);
		}
		
		// Open time tracking timer
		if(typeof timeTrackingTimer !== 'undefined' && eventData.return.hasOwnProperty('timer')) {
			timeTrackingTimer.play(eventData.return['timer']);
		}
		
		// Impersonate (admin only)
		if(eventData.return.hasOwnProperty('impersonate')) {
			let formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'worker');
			formData.set('action', 'su');
			formData.set('worker_id', eventData.return['impersonate']);
			
			let onSuccess = function() {
				document.location.reload();
			};
			
			genericAjaxPost(formData,null,null, onSuccess);
		}
	}
	
	this.toolbarAfterActions = function(done_params, options) {
		// Refresh widgets
		let response = {};
		
		if('object' !== typeof options)
			options = { };
		
		if(!options.hasOwnProperty('widgets'))
			return response;

		if(done_params.has('refresh_widgets')) {
			// If false, refresh nothing; otherwise refresh everything
			if('1' === done_params.get('refresh_widgets')) {
				response['refresh_widget_ids'] = [];
			} else if('0' === done_params.get('refresh_widgets')) {
				response['refresh_widget_ids'] = [-1];
			}

		} else if(done_params.has('refresh_widgets[]')) {
			if(-1 !== $.inArray('all', done_params.getAll('refresh_widgets[]'))) {
				response['refresh_widget_ids'] = [];
				
			} else {
				let widgets = options.widgets || $.find('');
				response['refresh_widget_ids'] = [];
				
				// Find the named widgets (all by default)
				widgets
					.filter(function() {
						var name = $(this).attr(options['widget_name_key'] || 'data-widget-name');

						if(undefined === name)
							return false;

						return -1 !== $.inArray(name, done_params.getAll('refresh_widgets[]'));
					})
					.each(function() {
						var widget_id = parseInt($(this).attr(options['widget_id_key'] || 'data-widget-id'));

						if(widget_id)
							response['refresh_widget_ids'].push(widget_id);
					})
				;
				
				// If no matches, refresh nothing
				if(0 === response['refresh_widget_ids'].length) {
					response['refresh_widget_ids'] = [-1];
				}
			}

		// By default, refresh just this widget
		} else {
			response['refresh_widget_ids'] = options.hasOwnProperty('default_widget_ids') ? options.default_widget_ids : [];
		}
		
		return response;
	}
	
	this.getDefaultjQueryUiTabOptions = function() {
		var $this = this;
		
		return {
			activate: function(event, ui) {
				var tabsId = ui.newPanel.closest('.ui-tabs').attr('id');
				
				if(!tabsId || 0 == tabsId.length)
					return;
				
				var index = ui.newTab.index();
				$this.setjQueryUiTabSelected(tabsId, index);
			},
			beforeLoad: function(event, ui) {
				var tab_title = ui.tab.find('> a').first().clone();
				var $div = $('<div style="font-size:18px;font-weight:bold;text-align:center;padding:10px;margin:10px;"/>')
					.text('Loading: ' + tab_title.text().trim())
					.append($('<br>'))
					.append(Devblocks.getSpinner())
					;
				ui.panel.html($div);
				
				ui.ajaxSettings.error = function(err) {
					ui.panel.html('');
					
					if(typeof err == 'object' && err.status) {
						Devblocks.clearAlerts();
						if(401 === err.status) {
							let $alert = Devblocks.createAlert('', 'error', 0);
							let $a = $('<b/>').css('margin-right', '0.5em').text('Your session has expired.');
							let $b = $('<a/>').attr('href', window.location.href).text('Please log back in.');
							$alert.append($a).append($b);
						} else if(404 === err.status) {
							let $alert = Devblocks.createAlert('', 'error', 0);
							let $a = $('<b/>').css('margin-right','0.5em').text('The requested resource was not found.');
							$alert.append($a);
						} else if(504 === err.status) {
							let $alert = Devblocks.createAlert('', 'error', 0);
							let $a = $('<b/>').css('margin-right','0.5em').text('The request timed out.');
							$alert.append($a);
						} else {
							let $alert = Devblocks.createAlert('', 'error', 0);
							let $a = $('<b/>').css('margin-right','0.5em').text('An unexpected error occurred.');
							let $b = $('<a/>').attr('href',window.location.href).text('Did your session expire?');
							$alert.append($a).append($b);
						}
					}
				}
			}
		};
	};
	
	this.setjQueryUiTabSelected = function(tabsId, index) {
		var selectedTabs = {};
		var currentRevision = '1'; // Increment this to invalidate
		
		if(undefined != localStorage.selectedTabs) {
			selectedTabs = JSON.parse(localStorage.selectedTabs);
			
			var revision = selectedTabs['_revision'];
			
			if(undefined == revision || currentRevision != revision) {
				selectedTabs = {'_revision': currentRevision};
			}
		} else {
			selectedTabs = {'_revision': currentRevision };
		}
		
		selectedTabs[tabsId] = index;
		localStorage.selectedTabs = JSON.stringify(selectedTabs);
	};
	
	this.getjQueryUiTabSelected = function(tabsId, activeTab) {
		if(undefined != activeTab) {
			var $tabs = $('#' + tabsId);
			var $activeTab = $tabs.find('li[data-alias="' + activeTab + '"]');
			
			if($activeTab.length > 0) {
				var selectedTabs = {};
				
				if(undefined != localStorage.selectedTabs)
					selectedTabs = JSON.parse(localStorage.selectedTabs);
				
				selectedTabs[tabsId] = $activeTab.index();
				
				try {
					localStorage.selectedTabs = JSON.stringify(selectedTabs);
				} catch(e) {
					
				}
				
				return $activeTab.index();
			}
		}
		
		if(undefined == localStorage.selectedTabs)
			return 0;
		
		var selectedTabs = JSON.parse(localStorage.selectedTabs);
		
		if(typeof selectedTabs != "object" || undefined == selectedTabs[tabsId])
			return 0;
		
		return selectedTabs[tabsId];
	};
	
	this.callbackPeekEditSave = function(e) {
		if(!(typeof e == 'object'))
			return false;
		
		var $button = $(e.target);
		var $popup = genericAjaxPopupFind($button);
		var $frm = $button.closest('form');
		var id = $frm.find('input:hidden[name=id]').val();
		var options = e.data;
		var is_delete = (options && options.mode === 'delete');
		var is_create = (options && (options.mode === 'create' || options.mode === 'create_continue')) || (0 === String(id).length);
		var is_continue = (options && (options.mode === 'continue' || options.mode === 'create_continue'));
		
		if(e.originalEvent && e.originalEvent.detail && e.originalEvent.detail > 1)
			return;
		
		if(options && options.before && typeof options.before == 'function') {
			options.before(e, $frm);
		}
		
		if(!($popup instanceof jQuery))
			return false;
		
		if(!($frm instanceof jQuery))
			return false;
		
		// Clear the status div
		Devblocks.clearAlerts();
		
		// Are we deleting the record?
		if(is_delete) {
			$frm.find('input:hidden[name=do_delete]').val('1');
		} else {
			$frm.find('input:hidden[name=do_delete]').val('0');
		}
		
		// Show a spinner
		var $spinner = Devblocks.getSpinner()
			.css('max-width', '16px')
			.css('margin-right', '5px')
			;
		$spinner.insertBefore($button);
		
		$button.prop('disabled', true).fadeTo('fast', 0.5);
		
		let cb = function(e) {
			$button.prop('disabled', false).fadeTo('fast', 1.0);
			$spinner.remove();
			
			if(!(typeof e == 'object')) {
				Devblocks.createAlertError("An unexpected network error occurred.");
				return;
			}
			
			if(e.hasOwnProperty('status') && e.hasOwnProperty('statusText') && 403 === e.status) {
				Devblocks.createAlertError("Access denied. Has your session expired?");
				return;
			}
			
			if(options && options.after && typeof options.after == 'function') {
				options.after(e);
			}
			
			if(e.status) {
				var event;
				
				if(is_delete) {
					event = new $.Event('peek_deleted');
					event.is_delete = is_delete;

				} else {
					event = new $.Event('peek_saved');

					event.is_new = is_create;
					event.is_continue = is_continue;
				}
				
				// Meta fields
				for(var k in e) {
					event[k] = e[k];
				}
				
				// Reload the associated view (underlying helper)
				if(e.view_id)
					genericAjaxGet('view'+e.view_id, 'c=internal&a=invoke&module=worklists&action=refresh&id=' + e.view_id);
				
				if(is_continue) {
					Devblocks.createAlert('Saved!', 'note');
					$popup.triggerHandler(event);
					
					// If this is a create+continue we need to reload the editor
					if(is_create) {
						var layer = $popup.attr('data-layer');
						var popup_url = 'c=internal&a=invoke&module=records&action=showPeekPopup' +
							'&context=' + encodeURIComponent(e.context) +
							'&context_id=' + encodeURIComponent(e.id) +
							'&view_id=' + encodeURIComponent(e.view_id) +
							'&edit=true'
						;
						
						// Body snatch
						
						var $new_popup = genericAjaxPopup(layer, popup_url, 'reuse', false);
						$new_popup.focus();
					}
					
				} else {
					genericAjaxPopupClose($popup, event);
				}
				
			} else {
				// Output errors
				if(e.error)
					Devblocks.createAlertError(e.error);
				
				event = new $.Event('peek_error');
				event.error = e.error;
				$popup.triggerHandler(event);
				
				// Highlight the failing field
				if(e.hasOwnProperty('field'))
					$frm.find('[name=' + e.field + ']').focus();
			}
		}
		
		let hookError = function() {
			$button.prop('disabled', false).fadeTo('fast', 1.0);
			$spinner.remove();
		}
		
		genericAjaxPost($frm, '', '', cb, {
			'error': hookError
		});
	};
	
	this.callbackPeekEditDeletePrompt = function(e) {
		e.stopPropagation();
		let $button = $(e.target);
		
		if(!$button.is('button'))
			$button = $button.closest('button');
		
		$button.parent().siblings('fieldset.delete').fadeIn();
		$button.closest('div').fadeOut();
	};
	
	this.callbackPeekEditDeleteCancel = function(e) {
		e.stopPropagation();
		let $button = $(e.target);
		
		if(!$button.is('button'))
			$button = $button.closest('button');
		
		$button.closest('form').find('div.buttons').fadeIn();
		$button.closest('fieldset.delete').fadeOut();
	};
	
	this.triggerEvent = function(element, e) {
		$(element).trigger(e);
	};
	
	this._loadedResources = {};
	
	this.getResourceState = function(url) {
		var state = this._loadedResources.hasOwnProperty(url) ? this._loadedResources[url] : null;
		return state;
	};
	
	this.setResourceState = function(url, state) {
		this._loadedResources[url] = state;
	};
	
	this.loadStylesheet = function(url, callback) {
		var $instance = this;
		var state = $instance.getResourceState(url);
		
		if(null == state) {
			var options = {
				dataType: "text",
				cache: true,
				url: url
			};
			
			$instance.setResourceState(url, 'loading');
			
			return jQuery.ajax(options)
				.done(function(data) {
					$('<style>\n' + data + '</style>').appendTo('head');
					$instance.setResourceState(url, 'loaded');
					callback();
				})
				.fail(function() {
					$instance.setResourceState(url, 'failed');
					callback(false);
				})
			;
			
		} else if ('loading' === state) {
			var timer = null;
			
			timer = setInterval(function() {
				var state = $instance.getResourceState(url);
				
				if('loaded' == state) {
					clearInterval(timer);
					callback();
					
				} else if ('failed' == state) {
					clearInterval(timer);
					callback(false);
				}
			}, 50);
			
		} else {
			callback();
		}
	};
	
	this.loadScript = function(url, callback) {
		var $instance = this;
		var state = $instance.getResourceState(url);
		
		if(null == state) {
			var options = {
				dataType: "script",
				scriptAttrs: {
					nonce: DevblocksRequestNonce
				},
				cache: true,
				url: url
			};
			
			$instance.setResourceState(url, 'loading');
			
			return jQuery.ajax(options)
				.done(function() {
					$instance.setResourceState(url, 'loaded');
					callback();
				})
				.fail(function() {
					$instance.setResourceState(url, 'failed');
					callback(false);
				})
			;
			
		} else if ('loading' === state) {
			var timer = null;
			
			timer = setInterval(function() {
				var state = $instance.getResourceState(url);
				
				if('loaded' == state) {
					clearInterval(timer);
					callback();
					
				} else if ('failed' == state) {
					clearInterval(timer);
					callback(false);
				}
			}, 50);
			
		} else {
			callback();
		}
	};
	
	this.loadScripts = function(urls, finished) {
		if(!Array.isArray(urls))
			return callback(false);
		
		var $instance = this;
		var jobs = [];
		
		urls.forEach(function(url) {
			if(url.substring(0,1) == '/')
				url = DevblocksWebPath + url.substring(1);
			
			jobs.push(async.apply($instance.loadScript.bind($instance), url));
		});
		
		async.parallelLimit(jobs, 2, function(err, json) {
			if(err)
				return finished(err);
			
			finished();
		});
	};
	
	this.loadResources = function(resources, finished) {
		if(typeof(resources) != 'object')
			return callback(false);
		
		var $instance = this;
		var jobs = [];
		
		if(resources.hasOwnProperty('css')) {
			if(!Array.isArray(resources.css))
				return callback(false);
			
			resources.css.forEach(function(url) {
				if(url.substring(0,1) == '/')
					url = DevblocksWebPath + url.substring(1);
				
				jobs.push(async.apply($instance.loadStylesheet.bind($instance), url));
			});
		}
		
		if(resources.hasOwnProperty('js')) {
			if(!Array.isArray(resources.js))
				return callback(false);
			
			resources.js.forEach(function(url) {
				if(url.substring(0,1) === '/')
					url = DevblocksWebPath + url.substring(1);
				
				jobs.push(async.apply($instance.loadScript.bind($instance), url));
			});
		}
		
		async.parallelLimit(jobs, 2, function(err, json) {
			if(err)
				return finished(err);
			
			finished();
		});
	};

	// https://gist.github.com/ghinda/8442a57f22099bdb2e34#gistcomment-2386093
	// https://gist.github.com/ghinda/8442a57f22099bdb2e34#gistcomment-2719686
	this.objectToFormData = function(model, form, namespace) {
		let formData = form || new FormData();
		for (let propertyName in model) {
			if (!model.hasOwnProperty(propertyName) || !model[propertyName]) continue;
			let formKey = namespace ? `${namespace}[${propertyName}]` : propertyName;
			if (model[propertyName] instanceof Date)
				formData.append(formKey, model[propertyName].toISOString());
			else if (model[propertyName] instanceof Array) {
				model[propertyName].forEach((element, index) => {
					const tempFormKey = `${formKey}[${index}]`;
					if (typeof element === 'object')
						this.objectToFormData(element, formData, tempFormKey);
					else
						formData.append(tempFormKey, element.toString());
				});
			}
			else if (typeof model[propertyName] === 'object' && !(model[propertyName] instanceof File))
				this.objectToFormData(model[propertyName], formData, formKey);
			else
				formData.append(formKey, model[propertyName].toString());
		}
		return formData;
	};
	
}

var Devblocks = new DevblocksClass();

// [TODO] Remove this in favor of jQuery $(select).val()
function selectValue(e) {
	return e.options[e.selectedIndex].value;
}

function toggleDiv(divName,state) {
	var div = document.getElementById(divName);
	if(null == div) return;
	var currentState = div.style.display;
	
	if(null == state) {
		if(currentState == "block") {
			div.style.display = 'none';
		} else {
			div.style.display = 'block';
		}
	} else if (null != state && (state == '' || state == 'block' || state == 'inline' || state == 'none')) {
		div.style.display = state;
	}
}

function checkAll(divName, state) {
	var div = document.getElementById(divName);
	if(null == div) return;
	
	var boxes = div.getElementsByTagName('input');
	var numBoxes = boxes.length;
	
	for(x=0;x<numBoxes;x++) {
		if(null != boxes[x].name) {
			if(state == null) state = !boxes[x].checked;
			boxes[x].checked = (state) ? true : false;
			// This may not be needed when we convert to jQuery
			$(boxes[x]).trigger('change');
			$(boxes[x]).trigger('check');
		}
	}
}

// [JAS]: [TODO] Make this a little more generic?
function appendTextboxAsCsv(formName, field, oLink) {
	var frm = document.getElementById(formName);
	if(null == frm) return;
	
	var txt = frm.elements[field];
	var sAppend = '';
	
	// [TODO]: check that the last character(s) aren't comma or comma space
	if(0 != txt.value.length && txt.value.substr(-1,1) != ',' && txt.value.substr(-2,2) != ', ')
		sAppend += ', ';
		
	sAppend += oLink.innerHTML;
	
	txt.value = txt.value + sAppend;
}

$(document).keydown(function(e) {
	var keycode = e.which || e.keyCode;
	
	if(27 === keycode) {
		e.stopPropagation();
		e.preventDefault();
		
		let dialogs = $(document).data('uiDialogInstances');
		
		if(!Array.isArray(dialogs))
			return;
		
		if(0 === dialogs.length)
			return;

		if(dialogs[0].element.is('[cerb-popup-confirm]')) {
			dialogs[0].element.dialog('close');
			return;
		}
		
		// If the top popup has inputs
		if(dialogs[0].element.find('input:text,input:password,input:checkbox,input:radio,textarea').length > 0 
			// But isn't a search popup
			&& !dialogs[0].element.is('[id^=popupsearch],[id^=popuplinks_]')) {
			confirmPopup(
				'Discard changes',
				'Are you sure you want to close this popup without saving?',
				function () {
					let popup = genericAjaxPopupFind(this.element);
					
					if(popup) {
						genericAjaxPopupClose(popup, 'peek_aborted');
					}
				}.bind(dialogs[0])
			);
		} else {
			let popup = genericAjaxPopupFind(dialogs[0].element);
			genericAjaxPopupClose(popup, 'peek_aborted');
		}
	}
});

function confirmPopup(title, content, callbackOk, callbackCancel) {
	if(null == title)
		title = 'Confirm';
	
	if(null == content)
		content = 'Are you sure?';
	
	if('function' !== typeof callbackOk)
		callbackOk = function() {};
	
	if('function' !== typeof callbackCancel)
		callbackCancel = function() {};
	
	$('<div/>')
		.attr('cerb-popup-confirm', true)
		.dialog({
			open: function() {
				let $dialog = $(this).closest('.ui-dialog');
				$dialog.find('.ui-dialog-titlebar-close').hide();
				$dialog.find('.ui-dialog-buttonpane').css('border', '0');
			},
			buttons: {
				"Ok": function() {
					callbackOk();
					$(this).dialog('close');
				},
				"Cancel": function() {
					callbackCancel();
					$(this).dialog('close');
				}
			},
			close: function(e, ui) {
				$(this).remove();
			},
			closeOnEscape: false,
			resizable: false,
			title: title,
			modal: true
		}).text(content)
	;
}
// The modal "Loading, please wait…" overlay — now backed by the singleton CerbUI.Dialog.Loading factory (the
// jQuery-UI panel is retired). Both names + their no-arg call sites are preserved; show() takes an optional message.
function showLoadingPanel(message) {
	if(window.CerbUI && CerbUI.Dialog && CerbUI.Dialog.Loading)
		CerbUI.Dialog.Loading.show(message);
}

function hideLoadingPanel() {
	if(window.CerbUI && CerbUI.Dialog && CerbUI.Dialog.Loading)
		CerbUI.Dialog.Loading.hide();
}


function genericAjaxPopupFind($sel) {
	var $devblocksPopups = $('#devblocksPopups');
	var $data = $devblocksPopups.data();
	var $element = $($sel).closest('DIV.devblocks-popup');
	for($key in $data) {
		if($element.attr('id') == $data[$key].attr('id'))
			return $data[$key];
	}
	
	return null;
}

function genericAjaxPopupFetch($layer) {
	return $('#devblocksPopups').data($layer);
}

function genericAjaxPopupClose($layer, $event) {
	var $popup = null;
	
	if($layer instanceof jQuery) {
		$popup = $layer;
	} else if(typeof $layer == 'string') {
		$popup = genericAjaxPopupFetch($layer);
	}
	
	if(null != $popup) {
		try {
			if(null != $event)
				$popup.trigger($event);
		} catch(e) { if(window.console) console.log(e); }
		
		try {
			$popup.dialog('close');
		} catch(e) { if(window.console) console.log(e); }
		
		return true;
	}
	return false;
}

function genericAjaxPopupDestroy($layer) {
	var $popup = null;
	
	if($layer instanceof jQuery) {
		$popup = $layer;
	} else if(typeof $layer == 'string') {
		$popup = genericAjaxPopupFetch($layer);
	}

	if(null != $popup) {
		genericAjaxPopupClose($popup);
		try {
			$popup.dialog('destroy');
			$popup.unbind();
		} catch(e) { }
		$($('#devblocksPopups').data($layer)).remove(); // remove DOM
		$('#devblocksPopups').removeData($layer); // remove from registry
		return true;
	}
	return false;
}

function genericAjaxPopupRegister($layer, $popup) {
	$devblocksPopups = $('#devblocksPopups');
	
	if(0 == $devblocksPopups.length) {
		$('body').append("<div id='devblocksPopups' style='display:none;'></div>");
		$devblocksPopups = $('#devblocksPopups');
	}
	
	$('#devblocksPopups').data($layer, $popup);
}

function genericAjaxPopup($layer,request,target,modal,width,cb) {
	// Default options
	var options = {
		title: "Loading...",
		autoOpen : false,
		closeOnEscape : false,
		draggable : true,
		modal : false,
		resizable : true,
		height: "auto",
		width : Math.max(Math.floor($(window).width()/2), 500) + 'px', // Larger of 50% of browser width or 500px
		dragStop: function(event, ui) {
			var $popup = $(this);
			var $dialog = $popup.closest('div.ui-dialog');
			$popup.css('height', 'auto');
			$dialog.css('height', 'auto');
		},
		resizeStop: function(event, ui) {
			var $popup = $(this);
			var $dialog = $popup.closest('div.ui-dialog');
			$popup.css('height', 'auto');
			$dialog.css('height', 'auto');
		},
		close: function(event) {
			var $popup = $(this);
			
			if('object' == typeof event && event.currentTarget) {
				if($(event.currentTarget).is('.ui-dialog-titlebar-close')) {
					$popup.triggerHandler($.Event('peek_aborted'));
				}
			}
			
			$popup.triggerHandler($.Event('popup_close'));
			$('#devblocksPopups').removeData($layer);
			$popup.unbind().find(':focus').blur();
			$popup.closest('.ui-dialog').remove();
		}
	};
	
	var $popup = null;
	var $listener_holder = $('<div/>');

	// Restore position from previous dialog?
	if(target === 'reuse') {
		$popup = genericAjaxPopupFetch($layer);
		if(null != $popup) {
			try {
				var offset = $popup.closest('div.ui-dialog').offset();
				var left = offset.left - $(document).scrollLeft();
				var top = offset.top - $(document).scrollTop();
				options.position = { 
					my: 'left top',
					at: 'left+' + left + ' top+' + top 
				};
			} catch(e) { }
			
		} else {
			options.position = {
				my: "center top",
				at: "center top+35"
			};
		}
		target = null;

		if(undefined !== $popup) {
			var old_listeners = $._data($popup[0], 'events');

			if (old_listeners)
				$.each(old_listeners, function () {
					$.each(this, function () {
						var parent_event = this;
						$listener_holder.each(function () {
							$(this).bind(parent_event.type, parent_event.handler);
						})
					});
				});
		}
		
	} else if(target && typeof target == "object" && null != target.my && null != target.at) {
		options.position = {
			my: target.my,
			at: target.at
		};
		
	} else {
		options.position = {
			my: "center top",
			at: "center top+35"
		};
	}
	
	// Reset (if exists)
	genericAjaxPopupDestroy($layer);
	
	if(undefined != width && null != width) {
		if(typeof width == 'string' && width.substr(-1) == '%') {
			width = Math.floor($(window).width() * parseInt(width)/100);
		}
		
		if(width < 500)
			width = 500;
		
		if(width > window.innerWidth)
			width = window.innerWidth - 30;
		
		options.width = width + 'px';
	}
	
	if(null != modal)
		options.modal = modal;
	
	$popup = $("#popup"+$layer);

	if(0 === $popup.length) {
		$popup = $('<div/>')
			.attr('id', 'popup' + $layer)
			.addClass('devblocks-popup')
			.hide()
			.appendTo($('body'))
			;
	}

	// Persist
	genericAjaxPopupRegister($layer, $popup);

	// Target
	if(null != target && null == target.at) {
		options.position = {
			my: "right bottom",
			at: "left top",
			of: target
		};
	}

	// Render
	$popup.dialog(options);

	// Layer
	$popup.attr('data-layer', $layer);

	// Listeners
	var copy_listeners = $._data($listener_holder[0], 'events');

	if(copy_listeners)
		$.each(copy_listeners, function() {
			$.each(this, function() {
				var parent_event = this;
				$popup.each(function() {
					$(this).bind(parent_event.type, parent_event.handler);
				})
			});
		});

	// Show a spinner
	var $spinner = $('<a href="#" style="outline:none;"/>').append(Devblocks.getSpinner());
	$popup.append($spinner);

	// Open
	$popup.dialog('open');

	// Popup min/max functionality
	var $titlebar = $popup.closest('.ui-dialog')
		.find('.ui-dialog-titlebar')
	;

	var $button_minmax = $("<button/>")
		.addClass('ui-dialog-titlebar-minmax')
		.button({
			text: false,
			icons: { primary: 'ui-icon-caret-1-n' }
		})
		.on('click', function() {
			var $this = $(this);
			var $dialog = $popup.closest('.ui-dialog');

			if($popup.is(':hidden')) {
				$dialog.css('position', $dialog.attr('data-position'));
				$this.button('option', 'icons', { primary: 'ui-icon-caret-1-n' } );
				$popup.dialog( "option", "position", { my: "center top", at: "center top+35", of: window } );
				$popup.show();
			} else {
				$popup.hide();
				$dialog.attr('data-position', $dialog.css('position'));
				$dialog.css('position', 'fixed');
				$popup.dialog( "option", "position", { my: "center top", at: "center top", of: window } );
				$this.button('option', 'icons', { primary: 'ui-icon-caret-1-s' } );
			}
		})
	;

	$titlebar
		.append($button_minmax)
	;

	if(null == options.position)
		$popup.dialog('option', 'position', { my: 'center top', at: 'center top+20px', of: window } );

	var callback = function(html) {
		// Handle response errors
		if(typeof html === 'object' && html.status) {
			genericAjaxPopupClose($popup);

		} else {
			$popup.closest('.ui-dialog').focus();
			
			// Set the content
			$popup.html(html);

			// Trigger event
			setTimeout(function() {
				$popup.trigger('popup_open');
			},0);

			// Callback
			try { cb(html); } catch(e) { }
		}
	};
	
	let hookError = function() {
		genericAjaxPopupClose($popup);
	}

	if(null == request) {
		
	} else if('function' == typeof request) {
		request();
		
	} else if(request instanceof FormData) {
		request.set('layer', $layer);
		genericAjaxPost(request, '', null, callback, {
			'error': hookError
		});
	} else {
		request += '&layer=' + $layer;
		genericAjaxGet('', request, callback, {
			'error': hookError
		});
	}

	return $popup;
}

// [TODO] Deprecate this
function genericAjaxPopupPostCloseReloadView($layer, frm, view_id, has_output, $event) {
	var has_view = (null != view_id && view_id.length > 0 && $('#view'+view_id).length > 0) ? true : false;
	if(null == has_output)
		has_output = false;

	if(has_view)
		$('#view'+view_id).fadeTo("fast", 0.2);
	
	genericAjaxPost(frm,'','',
		function(html) {
			if(has_view && has_output) { // Reload from post content
				if(html.length > 0)
					$('#view'+view_id).html(html);
			} else if (has_view && !has_output) { // Reload from view_id
				genericAjaxGet('view'+view_id, 'c=internal&a=invoke&module=worklists&action=refresh&id=' + view_id);
			}

			if(has_view)
				$('#view'+view_id).fadeTo("fast", 1.0);

			var $popup;

			if(null == $layer) {
				$popup = genericAjaxPopupFind('#'+frm);
			} else {
				$popup = genericAjaxPopupFetch($layer);
			}
			
			if(null != $popup) {
				$popup.trigger('popup_saved');
				genericAjaxPopupClose($popup, $event);
			}
		}
	);
}

function genericAjaxGet(divRef,args,cb,options) {
	var div = null;

	// Polymorph div
	if(divRef instanceof jQuery)
		div = divRef;
	else if(typeof divRef=="string" && divRef.length > 0)
		div = $('#'+divRef);
	
	// Allow custom options
	if(null == options)
		options = { };
	
	options.type = 'GET';
	options.url = DevblocksAppPath+'ajax.php?'+args;
	options.cache = false;

	if(null != div) {
		div.fadeTo("fast", 0.2);
		
		options.success = function(html) {
			if(null != div) {
				div.html(html);
				div.fadeTo("fast", 1.0);
				
				if(div.is('DIV[id^=view]'))
					div.trigger('view_refresh');
			}
		};
	}
	
	if(null == options.headers)
		options.headers = {};
		
	var $ajax = $.ajax(options);
	
	$ajax.fail(function(err) {
		Devblocks.clearAlerts();
		
		if(null != div) {
			div.html('').fadeIn();
		}
		
		if(401 === err.status) {
			let $alert = Devblocks.createAlert('', 'error', 0);
			let $a = $('<b/>').css('margin-right', '0.5em').text('Your session has expired.');
			let $b = $('<a/>').attr('href', window.location.href).text('Please log back in.');
			$alert.append($a).append($b);
			
		} else if(403 === err.status) {
			let $alert = Devblocks.createAlert('', 'error', 0);
			let $a = $('<b/>').css('margin-right','0.5em').text('Access denied.');
			let $b = $('<a/>').attr('href',window.location.href).text('Did your session expire?');
			$alert.append($a).append($b);
			
		} else if(404 === err.status) {
			let $alert = Devblocks.createAlert('', 'error', 0);
			let $a = $('<b/>').css('margin-right','0.5em').text('The requested resource was not found.');
			$alert.append($a);
			
		} else if(504 === err.status) {
			let $alert = Devblocks.createAlert('', 'error', 0);
			let $a = $('<b/>').css('margin-right','0.5em').text('The request timed out.');
			$alert.append($a);
		}
		
		if(typeof options.error == 'function') {
			options.error(err);
		}
	});
	
	if(typeof cb == 'function') {
		$ajax.done(cb);
	}
}

function genericAjaxPost(formRef,divRef,args,cb,options) {
	var div = null;
	
	// Polymorph div
	if(divRef instanceof jQuery)
		div = divRef;
	else if(typeof divRef=="string" && divRef.length > 0)
		div = $('#'+divRef);
	
	// Allow custom options
	if(null == options)
		options = { };

	// Polymorph to FormData
	if(formRef instanceof FormData) {
		// It's what we want

	} else if(formRef instanceof jQuery) {
		formRef = new FormData($(formRef)[0]);

	} else if(typeof formRef=="object") {
		var formData = new FormData();
		Devblocks.objectToFormData(formRef, formData);
		formRef = formData;

	} else if(typeof formRef=="string" && formRef.length > 0) {
		var $ref = $('#' + formRef);

		if(0 === $ref.length) {
			formData = null;
		} else {
			formRef = new FormData($ref[0]);
		}
	} else {
		formRef = null;
	}

	// If we couldn't make a FormData object, bail out
	if(!(formRef instanceof FormData)) {
		Devblocks.createAlertError('There was an issue sending your request to the server.');
		return false;
	}

	options.processData = false;
	options.contentType = false;
	options.data = formRef;

	var url = DevblocksAppPath+'ajax.php';

	if(formRef.has && formRef.get) {
		if (formRef.has('_log')) {
			url += '?_log=' + encodeURIComponent(formRef.get('_log').toString());
			formRef.delete('_log');

		} else {
			if (formRef.has('c')) {
				url += '?_log=' + encodeURIComponent(formRef.get('c').toString());

				if (formRef.has('a')) {
					url += '.' + encodeURIComponent(formRef.get('a').toString());

					if ('invoke' === formRef.get('a')) {
						url += '.' + encodeURIComponent(formRef.get('module').toString());
						url += '.' + encodeURIComponent(formRef.get('action').toString());

						if (formRef.has('id')) {
							url += '.' + encodeURIComponent(formRef.get('id').toString());
						}
					} else if ('invokeTab' === formRef.get('a')) {
						url += '.' + encodeURIComponent(formRef.get('tab_id').toString());
						url += '.' + encodeURIComponent(formRef.get('action').toString());

						if (formRef.has('id')) {
							url += '.' + encodeURIComponent(formRef.get('id').toString());
						}
					} else if ('pages' === formRef.get('c') && 'renderWorklist' === formRef.get('a')) {
						url += '.' + encodeURIComponent(formRef.get('list_id').toString());
					}
				}
			}
		}
	}

	options.type = 'POST';
	options.url = url;
	options.cache = false;

	if(null != div) {
		div.fadeTo("fast", 0.2);
		
		options.success = function(html) {
			if(null != div && div instanceof jQuery) {
				div.html(html);
				div.fadeTo("fast", 1.0);
				
				if(div.is('DIV[id^=view]'))
					div.trigger('view_refresh');
			}
		};
	}
	
	if(null == options.headers)
		options.headers = {};
		
	options.headers['X-CSRF-Token'] = $('meta[name="_csrf_token"]').attr('content');
	
	var $ajax = $.ajax(options);
	
	$ajax.fail(function(err) {
		Devblocks.clearAlerts();
		hideLoadingPanel();
		
		if(null != div) {
			div.html('').fadeIn();
		}
		
		if(401 === err.status) {
			let $alert = Devblocks.createAlert('', 'error', 0);
			let $a = $('<b/>').css('margin-right', '0.5em').text('Your session has expired.');
			let $b = $('<a/>').attr('href', window.location.href).text('Please log back in.');
			$alert.append($a).append($b);
			
		} else if(403 === err.status) {
			let $alert = Devblocks.createAlert('', 'error', 0);
			let $a = $('<b/>').css('margin-right','0.5em').text('Access denied.');
			let $b = $('<a/>').attr('href',window.location.href).text('Did your session expire?');
			$alert.append($a).append($b);
			
		} else if(404 === err.status) {
			let $alert = Devblocks.createAlert('', 'error', 0);
			let $a = $('<b/>').css('margin-right','0.5em').text('The requested resource was not found.');
			$alert.append($a);
			
		} else if(504 === err.status) {
			let $alert = Devblocks.createAlert('', 'error', 0);
			let $a = $('<b/>').css('margin-right','0.5em').text('The request timed out.');
			$alert.append($a);
		}
		
		if(typeof options.error == 'function') {
			options.error(err);
		}
	});

	if(typeof cb == 'function') {
		$ajax.done(cb);
	}
}