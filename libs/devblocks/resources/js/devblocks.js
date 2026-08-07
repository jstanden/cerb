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
						
						// Reload the CerbUI.Tabs panel this form lives in
						if(window.CerbUI && CerbUI.Tabs)
							CerbUI.Tabs.fromPanel($frm[0])?.refresh();
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
			.appendTo($alerts)
			;

		// Leave with a CSS transition (.cerb-alert--in off); a timer falls back when transitionend
		// can't fire (reduced-motion = no transition).
		var removeAlert = function() {
			$alert.removeClass('cerb-alert--in');

			var el = $alert[0], done = false;
			var finish = function() {
				if(done) return;
				done = true;
				$alert.remove();
			};

			el.addEventListener('transitionend', finish);
			setTimeout(finish, 600);
		};

		$('<span class="cerb-alert-close"><span class="cerb-icons cerb-icon-circle-remove"></span></span>')
			.on('click', function(e) {
				removeAlert();
			})
			.appendTo($alert)
			;

		if(style != undefined)
			$alert
				.addClass('cerb-alert-' + style)
				;

		// Reveal on the next frame so the enter transition runs.
		requestAnimationFrame(function() { $alert.addClass('cerb-alert--in'); });

		if(parseInt(duration) > 0) {
			setTimeout(removeAlert, parseInt(duration));
		}

		return $alert;
	};

	// The default AJAX failure UI, shared by genericAjaxGet()/genericAjaxPost(). Extracted so a caller can
	// install its own `.fail()` (the `fail` option) for the statuses it expects — e.g. an interaction's queue
	// worker sidecar, which is designed to outlive the gateway's request timeout — and delegate here for
	// everything else.
	this.ajaxFail = function(err, div) {
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
	};

	// A single reused CerbUI.Tooltip for automation callouts: a floating panel pinned to a DOM element,
	// with an arrow pointing at it, dismissed by click or outside-click. Reused (not per-callout) because
	// hiding a Tooltip keeps its panel in the DOM — a fresh instance each time would leak one.
	this._tooltip = undefined;

	// Reopen a parked worker interaction by its continuation token (from the command bar's "resume" rows). Mirrors
	// the await popup-open path in $.fn.cerbBotTrigger, but posts `resumeInteraction` instead of starting anew.
	this.resumeInteraction = function(token, options) {
		options = options || {};

		var $trigger = $('<a class="cerb-bot-trigger"/>');
		var layer = Devblocks.uniqueId();

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'automation');
		formData.set('action', 'resumeInteraction');
		formData.set('continuation_token', token);
		formData.set('layer', layer);

		// The launcher asking to reopen this. The server requires it to derive the SAME `resume_scope` the
		// continuation was stamped with at launch, so a chat can only reopen on a surface that can drive it.
		if(options.caller && options.caller.name) {
			formData.set('caller[name]', options.caller.name);

			Object.keys(options.caller.params || {}).forEach(function(k) {
				formData.set('caller[params][' + k + ']', options.caller.params[k]);
			});
		}

		// An inline host (an editor's agent pane) renders the bare panel into its own container instead of a
		// popup — the server branches on this too.
		var $target = (options.target && options.target.html) ? options.target : null;

		if($target)
			formData.set('interaction_style', 'inline');

		genericAjaxPost(formData, null, null, function(json) {
			if('object' != typeof json || !json.hasOwnProperty('exit'))
				return;

			if('await' === json.exit && $target) {
				var $html = $('<div/>')
					.on('cerb-interaction-reset', function(e) {
						e.stopPropagation();
						if('function' == typeof options.reset)
							options.reset($.Event(e));
					})
					.on('cerb-interaction-done', function(e) {
						e.stopPropagation();
						if('function' == typeof options.done)
							options.done($.Event('cerb-interaction-done', { trigger: $trigger, eventData: e.eventData }));
					})
					.html(json.html)
				;

				// ⚠ The load-bearing line. A resumed chat's `uiCommand` awaits invoke the host's editor through
				// this bridge; without it they return an EMPTY result with no error anywhere, so the agent reads
				// a blank editor and the failure looks like a bad model rather than a missing wire.
				// Same contract as cerbBotTrigger's inline launch.
				if('function' == typeof options.command)
					$html.find('form.cerb-form-builder').each(function() { this._cerbInteractionCommand = options.command; });

				$target.html($html);
				return;
			}

			if('await' === json.exit) {
				var popup_width = options.width || '50%';
				var $popup = genericAjaxPopup(layer, null, null, options && options.modal, popup_width);

				$popup
					.on('cerb-interaction-reset', function(e) {
						e.stopPropagation();
						if(options && options.reset && 'function' == typeof options.reset)
							options.reset($.Event(e));
					})
					.on('cerb-interaction-done', function(e) {
						e.stopPropagation();
						if(options && options.done && 'function' == typeof options.done)
							options.done($.Event('cerb-interaction-done', { trigger: $trigger, eventData: e.eventData }));
						genericAjaxPopupClose($popup);
					})
					.on('peek_aborted', function(e) {
						e.stopPropagation();
						if(options && options.abort && 'function' == typeof options.abort)
							options.abort($.Event('cerb-interaction-done', { trigger: $trigger, eventData: {} }));
					})
				;

				$popup.html(json.html);
				Devblocks.decorateInteractionDialog($popup, { label: options.label });

				setTimeout(function() {
					$popup.trigger('popup_open');
				}, 0);

			} else if('error' === json.exit) {
				Devblocks.createAlert(json.error || "That conversation can't be resumed.", 'error');
			}
		});
	};

	// Give an interaction popup its conversation affordances: a friendly title from the launcher label, no
	// dirty-close warning (the pause/end prompt is the second step), and a manual-close interceptor. Closing by
	// hand (x / Esc) offers pause-vs-end instead of silently abandoning; a programmatic close (the automation
	// finished, or our own pause/end) passes straight through.
	this.decorateInteractionDialog = function($popup, options) {
		options = options || {};

		if(!window.CerbUI || !CerbUI.Dialog)
			return;

		var dlg = CerbUI.Dialog.from($popup[0]);

		if(!dlg)
			return;

		dlg.opts.closeWarnOnUnsavedChanges = false;

		if(options.label)
			dlg.setTitle(options.label);

		var origOnClose = dlg.opts.onClose;

		dlg.opts.onClose = function() {
			// Only a RESUMABLE interaction closed BY HAND (x / Esc) offers pause-vs-end and vetoes until they pick.
			// Everything else just closes. Resumability is decided at render time and stored on the continuation —
			// never enforced at close, since a browser/tab close fires no handler at all. A non-resumable
			// interaction simply isn't listed and ages out; there's nothing to do on close.
			if(!dlg._programmaticClose && !dlg._closeMenuOpen
					&& $popup.find('input[name="__cerb_interaction_resumable"]').length) {
				Devblocks.promptInteractionClose(dlg, $popup);
				return false;
			}

			return origOnClose ? origOnClose.apply(this, arguments) : undefined;
		};
	};

	// When a worker closes an interaction by hand, drop a small menu on the (x) button — Pause (keep it in the
	// Resume list) or End (terminate) — instead of a jarring modal. The dialog's own Esc-close is suppressed while
	// the menu is up, and a following Esc pauses (the safe default). The saved name defaults to the dialog title
	// (renaming an interaction is a separate, later affordance).
	this.promptInteractionClose = function(dlg, $popup) {
		var token = $popup.find('input[name="continuation_token"]').val() || '';

		// No token or no Menu component -> nothing to persist; just close.
		if(!token || !window.CerbUI || !CerbUI.Menu) {
			$popup.dialog('close');
			return;
		}

		var anchor = dlg.el.querySelector('.cerb-ui-dialog--btn[aria-label="Close"]') || dlg.el;
		var settled = false;

		// While the menu is up, Escape pauses (the safe default). Handled in capture so it beats the menu's own
		// Esc-dismiss and any focused input still in the interaction.
		var onKeyCapture = function(e) {
			if('Escape' === e.key) {
				e.stopImmediatePropagation();
				e.preventDefault();
				dispose('pause');
			}
		};

		var restore = function() {
			dlg._closeMenuOpen = false;
			dlg.opts.closeOnEscape = true;
			document.removeEventListener('keydown', onKeyCapture, true);
		};

		var dispose = function(disposition) {
			if(settled) return;
			settled = true;
			restore();
			try { menu.close(); } catch(e) {}

			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'automation');
			formData.set('action', 'disposeInteraction');
			formData.set('continuation_token', token);
			formData.set('disposition', disposition);
			formData.set('name', dlg.opts.title || '');

			genericAjaxPost(formData, null, null, function() {
				$popup.dialog('close'); // programmatic -> the wrapped onClose lets it through

				// Point the eye at where the paused interaction now lives (the command-bar button).
				if('pause' === disposition)
					Devblocks.flashInteractionButton();
			});
		};

		var ul = document.createElement('ul');

		[
			{ disposition: 'pause', icon: 'pause', label: 'Pause' },
			{ disposition: 'end',   icon: 'trash', label: 'End' },
		].forEach(function(it) {
			var li = document.createElement('li');
			li.setAttribute('data-disposition', it.disposition);
			li.setAttribute('data-icon', it.icon);
			li.textContent = it.label;
			ul.appendChild(li);
		});

		var menu = new CerbUI.Menu(ul, {
			onSelect: function(renderedLi, sourceLi) {
				dispose(sourceLi.getAttribute('data-disposition'));
			},
			onRenderItem: function(renderedLi, sourceLi) {
				var icon = document.createElement('span');
				icon.className = 'cerb-icons cerb-u-mr-1 cerb-icon-' + sourceLi.dataset.icon;
				icon.setAttribute('aria-hidden', 'true');
				renderedLi.insertBefore(icon, renderedLi.firstChild);
			},
			onClose: function() {
				// Dismissed without a pick (an outside click) -> leave the interaction as-is.
				restore();
			},
		});

		dlg._closeMenuOpen = true;
		dlg.opts.closeOnEscape = false; // read live per-keydown, so the dialog stops self-closing on Esc
		document.addEventListener('keydown', onKeyCapture, true);

		menu.open(anchor);

		// Take focus off any interaction input so the keyboard drives the menu, then pre-highlight the first item.
		if(document.activeElement && document.activeElement !== document.body && typeof document.activeElement.blur === 'function')
			document.activeElement.blur();

		menu.moveActive(1);
	};

	// A quick pulse of the command-bar button, so a just-paused interaction's new home is obvious.
	this.flashInteractionButton = function() {
		var btn = document.getElementById('bot-chat-button');

		if(!btn || typeof btn.animate !== 'function')
			return;

		btn.animate(
			[
				{ transform: 'scale(1)' },
				{ transform: 'scale(1.3)' },
				{ transform: 'scale(1)' },
			],
			{ duration: 450, iterations: 2, easing: 'ease-in-out' }
		);
	};

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

			// Selector may not exist in this document (e.g. an explore callout targets
			// the page inside the iframe, not the top window) — skip rather than throw.
			if($target.length) {
				if(!$target.visible()) {
					$target[0].scrollIntoView();
				}

				let message = eventData.return.callout.message;
				let my = eventData.return.callout.my;
				let at = eventData.return.callout.at;

				if(undefined !== message) {
					if(undefined === Devblocks._tooltip)
						Devblocks._tooltip = new CerbUI.Tooltip( { gap: 0 } );

					// Pass the message as a text node (jQuery UI escaped the title; keep that posture).
					Devblocks._tooltip.anchor(document.createTextNode(message), $target[0], { my: my, at: at, interactive: true });
				}
			}
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
			
			jobs.push(CerbUI.utils.apply($instance.loadScript.bind($instance), url));
		});
		
		CerbUI.utils.parallelLimit(jobs, 2, function(err, json) {
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
				
				jobs.push(CerbUI.utils.apply($instance.loadStylesheet.bind($instance), url));
			});
		}
		
		if(resources.hasOwnProperty('js')) {
			if(!Array.isArray(resources.js))
				return callback(false);
			
			resources.js.forEach(function(url) {
				if(url.substring(0,1) === '/')
					url = DevblocksWebPath + url.substring(1);
				
				jobs.push(CerbUI.utils.apply($instance.loadScript.bind($instance), url));
			});
		}
		
		CerbUI.utils.parallelLimit(jobs, 2, function(err, json) {
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

// CerbUI.Dialog pass-through shim for $.fn.dialog (transitional — Step 7 of the jQuery-UI -> Cerb UI migration).
// Popups opened via genericAjaxPopup() are backed by CerbUI.Dialog, not jQuery-UI. This defines $.fn.dialog so the
// legacy .dialog(...) calls those popups' templates still make (mostly .dialog('option','title',X) inside popup_open,
// plus the genericAjaxPopupClose/Destroy helpers) route to the CerbUI instance. jQuery-UI has been removed, so this
// IS the only $.fn.dialog now — every app popup is CerbUI. CerbUI loads after us and is resolved at call time, so the
// load order is fine. (The captured _origDialog is undefined now that jQuery-UI is gone; the fallthrough is guarded.)
(function() {
	if(typeof $ === 'undefined' || !$.fn)
		return;

	let _origDialog = $.fn.dialog;

	$.fn.dialog = function() {
		let inst = (this[0] && window.CerbUI && CerbUI.Dialog) ? CerbUI.Dialog.from(this[0]) : null;

		if(inst) {
			let op = arguments[0], key = arguments[1], val = arguments[2];

			if('option' === op && 'title' === key) {
				inst.setTitle(val);
			} else if('option' === op && 'close' === key && false === val) {
				inst.opts.closable = false; // peek_error / merge_error: keep the popup from closing
			} else if('close' === op) {
				// Legacy programmatic closes (genericAjaxPopupClose, e.g. after a successful save) never prompted —
				// only the Esc handler did. Clear the dirty flag so the close doesn't trip CerbUI's discard guard.
				inst.markClean();
				inst._programmaticClose = true; // code-driven close -> onClose suppresses its own peek_aborted
				inst.close();
			} else if('destroy' === op) {
				inst.destroy();
			} else if('open' === op) {
				inst.open();
			} else if('isOpen' === op) {
				return inst.isOpen();
			}
			// resizable / minHeight / closeOnEscape and other legacy options -> harmless no-ops on CerbUI
			return this;
		}

		// No CerbUI dialog backs this element. jQuery-UI is no longer bundled (every app popup is CerbUI), so there's
		// nothing to fall through to; call the original only on the off chance some plugin still provides it.
		if(typeof _origDialog === 'function')
			return _origDialog.apply(this, arguments);
		return this;
	};
})();

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

// Opens an AJAX popup backed by CerbUI.Dialog (the jQuery-UI popup body has been removed). Same signature, same
// return ($popup), same legacy event/registry contract. The $.fn.dialog shim above lets each popup's existing
// template (popup_open -> .dialog('option','title',X), .dialog('close'), and the genericAjaxPopupFind/Close/Destroy
// helpers) work unchanged against the CerbUI instance.
function genericAjaxPopup($layer,request,target,modal,width,cb) {
	// Reuse: re-open in the same screen position as the current dialog for this layer (callers re-bind their own
	// listeners via popup_open, so we only need to carry the position across — not jQuery-UI's manual listener copy).
	var reuse_position = null;

	if(target === 'reuse') {
		var $prev = genericAjaxPopupFetch($layer);
		var prev_inst = (null != $prev && $prev.length && window.CerbUI && CerbUI.Dialog) ? CerbUI.Dialog.from($prev[0]) : null;
		if(prev_inst && prev_inst.el) {
			var left = parseInt(prev_inst.el.style.left, 10);
			var top = parseInt(prev_inst.el.style.top, 10);
			if(!isNaN(left) && !isNaN(top))
				reuse_position = { x: left, y: top };
		}
		target = null;
	}

	// Reset (if exists) — closes + tears down any prior dialog for this layer (fires its popup_close).
	genericAjaxPopupDestroy($layer);

	// Width: an explicit '<n>%' (and the no-width default of 80%) is passed through as a string so CerbUI keeps it
	// relative and reflows it on viewport resize; an explicit number stays fixed px (legacy clamp: min 500, capped at
	// viewport-30). The default is also capped at 1400px so it doesn't sprawl on ultrawide monitors.
	var opt_width;
	var opt_width_cap = null;

	if(typeof width == 'string' && width.substr(-1) == '%') {
		opt_width = width; // explicit % — relative, uncapped (the caller's choice)

	} else if(undefined == width || null == width) {
		opt_width = '80%'; // default — relative (mobile is 95%), capped below
		opt_width_cap = 1400;

	} else {
		width = parseInt(width, 10); // explicit px — fixed

		if(width < 500)
			width = 500;

		if(width > window.innerWidth)
			width = window.innerWidth - 30;

		opt_width = width;
	}

	// Position: reuse > element anchor > default (center, near top — CerbUI's default, matching legacy "center top+35").
	var opt_position = reuse_position;

	if(null == opt_position && null != target && !(typeof target == 'object' && null != target.my)) {
		// An element (or jQuery) anchor. Legacy pinned the popup's bottom-right to the anchor's top-left; we
		// approximate by placing the popup's top-left near the anchor (refined per-caller during the sweep).
		var $anchor = (target instanceof jQuery) ? target : $(target);
		if($anchor.length) {
			var rect = $anchor[0].getBoundingClientRect();
			opt_position = { x: Math.round(rect.left + window.scrollX), y: Math.round(rect.bottom + window.scrollY) };
		}
	}

	// Build (or reuse) the content element — same #popup{layer}.devblocks-popup that the helpers resolve against.
	var $popup = $("#popup"+$layer);

	if(0 === $popup.length) {
		$popup = $('<div/>')
			.attr('id', 'popup' + $layer)
			.addClass('devblocks-popup')
			.appendTo($('body'))
			;
	}

	$popup.attr('data-layer', $layer);

	// Persist
	genericAjaxPopupRegister($layer, $popup);

	// Search / links popups never warn on close (matches the legacy global Esc-handler exclusion).
	var warn_unsaved = !/^popup(search|links_)/.test($popup.attr('id'));

	var dlg = new CerbUI.Dialog($popup[0], {
		title: "Loading...",
		modal: (null != modal) ? modal : false,
		width: opt_width,
		widthCap: opt_width_cap, // caps the relative default at 1400px (null for explicit % / fixed px)
		autoHeight: true, // match the legacy resizeStop: an n/s drag refits height to content, keeping the new width
		position: opt_position,
		namespace: $layer,
		closeWarnOnUnsavedChanges: warn_unsaved,
		onClose: function() {
			// Legacy event contract: peek_aborted only on a user-initiated close (x / Esc); the programmatic path
			// (genericAjaxPopupClose/Destroy via the shim) already fired any event it wanted and flags itself.
			var programmatic = dlg._programmaticClose;
			dlg._programmaticClose = false;

			if(!programmatic)
				$popup.triggerHandler($.Event('peek_aborted'));

			$popup.triggerHandler($.Event('popup_close'));
			$('#devblocksPopups').removeData($layer);
		}
	});

	// Tear the DOM down once closed (CerbUI unwraps + restores the content element; drop the leftover #popup div so
	// re-opening this layer builds fresh — matching legacy's remove-on-close).
	$popup[0].addEventListener('cerb-ui-dialog:close', function() {
		dlg.destroy();
		$popup.remove();
	}, { once: true });

	// Show a spinner while loading (CerbUI's own; replaces the hand-rolled Devblocks.getSpinner append).
	var $loading = $('<div class="cerb-ui-dialog--loading"/>');
	if(window.CerbUI && CerbUI.Spinner)
		$loading.append(CerbUI.Spinner.create(dlg.opts.spinner));
	$popup.append($loading);

	dlg.open();

	var callback = function(html) {
		// Handle response errors
		if(typeof html === 'object' && html.status) {
			genericAjaxPopupClose($popup);

		} else {
			// Set the content — jQuery .html() runs the response's <script nonce> under the page CSP.
			$popup.html(html);

			// Convention: a top-level element of the fetched content may declare the dialog title via
			// data-cerb-dialog-title — read it here so templates don't need a popup_open .dialog('option','title',…)
			// call (and can drop the escape:'javascript' nofilter JS-string dance; an HTML attribute auto-escapes).
			// setTitle() assigns via textContent (cerb-ui/dialog.js), so this is XSS-safe.
			var $titled = $popup.children('[data-cerb-dialog-title]').first();
			if($titled.length)
				dlg.setTitle($titled.attr('data-cerb-dialog-title'));

			// The response grew the dialog — re-pin its top + lengthen the page to reach it.
			dlg.reflow();

			// Trigger event (deferred so inline scripts in the loaded partial see the DOM).
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
		// A caller-supplied `fail` REPLACES the default failure UI; it delegates to Devblocks.ajaxFail(err, div)
		// for the statuses it doesn't handle itself. (`options.error` keeps its additive meaning.)
		if(typeof options.fail == 'function')
			return options.fail(err, div);

		Devblocks.ajaxFail(err, div);

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
		// A caller-supplied `fail` REPLACES the default failure UI; it delegates to Devblocks.ajaxFail(err, div)
		// for the statuses it doesn't handle itself. (`options.error` keeps its additive meaning.)
		if(typeof options.fail == 'function')
			return options.fail(err, div);

		Devblocks.ajaxFail(err, div);

		if(typeof options.error == 'function') {
			options.error(err);
		}
	});

	if(typeof cb == 'function') {
		$ajax.done(cb);
	}
}