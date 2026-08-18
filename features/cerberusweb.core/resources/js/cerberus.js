var cAjaxCalls = function() {
	this.viewCloseTickets = function(view_id,mode) {
		var formName = 'viewForm'+view_id;
		var $frm = $('#' + formName);

		if(0 === $frm.length)
			return;

		showLoadingPanel();

		var formData = new FormData($frm[0]);

		switch(mode) {
			case 1: // spam
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'ticket');
				formData.set('action', 'viewMarkSpam');
				formData.set('view_id', view_id);

				genericAjaxPost(formData, '', '', function() {
					hideLoadingPanel();
					genericAjaxGet('view' + view_id,'c=internal&a=invoke&module=worklists&action=refresh&id=' + encodeURIComponent(view_id));
				});
				break;
			case 2: // delete
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'ticket');
				formData.set('action', 'viewMarkDeleted');
				formData.set('view_id', view_id);

				genericAjaxPost(formData, '', '', function() {
					hideLoadingPanel();
					genericAjaxGet('view' + view_id,'c=internal&a=invoke&module=worklists&action=refresh&id=' + encodeURIComponent(view_id));
				});
				break;
			default: // close
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'ticket');
				formData.set('action', 'viewMarkClosed');
				formData.set('view_id', view_id);

				genericAjaxPost(formData, '', '', function() {
					hideLoadingPanel();
					genericAjaxGet('view' + view_id,'c=internal&a=invoke&module=worklists&action=refresh&id=' + encodeURIComponent(view_id));
				});
				break;
		}
	};
	
	this.viewAddQuery = function(view_id, query, replace) {
		var formData = new FormData();
		formData.set('c', 'internal');
		formData.set('a', 'invoke');
		formData.set('module', 'worklists');
		formData.set('action', 'addFilter');
		formData.set('id', view_id);
		formData.set('add_mode', 'query');
		formData.set('replace', replace);
		formData.set('query', query);

		genericAjaxPost(formData, null, null, function(o) {
			var $view_filters = $('#viewCustomFilters'+view_id);
			
			if(0 !== $view_filters.length) {
				$view_filters.html(o);
				$view_filters.trigger('view_refresh')
			}
		});
	};
	
	this.viewAddFilter = function(view_id, field, oper, values, replace) {
		var formData = new FormData();
		formData.set('c', 'internal');
		formData.set('a', 'invoke');
		formData.set('module', 'worklists');
		formData.set('action', 'addFilter');
		formData.set('id', view_id);
		formData.set('replace', replace);
		formData.set('field', field);
		formData.set('oper', oper);

		Devblocks.objectToFormData(values, formData);

		genericAjaxPost(formData, null, null, function(o) {
			var $view_filters = $('#viewCustomFilters'+view_id);
			
			if(0 !== $view_filters.length) {
				$view_filters.html(o);
				$view_filters.trigger('view_refresh');
			}
		});
	};
	
	this.viewRemoveFilter = function(view_id, fields) {
		var formData = new FormData();
		formData.set('c', 'internal');
		formData.set('a', 'invoke');
		formData.set('module', 'worklists');
		formData.set('action', 'addFilter');
		formData.set('id', view_id);

		for(var field in fields) {
			formData.append('field_deletes[]', fields[field]);
		}
		
		genericAjaxPost(formData, null, null, function(o) {
			var $view_filters = $('#viewCustomFilters'+view_id);
			
			if(0 !== $view_filters.length) {
				$view_filters.html(o);
				$view_filters.trigger('view_refresh')
			}
		});
	};
	
	this.viewUndo = function(view_id, is_dismissed) {
		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'ticket');
		formData.set('action', 'viewUndo');
		formData.set('view_id', view_id);

		if(is_dismissed) {
			formData.set('clear', '1');
			genericAjaxPost(formData, null, null);

		} else {
			showLoadingPanel();

			genericAjaxPost(formData, null, null, function() {
				hideLoadingPanel();
				genericAjaxGet('view' + view_id,'c=internal&a=invoke&module=worklists&action=refresh&id=' + encodeURIComponent(view_id));
			});
		}
	};

}

