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
	}
	
	// Source: http://stackoverflow.com/a/16693578
	this.uniqueId = function() {
		return (Math.random().toString(16)+"000000000").substr(2,8);
	}
	
	/* Source: http://bytes.com/forum/thread90068.html */
	// [TODO] Does this matter with caret.js anymore?
	this.getSelectedText = function() {
		if (window.getSelection) { // recent Mozilla
			var selectedString = window.getSelection();
		} else if (document.all) { // MSIE 4+
			var selectedString = document.selection.createRange().text;
		} else if (document.getSelection) { //older Mozilla
			var selectedString = document.getSelection();
		};
		
		return selectedString;
	}
	
	this.getFormEnabledCheckboxValues = function(form_id,element_name) {
		return $("#" + form_id + " INPUT[name='" + element_name + "']:checked")
		.map(function() {
			return $(this).val();
		})
		.get()
		.join(',')
		;
	}

	this.resetSelectElements = function(form_id,element_name) {
		// Make sure the view form exists
		var viewForm = document.getElementById(form_id);
		if(null == viewForm) return;

		// Make sure the element is present in the form

		var elements = viewForm.elements[element_name];
		if(null == elements) return;

		var len = elements.length;
		var ids = new Array();
		
		if(null == len && null != elements.selectedIndex) {
			elements.selectedIndex = 0;

		} else {
			for(var x=len-1;x>=0;x--) {
				elements[x].selectedIndex = 0;
			}
		}
	}
	
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
	}
	
	this.saveAjaxForm = function($frm, options) {
		genericAjaxPost($frm, '', null, function(json) {
			Devblocks.handleAjaxFormResponse($frm, json, options);
		});
	}
	
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
	}
	
	this.clearAlerts = function() {
		var $alerts = $('#cerb-alerts');
		$alerts.find('.cerb-alert').remove();
	}
	
	this.createAlertError = function(message) {
		return this.createAlert(message, 'error', 0);
	}
	
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
		
		var $close = $('<span class="cerb-alert-close"><span class="glyphicons glyphicons-remove"></span></span>')
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
		
		$alert.effect('slide',{ direction:'up', mode:'show' }, 250);
		
		if(parseInt(duration) > 0) {
			$alert.delay(duration).effect('slide',{ direction:'up', mode:'hide' }, 500, function() {
				$alert.remove();
			});
		}
	}
	
	this.showError = function(target, message, animate) {
		$html = $('<div class="ui-widget"/>')
			.append(
				$('<div class="ui-state-error ui-corner-all" style="padding:0 0.5em;margin:0.5em;"/>')
				.append(
					$('<p/>').text(message)
						.prepend($('<span class="glyphicons glyphicons-circle-exclamation-mark" style="margin-right:5px;"></span>'))
				)
			)
		;
		
		var $status = $(target).html($html).show();
		
		animate = (null == animate || false != animate) ? true: false;
		if(animate)
			$status.effect('slide',{ direction:'up', mode:'show' },250);
		
		return $status;
	}
	
	this.showSuccess = function(target, message, autohide, animate) {
		$html = $('<div class="ui-widget"/>')
			.append(
				$('<div class="ui-state-highlight ui-corner-all" style="padding:0 0.5em;margin:0.5em;"/>')
				.append(
					$('<p/>').text(message)
						.prepend($('<span class="glyphicons glyphicons-circle-ok" style="margin-right:5px;"></span>'))
				)
			)
		;
		
		var $status = $(target).html($html).show();
		
		animate = (null == animate || false != animate) ? true: false; 
		if(animate)
			$status.effect('slide',{ direction:'up', mode:'show' },250);
		if(animate && (autohide || null == autohide))
			$status.delay(5000).effect('slide',{ direction:'up', mode:'hide' }, 250);
			
		return $status;
	}
	
	this.getDefaultjQueryUiTabOptions = function() {
		var $this = this;
		
		return {
			activate: function(event, ui) {
				var tabsId = ui.newPanel.closest('.ui-tabs').attr('id');
				
				if(0 == tabsId.length)
					return;
				
				var index = ui.newTab.index();
				$this.setjQueryUiTabSelected(tabsId, index);
			},
			beforeLoad: function(event, ui) {
				var tab_title = ui.tab.find('> a').first().clone();
				tab_title.find('div.tab-badge').remove();
				var $div = $('<div style="font-size:18px;font-weight:bold;text-align:center;padding:10px;margin:10px;"/>')
					.text('Loading: ' + $.trim(tab_title.text()))
					.append($('<br>'))
					.append($('<span class="cerb-ajax-spinner"/>'))
					;
				ui.panel.html($div);
			}
		};
	}
	
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
	}
	
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
	}
	
	this.callbackPeekEditSave = function(e) {
		if(!(typeof e == 'object'))
			return false;
		
		var $button = $(e.target);
		var $popup = genericAjaxPopupFind($button);
		var $frm = $popup.find('form').first();
		var options = e.data;
		var is_delete = (options && options.mode == 'delete');
		var is_continue = (options && options.mode == 'continue');
		
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
		var $spinner = $('<span class="cerb-ajax-spinner"/>')
			.css('zoom', '0.5')
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
					event = new jQuery.Event('peek_deleted');
				} else {
					event = new jQuery.Event('peek_saved');
				}
				
				// Meta fields
				for(k in e) {
					event[k] = e[k];
				}
				
				// Reload the associated view (underlying helper)
				if(e.view_id)
					genericAjaxGet('view'+e.view_id, 'c=internal&a=viewRefresh&id=' + e.view_id);
				
				if(is_continue) {
					Devblocks.createAlert('Saved!', 'note');
					
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
	}
	
	this.triggerEvent = function(element, e) {
		$(element).trigger(e);
	}
	
	this._loadedResources = {};
	
	this.getResourceState = function(url) {
		var state = this._loadedResources.hasOwnProperty(url) ? this._loadedResources[url] : null;
		return state;
	}
	
	this.setResourceState = function(url, state) {
		this._loadedResources[url] = state;
	}
	
	this.loadStylesheet = function(url, callback) {
		var $instance = this;
		var state = $instance.getResourceState(url);
		
		if(null == state) {
			var options = {
				dataType: "text",
				cache: true,
				url: url
			}
			
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
	}
	
	this.loadScript = function(url, callback) {
		var $instance = this;
		var state = $instance.getResourceState(url);
		
		if(null == state) {
			var options = {
				dataType: "script",
				cache: true,
				url: url
			}
			
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
	}
	
	this.loadScripts = function(urls, finished) {
		if(!$.isArray(urls))
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
	}
	
	this.loadResources = function(resources, finished) {
		if(typeof(resources) != 'object')
			return callback(false);
		
		var $instance = this;
		var jobs = [];
		
		if(resources.hasOwnProperty('css')) {
			if(!$.isArray(resources.css))
				return callback(false);
			
			resources.css.forEach(function(url) {
				if(url.substring(0,1) == '/')
					url = DevblocksWebPath + url.substring(1);
				
				jobs.push(async.apply($instance.loadStylesheet.bind($instance), url));
			});
		}
		
		if(resources.hasOwnProperty('js')) {
			if(!$.isArray(resources.js))
				return callback(false);
			
			resources.js.forEach(function(url) {
				if(url.substring(0,1) == '/')
					url = DevblocksWebPath + url.substring(1);
				
				jobs.push(async.apply($instance.loadScript.bind($instance), url));
			});
		}
		
		async.parallelLimit(jobs, 2, function(err, json) {
			if(err)
				return finished(err);
			
			finished();
		});
	}
	
	this.cerbCodeEditor = {
		insertMatchAndAutocomplete: function(editor, data) {
			delete data.completer;
			editor.completer.insertMatch(data);
			
			if(data.suppress_autocomplete)
				return;
			
			// If we're inserting a field, trigger autocompletion
			if(
				(data.value && -1 != data.value.indexOf(':'))
				|| (data.snippet && -1 != data.snippet.indexOf(':'))
			) {
				setTimeout(function() {
					editor.commands.byName.startAutocomplete.exec(editor);
				}, 50)
			}
		},
		getYamlTokenPath: function(pos, editor) {
			var TokenIterator = require('ace/token_iterator').TokenIterator;
			var iter = new TokenIterator(editor.session, pos.row, pos.column);
			var results = [];
			
			var start = iter.getCurrentToken();
			var token = start;
			var current_indent = null;
			
			if(!String.prototype.trimStart) {
				String.prototype.trimStart = function() {
					if(String.prototype.trimLeft)
						return this.trimLeft();
					
					var matches = this.match(/^( +)/);
					
					if(null === matches)
						return this.valueOf();
					
					return this.substr(matches[1].length);
				}
			}
			
			if(null != token) {
				// We're on a new indent
				if(token.type == 'text' && token.value.length > 0 && 0 == token.value.trimStart().length) {
					current_indent = token.value;
					iter.stepBackward();
					
				} else if (token.type == 'list.markup' || (token.type == 'string' && token.value.substr(-2) == '- ')) {
					var tag_indent = " ".repeat(token.value.length);
					
					if (null === current_indent || tag_indent.length < current_indent.length) {
						results.push('-:');
						current_indent = tag_indent;
					}
					
					iter.stepBackward();
				}
				
				do {
					token = iter.getCurrentToken();
					
					if(token.type == 'meta.tag') {
						var token_value = token.value;
						var prevToken = iter.stepBackward();
						
						if(prevToken && prevToken.type == 'list.markup') {
							if(-1 == token_value.indexOf(' ')) {
								token_value = " ".repeat(prevToken.value.length) + token_value;
							} else {
								token_value = " ".repeat(prevToken.value.length) + token_value;
							}
							
						} else {
							iter.stepForward();
						}
						
						var tag_trimmed = token_value.trimStart();
						var tag_indent = " ".repeat(token_value.length - tag_trimmed.length);
						
						if(null === current_indent) {
							current_indent = tag_indent;
							results.push(tag_trimmed + ':');
							
						} else if (tag_indent.length < current_indent.length) {
							results.push(tag_trimmed + ':');
							current_indent = tag_indent;
							
							if(prevToken && prevToken.type == 'list.markup' && '-:' != results.slice(-1))
								results.push('-:');
							
						} else if (tag_indent.length == current_indent.length) {
							if(prevToken && prevToken.type == 'list.markup' && '-:' != results.slice(-1))
								results.push('-:');
						}
						
						// If we hit the root, stop early
						if(0 == current_indent.length)
							break;
					}
					
				} while (iter.stepBackward());
			}
			
			return results.reverse();
		},
		getQueryTokenValueByPath: function(editor, path) {
			var TokenIterator = require('ace/token_iterator').TokenIterator;
			var iter = new TokenIterator(editor.session, 0, 0);
			var tree = {};
			
			path = path.slice(0,-1).split(':').map(function(s) { return s + ':'; });
			var depth = 0;
			var matches = 0;
			
			do {
				var token = iter.getCurrentToken();
				
				if('meta.tag' == token.type) {
					if(path.hasOwnProperty(depth) && token.value == path[depth]) {
						if(matches == depth) {
							matches++;
							
							if(path.length == matches) {
								// [TODO] This could be multiple following tokens (e.g. [1,2,3])
								var val = iter.stepForward().value;
								return val;
							}
						}
					}
					
				} else if('paren.lparen' == token.type) {
					depth++;
					
				} else if('paren.rparen' == token.type) {
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
				if(token.type != 'meta.tag') {
					results.push(token);
				}
				
				if(
					'whitespace' == token.type
					|| 'keyword.operator' == token.type
					|| 'variable.other.readwrite.local.twig' == token.type
					|| 'meta.tag.twig' == token.type
					|| ('paren.rparen' == token.type && ']' == token.value)
					|| ('paren.rparen' == token.type && ')' == token.value)
				) {
					// Ignore
					
				} else if('meta.tag' == token.type) {
					scope.push(token.value);
					iter.stepBackward();
					
				} else {
					var lastToken = token;
					
					while(iter.stepBackward()) {
						token = iter.getCurrentToken();
						
						if('meta.tag' == token.type) {
							scope.push(token.value);
							iter.stepBackward();
							break;
							
						} else if (
							'whitespace' == token.type
							|| (lastToken.type == 'text' && token.type == lastToken.type)
							|| ('keyword.operator' == token.type && -1 != ['OR','AND'].indexOf(token.value))
							|| ('paren.rparen' == token.type && ']' == token.value)
							|| ('paren.rparen' == token.type && ')' == token.value)
						) {
							break;
							
						} else if (
								'string' == token.type
								|| ('keyword.operator' == token.type && '!' == token.value)
								|| 'text' == token.type
								|| 'constant.numeric' == token.type
								|| ('paren.lparen' == token.type && '[' == token.value)
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
		}
	};
};

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
	$("#loadingPanel").html('<span class="cerb-ajax-spinner"></span><h3>Loading, please wait...</h3>');
	
	// Render
	loadingPanel = $("#loadingPanel").dialog(options);
	
	loadingPanel.siblings('.ui-dialog-titlebar').hide();
	
	loadingPanel.dialog('open');
}

function hideLoadingPanel() {
	loadingPanel.unbind();
	loadingPanel.dialog('destroy');
	loadingPanel = null;
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
		autoOpen : false,
		closeOnEscape : true,
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
		close: function(event, ui) {
			var $this = $(this);
			$('#devblocksPopups').removeData($layer);
			$this.unbind().find(':focus').blur();
			$this.closest('.ui-dialog').remove();
		}
	};
	
	var $popup = null;
	
	// Restore position from previous dialog?
	if(target == 'reuse') {
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
				at: "center top+20"
			};
		}
		target = null;
		
	} else if(target && typeof target == "object" && null != target.my && null != target.at) {
		options.position = {
			my: target.my,
			at: target.at
		};
		
	} else {
		options.position = {
			my: "center top",
			at: "center top+20"
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
	
	// Load the popup content
	var $options = { async: false }
	genericAjaxGet('', request + '&layer=' + $layer,
		function(html) {
			$popup = $("#popup"+$layer);
			
			if(0 == $popup.length) {
				$("body").append("<div id='popup"+$layer+"' class='devblocks-popup' style='display:none;'></div>");
				$popup = $('#popup'+$layer);
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
			
			// Open
			$popup.dialog('open');
			
			// Popup min/max functionality
			var $titlebar = $popup.closest('.ui-dialog')
				.find('.ui-dialog-titlebar')
				;
			
			var $button_minmax = $("<button class='ui-dialog-titlebar-minmax'></button>")
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
						$popup.dialog( "option", "position", { my: "center top", at: "center top", of: window } );
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
			
			// Set the content
			$popup.html(html);
			
			if(null == options.position)
				$popup.dialog('option', 'position', { my: 'center top', at: 'center top+20px', of: window } );
			
			$popup.trigger('popup_open');
			
			// Callback
			try { cb(html); } catch(e) { }
		},
		$options
	);
	
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
				genericAjaxGet('view'+view_id, 'c=internal&a=viewRefresh&id=' + view_id);
			}

			if(has_view)
				$('#view'+view_id).fadeTo("fast", 1.0);

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
		
	options.headers['X-CSRF-Token'] = $('meta[name="_csrf_token"]').attr('content');
	
	var $ajax = $.ajax(options);
	
	if(typeof cb == 'function') {
		$ajax.done(cb);
	}
}

function genericAjaxPost(formRef,divRef,args,cb,options) {
	var frm = null;
	var div = null;
	
	// Polymorph div
	if(divRef instanceof jQuery)
		div = divRef;
	else if(typeof divRef=="string" && divRef.length > 0)
		div = $('#'+divRef);
	
	// Allow custom options
	if(null == options)
		options = { };
	
	options.type = 'POST';
	options.url = DevblocksAppPath+'ajax.php'+(null!=args?('?'+args):''),
	options.cache = false;
	
	// Handle formData
	if(formRef instanceof FormData) {
		options.processData = false;
		options.contentType = false;
		options.data = formRef;
		
	} else {
		// Polymorph form
		if(formRef instanceof jQuery)
			frm = formRef;
		else if(typeof formRef=="string" && formRef.length > 0)
			frm = $('#'+formRef);
		
		if(null == frm)
			return;
		
		options.data = $(frm).serialize();
	}
	
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
		$ajax.done(cb);
	}
}

function devblocksAjaxDateChooser(field, div, options) {
	if(typeof field == 'object') {
		if(field.selector)
			var $sel_field = field;
		else
			var $sel_field = $(field);
	} else {
		var $sel_field = $(field);
	}
	
	if(typeof div == 'object') {
		if(div.selector)
			var $sel_div = div;
		else
			var $sel_div = $(div);
	} else {
		var $sel_div = $(div);
	}
	
	if(null == options)
		options = { 
			changeMonth: true,
			changeYear: true
		} ;
	
	if(null == options.dateFormat)
		options.dateFormat = 'DD, d MM yy';
			
	if(null == $sel_div) {
		var chooser = $sel_field.datepicker(options);
		
	} else {
		if(null == options.onSelect)
			options.onSelect = function(dateText, inst) {
				$sel_field.val(dateText);
				chooser.datepicker('destroy');
			};
		var chooser = $sel_div.datepicker(options);
	}
}
