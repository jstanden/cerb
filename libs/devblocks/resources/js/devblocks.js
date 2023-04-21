function DevblocksClass() {
	this.audio = null;
	
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
		
		$frm
			.off('submit')
			.on('submit', function() {
			return false;
		});
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
		
		var $alerts = $('#cerb-alerts');
		
		var $alert = $('<div/>')
			.addClass('cerb-alert')
			.text(message)
			.hide()
			.appendTo($alerts)
			;
		
		$('<span class="cerb-alert-close"><span class="glyphicons glyphicons-remove"></span></span>')
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
						.prepend($('<span class="glyphicons glyphicons-circle-exclamation-mark" style="margin-right:5px;"></span>'))
				)
			)
		;
		
		var $status = $(target).html($html).show();
		
		animate = (null == animate || false !== animate);
		if(animate)
			$status.effect('slide',{ direction:'up', mode:'show' },250);
		
		return $status;
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
		
		if(eventData.return.hasOwnProperty('alert')) {
			Devblocks.createAlert(eventData.return['alert']);
		}

		// Open URLs in new tabs
		if(eventData.return.hasOwnProperty('open_link')) {
			var a = document.createElement('a');
			a.style.display = 'none';
			document.body.appendChild(a);
			a.href = eventData.return['open_link'];
			a.target = '_blank';
			a.click();
			a.remove();
		}
		
		// Open time tracking timer
		if(typeof timeTrackingTimer !== 'undefined' && eventData.return.hasOwnProperty('timer')) {
			timeTrackingTimer.play(eventData.return['timer']);
		}
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
		
		genericAjaxPost($frm, '', '', function(e) {
			$button.prop('disabled', false).fadeTo('fast', 1.0);
			$spinner.remove();
			
			if(!(typeof e == 'object'))
				return;
			
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
				
				event = new jQuery.Event('peek_error');
				event.error = e.error;
				$popup.triggerHandler(event);
				
				// Highlight the failing field
				if(e.field)
					$frm.find('[name=' + e.field + ']').focus();
			}
		});
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
					$('<style type="text/css">\n' + data + '</style>').appendTo('head');
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
	
	this.cerbCodeEditor = {
		insertMatchAndAutocomplete: function(editor, data) {
			delete data.completer;

			var pos = editor.getCursorPosition();
			var token = editor.session.getTokenAt(pos.row, pos.column);

			var suggestion = data;

			// If the suggestion and the current token start with a quote
			if(token && (token.type === 'text' || token.type === 'string')
				&& 'string' === typeof token.value
				&& token.value.substr(0,1) === '"'
				&& data.value.substr(0,1) === '"'
				) {
				suggestion = JSON.parse(JSON.stringify(suggestion));

				// If the suggestion and token both end with a quote, only keep one
				var end = (data.value.substr(-1,1) === '"' && token.value.substr(-1,1) === '"') ? 2 : 1;

				// Strip the leading quote
				suggestion.value = suggestion.value.substr(1, suggestion.value.length-end);
			}

			editor.completer.insertMatch(suggestion);

			if(data.suppress_autocomplete)
				return;
			
			// If we're inserting a field, trigger autocompletion
			if(
				(data.value && -1 !== data.value.indexOf(':'))
				|| (data.snippet && -1 !== data.snippet.indexOf(':'))
			) {
				setTimeout(function() {
					editor.commands.byName.startAutocomplete.exec(editor);
				}, 50)
			}
		},
		getKataTokenPath: function(pos, editor) {
			if(null == pos)
				pos = editor.getCursorPosition();

			var TokenIterator = require('ace/token_iterator').TokenIterator;
			var iter = new TokenIterator(editor.session, pos.row, pos.column);
			var results = [];

			var token = iter.getCurrentToken();
			var current_indent = null;
			
			if(null != token) {
				let token_column = null;
				let token_row = null;
				let currentTokenLine = null;
				
				try {
					token_column = iter.getCurrentTokenColumn();
					token_row = iter.getCurrentTokenRow();
					currentTokenLine = editor.session.getLine(token_row);
				} catch (e) {
					return [];
				}
				
				// If our previous token is a tag, and we're on the same line, autocomplete inline
				if(token.type === 'text' && 0 !== token_column) {
					iter.stepBackward();
					var prevToken = iter.getCurrentToken();
					var prevTokenLine = editor.session.getLine(iter.getCurrentTokenRow());

					// If we're a text token on the same row as our parent tag token 
					if(
						prevToken 
						&& prevToken.type === 'meta.tag' 
						&& iter.getCurrentTokenRow() === token_row
					) {
						current_indent = prevToken.value;
						iter.stepForward();

					// If we're a text token with a sibling tag token
					} else if(
						iter.getCurrentTokenRow() !== token_row
						&& Devblocks.cerbCodeEditor.lineIsKataField(iter.getCurrentTokenRow(), editor)
						&& Devblocks.cerbCodeEditor.linesAreSiblings(prevTokenLine, currentTokenLine)
					) {
						Devblocks.cerbCodeEditor.iterToKataParent(iter, editor);
					
					// If this isn't a blank line
					} else if(
						token.value.length > 0 
						&& 0 === token.value.trimStart().length
					) {
						current_indent = token.value;
						
					} else {
						iter.stepForward();
					}
				}

				do {
					try {
						token = iter.getCurrentToken();
						token_column = iter.getCurrentTokenColumn();
						token_row = iter.getCurrentTokenRow();
					} catch(e) {
						continue;
					}

					var token_value = token.value;
					var tag_trimmed = token_value.trimStart();
					var tag_indent = " ".repeat(token_value.length - tag_trimmed.length);

					if(token.type === 'meta.tag' && 0 === token_column) {
						if(null === current_indent) {
							current_indent = tag_indent;
							results.push(tag_trimmed);
							
						} else if (tag_indent.length < current_indent.length) {
							results.push(tag_trimmed);
							current_indent = tag_indent;
						}
						
						// If we hit the root, stop early
						if(0 === current_indent.length)
							break;
						
					// If we're typing a tag name on a new line, use this ident
					} else if(token.type === 'text' && 0 === token_column) {
						if(null === current_indent) {
							current_indent = tag_indent;
						} else if (tag_indent.length < current_indent.length) {
							current_indent = tag_indent;
						}

						// If we hit the root, stop early
						if(0 === current_indent.length)
							break;
					}
					
				} while (iter.stepBackward());
			}

			return results.reverse();
		},
		getKataRowByPath: function(editor, path) {
			var TokenIterator = require('ace/token_iterator').TokenIterator;
			var iter = new TokenIterator(editor.session, 0, 0);

			if(typeof path != 'string')
				return false;

			// Remove trailing colons on the path
			if(path.substr(-1,1) === ':')
				path = path.substr(0, path.length-1);

			path = path.split(':');
			var depth_stack = [];
			var depth = 0;
			var indent = '';
			var matches = 0;
			var last_token_row = -1;
			
			do {
				var token = iter.getCurrentToken();

				if('meta.tag' === token.type) {
					if(last_token_row === iter.getCurrentTokenRow())
						continue;

					last_token_row = iter.getCurrentTokenRow();

					var token_value = token.value;
					var tag_trimmed = token_value.trimStart();
					var tag_indent = " ".repeat(token_value.length - tag_trimmed.length);

					var annotations_pos = tag_trimmed.indexOf('@');

					if(-1 !== annotations_pos) {
						tag_trimmed = tag_trimmed.substr(0, annotations_pos);
					}

					if(tag_indent.length > indent.length) {
						depth++;
						depth_stack.push(tag_indent);
						indent = tag_indent;
						
					} else if(tag_indent.length < indent.length) {
						while(depth_stack.length > 0 && tag_indent.length < indent.length) {
							indent = depth_stack.pop();
							depth--;

							if(indent.length === tag_indent.length) {
								depth_stack.push(tag_indent);
								depth++;
							}

							if(0 === depth)
								indent = '';
						}
					}

					if(path.hasOwnProperty(depth) && tag_trimmed === (path[depth] + ':')) {
						if(matches === depth) {
							matches++;

							if(path.length === matches) {
								return last_token_row;
							}
						}
					}
				}
				
			} while(iter.stepForward());

			return false;
		},
		getQueryTokenValueByPath: function(editor, path) {
			var TokenIterator = require('ace/token_iterator').TokenIterator;
			var iter = new TokenIterator(editor.session, 0, 0);

			path = path.slice(0,-1).split(':').map(function(s) { return s + ':'; });
			var depth = 0;
			var matches = 0;
			
			do {
				var token = iter.getCurrentToken();
				
				if('meta.tag' === token.type) {
					if(path.hasOwnProperty(depth) && token.value === path[depth]) {
						if(matches === depth) {
							matches++;
							
							if(path.length === matches) {
								// [TODO] This could be multiple following tokens (e.g. [1,2,3])
								return iter.stepForward().value;
							}
						}
					}
					
				} else if('paren.lparen' === token.type) {
					depth++;
					
				} else if('paren.rparen' === token.type) {
					depth--;
				}
				
			} while(iter.stepForward());
		},
		getQueryTokenPath: function(pos, editor, max_length) {
			var TokenIterator = require('ace/token_iterator').TokenIterator;
			var iter = new TokenIterator(editor.session, pos.row, pos.column);
			var results = [];
			var scope = [];
			
			var start = iter.getCurrentToken();
			var token = start;
			
			if(null != token) {
				if(token.type !== 'meta.tag') {
					results.push(token);
				}
				
				if(
					'whitespace' === token.type
					|| 'keyword.operator' === token.type
					|| 'variable.other.readwrite.local.twig' === token.type
					|| 'meta.tag.twig' === token.type
					|| ('paren.rparen' === token.type && ']' === token.value)
					|| ('paren.rparen' === token.type && ')' === token.value)
				) {
					// Ignore
					
				} else if('meta.tag' === token.type) {
					scope.push(token.value);
					iter.stepBackward();
					
				} else {
					var lastToken = token;
					
					while(iter.stepBackward()) {
						token = iter.getCurrentToken();
						
						if('meta.tag' === token.type) {
							scope.push(token.value);
							iter.stepBackward();
							break;
							
						} else if (
							'whitespace' === token.type
							|| (lastToken.type === 'text' && token.type === lastToken.type)
							|| ('keyword.operator' === token.type && -1 !== ['OR','AND'].indexOf(token.value))
							|| ('paren.rparen' === token.type && ']' === token.value)
							|| ('paren.rparen' === token.type && ')' === token.value)
						) {
							break;
							
						} else if (
								'string' === token.type
								|| ('keyword.operator' === token.type && '!' === token.value)
								|| 'text' === token.type
								|| 'constant.numeric' === token.type
								|| ('paren.lparen' === token.type && '[' === token.value)
						) {
							// Keep looking
							continue;
						}
						
						lastToken = token;
					}
				}
			}
			
			if(max_length && scope.length >= max_length) {
				return {
					'scope': scope.reverse(),
					'nodes': results.reverse()
				};
			}
			
			// Find root
			var depth = 0;
			
			do {
				token = iter.getCurrentToken();
				
				if(null == token) {
					continue;
					
				} else if('paren.rparen' == token.type && ')' == token.value) {
					depth++;
					
				} else if('paren.lparen' == token.type && '(' == token.value) {
					depth--;
					
					if(-1 == depth)
						while(null != iter.stepBackward()) {
							token = iter.getCurrentToken();
							
							if('keyword.operator' == token.type && '!' == token.value)
								token = iter.stepBackward();
							
							if('meta.tag' == token.type) {
								scope.push(token.value);
								depth++;
								continue;
								
							} else if ('whitespace' == token.type) {
								continue;
								
							} else {
								iter.stepForward();
								break;
							}
						}
				}
				
				if(max_length && scope.length >= max_length)
					break;
				
			} while(null != iter.stepBackward())
			
			return {
				'scope': scope.reverse(),
				'nodes': results.reverse()
			}
		},
		trimLineStart: function(line) {
			if(String.prototype.trimStart)
				return line.trimStart();
	
			if(String.prototype.trimLeft)
				return line.trimLeft();
	
			var matches = line.match(/^( +)/);
	
			if(null === matches)
				return line.valueOf();
	
			return line.substr(matches[1].length);
		},
		getLineIndent: function(line) {
			var trimmedLine = this.trimLineStart(line);
			return " ".repeat(line.length - trimmedLine.length);
		},
		linesAreSiblings: function(a, b) {
			return this.getLineIndent(a) === this.getLineIndent(b);
		},
		lineIsKataField: function(row, editor) {
			var token = editor.session.getTokenAt(row, 0);
			return token && 'meta.tag' === token.type;
		},
		lineIsComment: function(row, editor) {
			var currentLine = editor.session.getLine(row);
			return null != currentLine.match(/\s*#(.*)/i);
		},
		iterToKataParent: function(iter, editor) {
			var refLineRow = iter.getCurrentTokenRow();
			var refLine = editor.session.getLine(refLineRow);
			var refLineIndent = this.getLineIndent(refLine);
			
			while(iter.stepBackward()) {
				var currentLineRow = iter.getCurrentTokenRow();
				var currentLine = editor.session.getLine(currentLineRow);
				var currentLineIndent = this.getLineIndent(currentLine);
				
				if(currentLineIndent < refLineIndent)
					return;
			}
		}
	};
}