var ajax = new cAjaxCalls();

(function ($) {
	
	// Abstract bot interaction trigger
	
	$.fn.cerbBotTrigger = function(options) {
		if(null == options)
			options = {};
		
		return this.each(function() {
			var $trigger = $(this);
			
			// Context
			
			$trigger.on('click', function(e) {
				e.stopPropagation();
				
				var startInteraction = async function() {
					let promise = new Promise((resolve, reject) => {
						var interaction_uri = $trigger.attr('data-interaction-uri');
						var interaction = $trigger.attr('data-interaction');
						var interaction_params = $trigger.attr('data-interaction-params');
						var behavior_id = $trigger.attr('data-behavior-id');

						var layer = Devblocks.uniqueId();

						var formData = new FormData();
						formData.set('c', 'profiles');
						formData.set('a', 'invoke');
						formData.set('module', 'automation');
						formData.set('action', 'startInteraction');

						formData.set('interaction', interaction);
						formData.set('browser[url]', window.location.href);

						if(null != interaction_uri)
							formData.set('interaction_uri', interaction_uri);

						if(null != behavior_id)
							formData.set('behavior_id', behavior_id);

						if(interaction_params && interaction_params.length > 0) {
							var parts = new URLSearchParams(interaction_params);

							for(var pair of parts.entries()) {
								if('[]' === pair[0].substr(-2)) {
									formData.append('params[' + pair[0].slice(0,-2) + '][]', pair[1]);
								} else {
									// Nested
									if(!pair[0].startsWith('[') && -1 !== pair[0].indexOf(']')) {
										let firstBracket = pair[0].indexOf('[');
										pair[0] = 'params[' + pair[0].substring(0, firstBracket) + ']'
											+ pair[0].substring(firstBracket)
										formData.set(pair[0], pair[1]);
									} else {
										formData.set('params[' + pair[0] + ']', pair[1]);
									}
								}
							}
						}

						// @deprecated
						$.each(this.attributes, function() {
							if('data-interaction-param-' === this.name.substring(0,23)) {
								formData.append('params[' + this.name.substring(23) + ']', this.value);
							}
						});

						// Caller
						if(options && options.caller && 'object' == typeof options.caller) {
							if(options.caller.name)
								formData.set('caller[name]', options.caller.name);

							if(options.caller.params && 'object' == typeof options.caller.params) {
								for(var k in options.caller.params) {
									formData.set('caller[params][' + k + ']', options.caller.params[k]);
								}
							}
						}

						// Give the callback an opportunity to append
						if(options && options.start && 'function' == typeof options.start) {
							options.start(formData);
						}

						// Is the interaction inline or a popup?

						if(options && options.target) {
							formData.set('interaction_style', 'inline');

							genericAjaxPost(formData, null, null, function(json) {
								// Polymorph old HTML responses
								if('string' == typeof json) {
									var html = json;

									json = {
										'exit': 'await',
										'html': html
									};
								}

								if('object' != typeof json)
									return reject();

								if(!json.hasOwnProperty('exit'))
									return reject();

								if('return' === json.exit || 'exit' === json.exit) {
									if(options && options.done && 'function' == typeof options.done) {
										options.done($.Event('cerb-interaction-done', { trigger: $trigger, eventData: json }));
									}
									
									if(json.return && json.return.clipboard) {
										resolve(json);
									} else {
										reject();
									}

								} else if('error' === json.exit) {
									if(options && options.error && 'function' == typeof options.error) {
										options.error($.Event('cerb-interaction-done', { trigger: $trigger, eventData: json }));
									}
									
									reject('Interaction error');
									
								} else if('await' === json.exit) {
									var $html = $('<div/>')
										.on('cerb-interaction-reset', function(e) {
											e.stopPropagation();
											if(options && options.reset && 'function' == typeof options.reset) {
												options.reset($.Event(e));
											}
										})
										.on('cerb-interaction-done', function(e) {
											e.stopPropagation();
											if(options && options.done && 'function' == typeof options.done) {
												options.done($.Event('cerb-interaction-done', { trigger: $trigger, eventData: e.eventData }));
											}
										})
										.html(json.html)
									;

									// Expose the host's `command` callback ON the interaction form so a `uiCommand` await can
									// invoke it SYNCHRONOUSLY (filling its hidden field before the sibling submit fires). The form
									// persists across the inner loop re-renders, so set it once here. uiCommand never submits —
									// only `submit` does.
									if(options && 'function' == typeof options.command)
										$html.find('form.cerb-form-builder').each(function() { this._cerbInteractionCommand = options.command; });

									if(options.target.html) {
										options.target.html($html);
									}
								}
							});

						} else {
							formData.set('layer', layer);

							// This returns JSON now to control the popup before it opens
							genericAjaxPost(formData, null, null, function(json) {
								// Polymorph old HTML responses
								if('string' == typeof json) {
									var html = json;

									json = {
										'exit': 'await',
										'html': html
									};
								}

								if('object' != typeof json)
									return reject();

								if(!json.hasOwnProperty('exit'))
									return reject();

								// Return right away without the popup
								if('return' === json.exit || 'exit' === json.exit) {
									if(options && options.done && 'function' == typeof options.done) {
										options.done($.Event('cerb-interaction-done', { trigger: $trigger, eventData: json }));
									}
									
									if(json.return && json.return.clipboard) {
										resolve(json);
									} else {
										reject('No clipboard data');
									}
									
								} else if('error' === json.exit) {
									if(options && options.error && 'function' == typeof options.error) {
										options.error($.Event('cerb-interaction-done', { trigger: $trigger, eventData: json }));
									}

									reject('Interaction error');

								// Open a blank popup and assign content
								} else if('await' === json.exit) {
									var popup_width = options.width || '50%';

									var $popup = genericAjaxPopup(layer, null, null, options && options.modal, popup_width);

									$popup
										.on('cerb-interaction-reset', function(e) {
											e.stopPropagation();

											if(options && options.reset && 'function' == typeof options.reset) {
												options.reset($.Event(e));
											}
										})
										.on('cerb-interaction-done', function(e) {
											e.stopPropagation();

											if(options && options.done && 'function' == typeof options.done) {
												options.done($.Event('cerb-interaction-done', { trigger: $trigger, eventData: e.eventData }));
											}

											genericAjaxPopupClose($popup);
										})
										.on('peek_aborted', function(e) {
											e.stopPropagation();

											if(options && options.abort && 'function' == typeof options.abort) {
												options.abort($.Event('cerb-interaction-done', { trigger: $trigger, eventData: { } }));
											}
										})
									;

									$popup.html(json.html);

									// The same bridge as the inline branch above, and a POPUP interaction needs it
									// just as much -- the command bar passes no `target`, so its agent chats come
									// through here. Without it a `uiCommand` await returns an EMPTY result with no
									// error anywhere, which reads as "the agent did nothing" rather than "nothing
									// was wired".
									if(options && 'function' == typeof options.command)
										$popup.find('form.cerb-form-builder').each(function() { this._cerbInteractionCommand = options.command; });

									Devblocks.decorateInteractionDialog($popup, { label: ($trigger.attr('data-interaction-label') || $trigger.text() || '').trim() });

									setTimeout(function() {
										$popup.trigger('popup_open');
									},0);
								}
							});
						}
					});
					
					return await promise;
				}

				// √: Firefox, Chrome, Opera, Edge
				if ('function' == typeof navigator?.clipboard.writeText && 'safari' !== Devblocks.getBrowserName()) {
					// Otherwise we need to call our promise async manually
					startInteraction().then(
						function(result) {
							if(result?.return?.clipboard) {
								navigator.clipboard.writeText(result.return.clipboard).then(
									function() { },
									function() { }
								);
							}
						},
						function(err) {
							// Failed the interaction
						}
					).catch(function(reason) {});
					
				// √ Safari
				} else if('function' == typeof navigator?.clipboard?.write) {
					navigator.clipboard.write([new ClipboardItem({
						'text/plain': startInteraction().then((result) => {
							return new Promise(async (resolve, reject) => {
								if(result?.return?.clipboard) {
									resolve(new Blob([result.return.clipboard], { type: 'text/plain'}));
								} else {
									reject();
								}
							});
						}).catch(function() {
						})
					})]).then(
						function() {
							// Wrote to clipboard
						},
						function(err) {
							// Failed
						}
					).catch(function(reason) { });
				}
			});
		});
	}

	// Abstract peeks
	
	$.fn.cerbPeekTrigger = function(options) {
		return this.each(function() {
			var $trigger = $(this);
			
			$trigger.on('click', function(evt) {
				evt.preventDefault();
				evt.stopPropagation();
				
				var context = $trigger.attr('data-context');
				var context_id = $trigger.attr('data-context-id');
				var layer = $trigger.attr('data-layer');
				var width = $trigger.attr('data-width') || null;
				var edit_mode = !!$trigger.attr('data-edit');
				
				if(null == width && 'object' == typeof options)
					width = options.width || null;
				
				var profile_url = $trigger.attr('data-profile-url');
				
				if(!profile_url && (evt.shiftKey || evt.metaKey))
					edit_mode = true;
				
				// Context
				if(!(typeof context == "string") || 0 === context.length)
					return;
				
				// Layer
				if(!(typeof layer == "string") || 0 === layer.length)
					//layer = "peek" + Devblocks.uniqueId();
					layer = $.md5(context + ':' + context_id + ':' + (edit_mode ? 'true' : 'false'));
				
				if(profile_url && (evt.shiftKey || evt.metaKey)) {
					window.open(profile_url, '_blank', 'noopener');
					return;
				}
				
				var peek_url = 'c=internal&a=invoke&module=records&action=showPeekPopup&context=' + encodeURIComponent(context) + '&context_id=' + encodeURIComponent(context_id);

				// View
				if(typeof options == 'object' && options.view_id)
					peek_url += '&view_id=' + encodeURIComponent(options.view_id);
				
				// Edit mode
				if(edit_mode) {
					peek_url += '&edit=' + encodeURIComponent($trigger.attr('data-edit'));
				}
				
				if(!width)
					width = '50%';
				
				// Open peek
				var $peek = genericAjaxPopup(layer,peek_url,null,false,width);
				
				var peek_open_event = $.Event('cerb-peek-opened');
				peek_open_event.peek_layer = layer;
				peek_open_event.peek_context = context;
				peek_open_event.peek_context_id = context_id;
				peek_open_event.popup_ref = $peek;
				$trigger.trigger(peek_open_event);
				
				$peek.on('peek_saved cerb-peek-saved', function(e) {
					var is_rebroadcast = e.type === 'cerb-peek-saved';
					var save_event = $.Event(e.type, e);
					save_event.type = 'cerb-peek-saved';
					save_event.context = context;
					save_event.is_rebroadcast = is_rebroadcast;
					$trigger.trigger(save_event);

					if(e.is_new) {
						var new_event = $.Event(e.type, e);
						new_event.type = 'cerb-peek-created';
						new_event.is_rebroadcast = is_rebroadcast;
						$trigger.trigger(new_event);
					}

					e.stopPropagation();
				});

				$peek.on('peek_deleted cerb-peek-deleted', function(e) {
					var is_rebroadcast = e.type === 'cerb-peek-deleted';
					var delete_event = $.Event(e.type, e);
					delete_event.type = 'cerb-peek-deleted';
					delete_event.context = context;
					delete_event.is_rebroadcast = is_rebroadcast;
					$trigger.trigger(delete_event);

					e.stopPropagation();
				});
				
				$peek.on('peek_aborted', function(e) {
					var abort_event = $.Event(e.type, e);
					abort_event.type = 'cerb-peek-aborted';
					abort_event.context = context;
					abort_event.is_rebroadcast = e.type === 'cerb-peek-aborted';
					$trigger.trigger(abort_event);

					e.stopPropagation();
				});
				
				$peek.on('cerb-links-changed', function(e) {
					var links_event = $.Event(e.type, e);
					links_event.type = 'cerb-peek-links-changed';
					links_event.context = context;
					links_event.is_rebroadcast = e.type === 'cerb-links-changed';
					$trigger.trigger(links_event);

					e.stopPropagation();
				});
				
				$peek.on('popup_close', function() {
					$trigger.trigger('cerb-peek-closed');
				});
			});
		});
	}

	// Open the queue_job peek for a background job and (optionally) refresh a worklist
	// when the peek is closed. Used by the worklist export modal and every per-context
	// bulk-update form to surface job progress in real-time without per-template duplication.
	window.cerbOpenQueueJobPeek = function(job_id, view_id) {
		if(!job_id) return;

		let $trigger = $('<a/>')
			.attr('data-context', 'cerb.contexts.queue.job')
			.attr('data-context-id', String(job_id))
			.css('display', 'none')
			.appendTo('body');

		$trigger
			.cerbPeekTrigger({ width: '600' })
			.on('cerb-peek-closed', function(event) {
				event.stopPropagation();
				$trigger.remove();
				if(view_id) {
					genericAjaxGet('view' + view_id,
						'c=internal&a=invoke&module=worklists&action=refresh&id=' + encodeURIComponent(view_id)
					);
				}
			})
			.trigger('click');
	};

	// Abstract searches
	
	$.fn.cerbSearchTrigger = function(options) {
		return this.each(function() {
			var $trigger = $(this);
			
			$trigger.click(function() {
				var context = $trigger.attr('data-context');
				var layer = $trigger.attr('data-layer');
				var query = $trigger.attr('data-query');
				var query_req = $trigger.attr('data-query-required');

				// Context
				if(!(typeof context == "string") || 0 === context.length)
					return;
				
				// Layer
				if(!(typeof layer == "string") || 0 === layer.length)
					layer = "search" + Devblocks.uniqueId();
				
				var search_url = 'c=search&a=openSearchPopup&context=' + encodeURIComponent(context) + '&id=' + layer;
				
				if(typeof query == 'string' && query.length > 0) {
					search_url = search_url + '&q=' + encodeURIComponent(query);
				}
				
				if(typeof query_req == 'string' && query_req.length > 0) {
					search_url = search_url + '&qr=' + encodeURIComponent(query_req);
				}
				
				// Open search
				var $peek = genericAjaxPopup(layer,search_url,null,false,'90%');
				
				$trigger.trigger('cerb-search-opened');
				
				$peek.on('popup_close', function(e) {
					$trigger.trigger('cerb-search-closed');
				});
			});
		});
	}

	
	// Select shortcuts
	
	$.fn.cerbSelectShortcuts = function(options) {
		if(undefined === options)
			options = {};
		
		return this.each(function() {
			let $select = $(this);
			
			if(null == options.attr)
				return;
			
			let indices = String($select.attr(options.attr)).split(',').reverse();
			
			for(let i in indices) {
				let $option = $select.find('> option').eq(indices[i]);
				
				$('<button/>')
					.attr('type', 'button')
					.text($option.text().toLowerCase())
					.on('click', function(e) {
						e.stopPropagation();
						$select.val($option.val());
					})
					.insertAfter($select)
				;
			}
		});
	}
	
}(jQuery));

// https://github.com/component/textarea-caret-position
(function () {
// We'll copy the properties below into the mirror div.
// Note that some browsers, such as Firefox, do not concatenate properties
// into their shorthand (e.g. padding-top, padding-bottom etc. -> padding),
// so we have to list every single property explicitly.
	var properties = [
		'direction',  // RTL support
		'boxSizing',
		'width',  // on Chrome and IE, exclude the scrollbar, so the mirror div wraps exactly as the textarea does
		'height',
		'overflowX',
		'overflowY',  // copy the scrollbar for IE

		'borderTopWidth',
		'borderRightWidth',
		'borderBottomWidth',
		'borderLeftWidth',
		'borderStyle',

		'paddingTop',
		'paddingRight',
		'paddingBottom',
		'paddingLeft',

		// https://developer.mozilla.org/en-US/docs/Web/CSS/font
		'fontStyle',
		'fontVariant',
		'fontWeight',
		'fontStretch',
		'fontSize',
		'fontSizeAdjust',
		'lineHeight',
		'fontFamily',

		'textAlign',
		'textTransform',
		'textIndent',
		'textDecoration',  // might not make a difference, but better be safe

		'letterSpacing',
		'wordSpacing',

		'tabSize',
		'MozTabSize'

	];

	var isBrowser = (typeof window !== 'undefined');
	var isFirefox = (isBrowser && window.mozInnerScreenX != null);

	function getCaretCoordinates(element, position, options) {
		if (!isBrowser) {
			throw new Error('textarea-caret-position#getCaretCoordinates should only be called in a browser');
		}

		var debug = options && options.debug || false;
		if (debug) {
			var el = document.querySelector('#input-textarea-caret-position-mirror-div');
			if (el) el.parentNode.removeChild(el);
		}

		// The mirror div will replicate the textarea's style
		var div = document.createElement('div');
		div.id = 'input-textarea-caret-position-mirror-div';
		document.body.appendChild(div);

		var style = div.style;
		var computed = window.getComputedStyle ? window.getComputedStyle(element) : element.currentStyle;  // currentStyle for IE < 9
		var isInput = element.nodeName === 'INPUT';

		// Default textarea styles
		style.whiteSpace = 'pre-wrap';
		if (!isInput)
			style.wordWrap = 'break-word';  // only for textarea-s

		// Position off-screen
		style.position = 'absolute';  // required to return coordinates properly
		if (!debug)
			style.visibility = 'hidden';  // not 'display: none' because we want rendering

		// Transfer the element's properties to the div
		properties.forEach(function (prop) {
			if (isInput && prop === 'lineHeight') {
				// Special case for <input>s because text is rendered centered and line height may be != height
				if (computed.boxSizing === "border-box") {
					var height = parseInt(computed.height);
					var outerHeight =
						parseInt(computed.paddingTop) +
						parseInt(computed.paddingBottom) +
						parseInt(computed.borderTopWidth) +
						parseInt(computed.borderBottomWidth);
					var targetHeight = outerHeight + parseInt(computed.lineHeight);
					if (height > targetHeight) {
						style.lineHeight = height - outerHeight + "px";
					} else if (height === targetHeight) {
						style.lineHeight = computed.lineHeight;
					} else {
						style.lineHeight = 0;
					}
				} else {
					style.lineHeight = computed.height;
				}
			} else {
				style[prop] = computed[prop];
			}
		});

		if (isFirefox) {
			// Firefox lies about the overflow property for textareas: https://bugzilla.mozilla.org/show_bug.cgi?id=984275
			if (element.scrollHeight > parseInt(computed.height))
				style.overflowY = 'scroll';
		} else {
			style.overflow = 'hidden';  // for Chrome to not render a scrollbar; IE keeps overflowY = 'scroll'
		}

		div.textContent = element.value.substring(0, position);
		// The second special handling for input type="text" vs textarea:
		// spaces need to be replaced with non-breaking spaces - http://stackoverflow.com/a/13402035/1269037
		if (isInput)
			div.textContent = div.textContent.replace(/\s/g, '\u00a0');

		var span = document.createElement('span');
		// Wrapping must be replicated *exactly*, including when a long word gets
		// onto the next line, with whitespace at the end of the line before (#7).
		// The  *only* reliable way to do that is to copy the *entire* rest of the
		// textarea's content into the <span> created at the caret position.
		// For inputs, just '.' would be enough, but no need to bother.
		span.textContent = element.value.substring(position) || '.';  // || because a completely empty faux span doesn't render at all
		div.appendChild(span);

		var coordinates = {
			top: span.offsetTop + parseInt(computed['borderTopWidth']),
			left: span.offsetLeft + parseInt(computed['borderLeftWidth']),
			height: parseInt(computed['lineHeight'])
		};

		if (debug) {
			span.style.backgroundColor = '#aaa';
		} else {
			document.body.removeChild(div);
		}

		return coordinates;
	}

	if (typeof module != 'undefined' && typeof module.exports != 'undefined') {
		module.exports = getCaretCoordinates;
	} else if(isBrowser) {
		window.getCaretCoordinates = getCaretCoordinates;
	}

}());