var Devblocks = new DevblocksClass();

// [TODO] Remove this in favor of jQuery $(select).val()
function selectValue(e) {
	return e.options[e.selectedIndex].value;
}

function interceptInputCRLF(e,cb) {
	var code = (window.Event) ? e.which : event.keyCode;
	
	if(null != cb && code == 13) {
		try { cb(); } catch(e) { }
	}
	
	return code != 13;
}

/* From:
 * http://www.webmasterworld.com/forum91/4527.htm
 */
function setElementSelRange(e, selStart, selEnd) { 
	if (e.setSelectionRange) { 
		e.focus(); 
		e.setSelectionRange(selStart, selEnd); 
	} else if (e.createTextRange) { 
		var range = e.createTextRange(); 
		range.collapse(true); 
		range.moveEnd('character', selEnd); 
		range.moveStart('character', selStart); 
		range.select(); 
	} 
}

function scrollElementToBottom(e) {
	if(null == e) return;
	e.scrollTop = e.scrollHeight - e.clientHeight;
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

		if(dialogs[0].element.is('[cerb-ui-popup-confirm]')) {
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
		.attr('cerb-ui-popup-confirm', true)
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

var loadingPanel;
function showLoadingPanel() {
	if(null != loadingPanel) {
		hideLoadingPanel();
	}
	
	var options = {
		autoOpen : false,
		closeOnEscape : false,
		draggable : false,
		resizable : false,
		modal : true,
		width : '300px',
		title : 'Please wait...'
	};

	if(0 == $("#loadingPanel").length) {
		$("body").append("<div id='loadingPanel' style='display:none;text-align:center;padding-top:20px;'></div>");
	}

	// Set the content
	$("#loadingPanel")
		.empty()
		.append(Devblocks.getSpinner())
		.append($('<h3>Loading, please wait...</h3>'))
	;
	
	// Render
	loadingPanel = $("#loadingPanel").dialog(options);
	
	loadingPanel.siblings('.ui-dialog-titlebar').hide();
	
	loadingPanel.dialog('open');
}

function hideLoadingPanel() {
	if(loadingPanel) {
		loadingPanel.unbind();
		loadingPanel.dialog('destroy');
		loadingPanel = null;
	}
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
				$this.button('option', 'icons', { primary: 'ui-icon-carat-1-n' } );
				$popup.dialog( "option", "position", { my: "center top", at: "center top+35", of: window } );
				$popup.show();
			} else {
				$popup.hide();
				$dialog.attr('data-position', $dialog.css('position'));
				$dialog.css('position', 'fixed');
				$popup.dialog( "option", "position", { my: "center top", at: "center top", of: window } );
				$this.button('option', 'icons', { primary: 'ui-icon-carat-1-s' } );
			}
		})
	;

	$titlebar
		.append($button_minmax)
	;

	if(null == options.position)
		$popup.dialog('option', 'position', { my: 'center top', at: 'center top+20px', of: window } );

	var callback = function(html) {
		$popup.closest('.ui-dialog').focus();

		// Handle response errors
		if(typeof html === 'object' && html.status) {
			$popup.html('');

			if(404 === html.status) {
				$popup.dialog('option', 'title', 'Error: Not found');
			} else if (403 === html.status) {
				$popup.dialog('option', 'title', 'Error: Forbidden');
			} else if (401 === html.status) {
				$popup.dialog('option', 'title', 'Error: Unauthenticated');
			} else {
				$popup.dialog('option', 'title', 'Error');
			}

		} else {
			$popup.dialog('option', 'title', '');
			
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

	if(null == request) {
		
	} else if('function' == typeof request) {
		request();
		
	} else if(request instanceof FormData) {
		request.set('layer', $layer);
		genericAjaxPost(request, '', null, callback);
	} else {
		request += '&layer=' + $layer;
		genericAjaxGet('', request, callback);
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
	
	if(typeof cb == 'function') {
		$ajax.always(cb);
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

	if(typeof cb == 'function') {
		$ajax.always(cb);
	}
}

function devblocksAjaxDateChooser(field, div, options) {
	var $sel_field;
	var $sel_div;

	if(typeof field == 'object') {
		if(field.selector)
			$sel_field = field;
		else
			$sel_field = $(field);
	} else {
		$sel_field = $(field);
	}
	
	if(typeof div == 'object') {
		if(div.selector)
			$sel_div = div;
		else
			$sel_div = $(div);
	} else {
		$sel_div = $(div);
	}
	
	if(null == options)
		options = { 
			changeMonth: true,
			changeYear: true
		} ;
	
	if(null == options.dateFormat)
		options.dateFormat = 'DD, d MM yy';

	if(null == $sel_div) {
		$sel_field.datepicker(options);
		
	} else {
		if(null == options.onSelect)
			options.onSelect = function(dateText, inst) {
				$sel_field.val(dateText);
				chooser.datepicker('destroy');
			};
		$sel_div.datepicker(options);
	}
}
