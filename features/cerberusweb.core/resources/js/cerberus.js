var markitupPlaintextDefaults = {
	resizeHandle: false,
	nameSpace:'markItUpPlaintext',
	onShiftEnter:		{keepDefault:false, openWith:'\n\n'},
	markupSet: [
	]
}

var markitupMarkdownDefaults = {
	resizeHandle: false,
	previewParserPath:	DevblocksAppPath + 'ajax.php?c=internal&a=transformMarkupToHTML&format=markdown&_csrf_token=' + $('meta[name="_csrf_token"]').attr('content'),
	onShiftEnter:		{keepDefault:false, openWith:'\n\n'},
	markupSet: [
		{name:'Heading 1', key:'1', openWith:'# ', placeHolder:'Your title here...', className:'h1' },
		{name:'Heading 2', key:'2', openWith:'## ', placeHolder:'Your title here...', className:'h2' },
		{name:'Heading 3', key:'3', openWith:'### ', placeHolder:'Your title here...', className:'h3' },
		{separator:' ', className:'sep' },		
		{name:'Bold', key:'B', openWith:'**', closeWith:'**', className:'b'},
		{name:'Italic', key:'I', openWith:'_', closeWith:'_', className:'i'},
		{separator:' ', className:'sep' },
		{name:'Bulleted List', openWith:'- ', className:'ul' },
		{name:'Numeric List', className:'ol', openWith:function(markItUp) {
			return markItUp.line+'. ';
		}},
		{separator:' ', className:'sep' },
		{name:'Link to an External Image', replaceWith:'![[![Alternative text]!]]([![Url:!:http://]!] "[![Title]!]")', className:'img'},
		{name:'Link', key:'L', openWith:'[', closeWith:']([![Url:!:http://]!] "[![Title]!]")', placeHolder:'Your text to link here...', className:'a' },
		{separator:' ', className:'sep'},	
		{name:'Quotes', openWith:'> ', className:'blockquote'},
		{
			name:'Code Format', 
			openWith:function(markitup) {
				if(markitup.selection.split("\n").length > 1)
					return "```\n";
				return "`";
			},
			closeWith:function(markitup) {
				if(markitup.selection.split("\n").length > 1)
					return "\n```\n";
				return "`";
			},
			placeHolder:'code',
			className:'code'
		},
		{separator:' '},
		{name:'Preview', key: 'P', call:'preview', className:"preview"}
	]
}

var markitupParsedownDefaults = {
	nameSpace:'markItUpParsedown',
	resizeHandle: false,
	previewParserPath:	DevblocksAppPath + 'ajax.php?c=internal&a=transformMarkupToHTML&format=parsedown&_csrf_token=' + $('meta[name="_csrf_token"]').attr('content'),
	previewAutoRefresh: true,
	previewInWindow: 'width=800, height=600, titlebar=no, location=no, menubar=no, status=no, toolbar=no, resizable=yes, scrollbars=yes',
	onShiftEnter:		{keepDefault:false, openWith:'\n\n'},
	markupSet: [
		{name:'Bold', key:'B', openWith:'**', closeWith:'**', className:'b'},
		{name:'Italic', openWith:'_', closeWith:'_', className:'i'},
		{name:'Bulleted List', openWith:'- ', className:'ul' },
		{name:'Numeric List', className:'ol', openWith:function(markItUp) {
			return markItUp.line+'. ';
		}},
		{name:'Link to an External Image', openWith:'![Image](', closeWith:')', placeHolder:'http://www.example.com/path/to/image.png', className:'img'},
		{name:'Link', key:"L", openWith:'[', closeWith:']([![Url:!:http://]!])', placeHolder:'Your text to link here...', className:'a' },
		{name:'Quotes', openWith:'> ', className:'blockquote'},
		{
			name:'Code Format', 
			openWith:function(markitup) {
				if(markitup.selection.split("\n").length > 1)
					return "```\n";
				return "`";
			},
			closeWith:function(markitup) {
				if(markitup.selection.split("\n").length > 1)
					return "\n```\n";
				return "`";
			},
			placeHolder:'code',
			className:'code'
		},
		{separator:' '},
		{name:'Preview', key: 'P', call:'preview', className:"preview"}
	]
}

var markitupHTMLDefaults = {
	resizeHandle: false,
	previewParserPath:	DevblocksAppPath + 'ajax.php?c=internal&a=transformMarkupToHTML&format=html&_csrf_token=' + $('meta[name="_csrf_token"]').attr('content'),
	onShiftEnter:	{keepDefault:false, replaceWith:'<br />\n'},
	onCtrlEnter:	{keepDefault:false, openWith:'\n<p>', closeWith:'</p>\n'},
	onTab:			{keepDefault:false, openWith:'	 '},
	markupSet: [
		{name:'Heading 1', key:'1', openWith:'<h1(!( class="[![Class]!]")!)>', closeWith:'</h1>', placeHolder:'Your title here...', className:'h1' },
		{name:'Heading 2', key:'2', openWith:'<h2(!( class="[![Class]!]")!)>', closeWith:'</h2>', placeHolder:'Your title here...', className:'h2' },
		{name:'Heading 3', key:'3', openWith:'<h3(!( class="[![Class]!]")!)>', closeWith:'</h3>', placeHolder:'Your title here...', className:'h3' },
		{name:'Paragraph', openWith:'<p(!( class="[![Class]!]")!)>', closeWith:'</p>', className:'p' },
		{separator:' ', className:'sep' },
		{name:'Bold', key:'B', openWith:'(!(<strong>|!|<b>)!)', closeWith:'(!(</strong>|!|</b>)!)', className:'b' },
		{name:'Italic', key:'I', openWith:'(!(<em>|!|<i>)!)', closeWith:'(!(</em>|!|</i>)!)', className:'i' },
		{name:'Stroke through', key:'S', openWith:'<del>', closeWith:'</del>', className:'strike' },
		{separator:' ', className:'sep' },
		{name:'Ul', openWith:'<ul>\n', closeWith:'</ul>\n', className:'ul' },
		{name:'Ol', openWith:'<ol>\n', closeWith:'</ol>\n', className:'ol' },
		{name:'Li', openWith:'<li>', closeWith:'</li>', className:'li' },
		{separator:' ', className:'sep' },
		{name:'Link to an External Image', replaceWith:'<img src="[![Source:!:http://]!]" alt="[![Alternative text]!]" />', className:'img' },
		{name:'Link', key:'L', openWith:'<a href="[![Link:!:http://]!]"(!( title="[![Title]!]")!)>', closeWith:'</a>', placeHolder:'Your text to link...', className:'a' },
		{separator:' ', className:'sep' },
		{name:'Clean', className:'clean', replaceWith:function(markitup) { return markitup.selection.replace(/<(.*?)>/g, "") } },
		{name:'Preview', key: 'P', className:'preview', call:'preview' }
	]
}

$.fn.cerbDateInputHelper = function(options) {
	var options = (typeof options == 'object') ? options : {};
	
	return this.each(function() {
		var $this = $(this);
		
		$this.datepicker({
			showOn: 'button',
			buttonText: '<span class="glyphicons glyphicons-calendar"></span>',
			dateFormat: 'D, d M yy',
			defaultDate: 'D, d M yy',
			onSelect: function(dateText, inst) {
				inst.input.addClass('changed').focus();
			}
		});
		
		$this
			.attr('placeholder', '+2 hours; +4 hours @Calendar; Jan 15 2018 2pm; 5pm America/New York')
			.autocomplete({
				delay: 300,
				minLength: 1,
				autoFocus: false,
				source: function(request, response) {
					var last = request.term.split(' ').pop();

					request.term = last;
					
					if(request.term == null)
						return;
					
					var url = DevblocksAppPath+'ajax.php?c=internal&a=handleSectionAction&section=calendars&action=getDateInputAutoCompleteOptionsJson';
					
					var ajax_options = {
						url: url,
						dataType: "json",
						data: request,
						success: function(data) {
							response(data);
						}
					};
					
					if(null == ajax_options.headers)
						ajax_options.headers = {};

					ajax_options.headers['X-CSRF-Token'] = $('meta[name="_csrf_token"]').attr('content');
					
					$.ajax(ajax_options);
				},
				focus: function() {
					return false;
				},
				select: function(event, ui) {
					$(this).addClass('autocomplete_select');
					
					var terms = this.value.split(' ');
					terms.pop();
					terms.push(ui.item.value);
					terms.push('');
					this.value = terms.join(' ');
					$(this).addClass('changed', true);
					return false;
				}
			})
			.data('uiAutocomplete')
				._renderItem = function(ul, item) {
					var $li = $('<li/>')
						.data('ui-autocomplete-item', item)
						.append($('<a></a>').text(item.label))
						.appendTo(ul);
					
					item.value = $li.text();
					
					return $li;
				}
			;
		
		$this
			.on('send', function(e) {
				var $input_date = $(this);
				var val = $input_date.val();
				
				if(!$input_date.is('.changed')) {
					if(e.keydown_event_caller && e.keydown_event_caller.shiftKey && e.keydown_event_caller.ctrlKey && e.keydown_event_caller.which == 13)
						if(options.submit && typeof options.submit == 'function')
							options.submit();
						
					return;
				}
				
				$input_date.autocomplete('close');
				
				// If the date contains any placeholders, don't auto-parse it
				if(-1 != val.indexOf('{'))
					return;
				
				// Send the text to the server for translation
				genericAjaxGet('', 'c=internal&a=handleSectionAction&section=calendars&action=parseDateJson&date=' + encodeURIComponent(val), function(json) {
					if(json == false) {
						// [TODO] Color it red for failed, and display an error somewhere
						$input_date.val('');
					} else {
						$input_date.val(json.to_string);
						$this.trigger('cerb-date-changed');
					}
					
					if(e.keydown_event_caller && e.keydown_event_caller.shiftKey && e.keydown_event_caller.ctrlKey && e.keydown_event_caller.which == 13)
						if(options.submit && typeof options.submit == 'function')
							options.submit();
					
					$input_date.removeClass('changed');
				});
			})
			.blur(function(e) {
				$(this).trigger('send');
			})
			.keydown(function(e) {
				$(this).addClass('changed', true);
				
				// If the worker hit enter and we're not showing an autocomplete menu
				if(e.which == 13) {
					e.preventDefault();

					if($(this).is('.autocomplete_select')) {
						$(this).removeClass('autocomplete_select');
						return false;
					}
					
					$(this).trigger({ type: 'send', 'keydown_event_caller': e });
				}
			})
			;
		
		var $parent = $this.parent();
		
		if($parent.is(':visible')) {
			var width_minus_icons = ($parent.width() - 64);
			var width_relative = Math.floor(100 * (width_minus_icons / $parent.width()));
			$this.css('width', width_relative + '%');
		}
	});
};

var cAjaxCalls = function() {
	this.viewTicketsAction = function(view_id, action) {
		var divName = 'view'+view_id;
		var formName = 'viewForm'+view_id;

		switch(action) {
			case 'not_spam':
				showLoadingPanel();
				genericAjaxPost(formName, '', 'c=tickets&a=viewNotSpamTickets&view_id='+view_id, function(html) {
					$('#'+divName).html(html).trigger('view_refresh');
					hideLoadingPanel();
				});
				break;
			case 'waiting':
				showLoadingPanel();
				genericAjaxPost(formName, '', 'c=tickets&a=viewWaitingTickets&view_id='+view_id, function(html) {
					$('#'+divName).html(html).trigger('view_refresh');
					hideLoadingPanel();
				});
				break;
			case 'not_waiting':
				showLoadingPanel();
				genericAjaxPost(formName, '', 'c=tickets&a=viewNotWaitingTickets&view_id='+view_id, function(html) {
					$('#'+divName).html(html).trigger('view_refresh');
					hideLoadingPanel();
				});
				break;
			default:
				break;
		}
	}
	
	this.viewCloseTickets = function(view_id,mode) {
		var divName = 'view'+view_id;
		var formName = 'viewForm'+view_id;

		showLoadingPanel();

		switch(mode) {
			case 1: // spam
				genericAjaxPost(formName, '', 'c=tickets&a=viewSpamTickets&view_id=' + view_id, function(html) {
					$('#'+divName).html(html).trigger('view_refresh');
					hideLoadingPanel();
				});
				break;
			case 2: // delete
				genericAjaxPost(formName, '', 'c=tickets&a=viewDeleteTickets&view_id=' + view_id, function(html) {
					$('#'+divName).html(html).trigger('view_refresh');
					hideLoadingPanel();
				});
				break;
			default: // close
				genericAjaxPost(formName, '', 'c=tickets&a=viewCloseTickets&view_id=' + view_id, function(html) {
					$('#'+divName).html(html).trigger('view_refresh');
					hideLoadingPanel();
				});
				break;
		}
	}
	
	this.viewAddQuery = function(view_id, query, replace) {
		var $view = $('#view'+view_id);
		
		var post_str = 'c=internal' +
			'&a=viewAddFilter' + 
			'&id=' + view_id +
			'&add_mode=query' +
			'&replace=' + encodeURIComponent(replace) +
			'&query=' + encodeURIComponent(query)
			;
		
		var cb = function(o) {
			var $view_filters = $('#viewCustomFilters'+view_id);
			
			if(0 != $view_filters.length) {
				$view_filters.html(o);
				$view_filters.trigger('view_refresh')
			}
		}
		
		var options = {};
		options.type = 'POST';
		options.data = post_str; //$('#'+formName).serialize();
		options.url = DevblocksAppPath+'ajax.php';//+(null!=args?('?'+args):''),
		options.cache = false;
		options.success = cb;
		
		if(null == options.headers)
			options.headers = {};
		
		options.headers['X-CSRF-Token'] = $('meta[name="_csrf_token"]').attr('content');
		
		$.ajax(options);
	}
	
	this.viewAddFilter = function(view_id, field, oper, values, replace) {
		var $view = $('#view'+view_id);
		
		var post_str = 'c=internal' +
		'&a=viewAddFilter' + 
		'&id=' + view_id +
		'&replace=' + encodeURIComponent(replace) +
		'&field=' + encodeURIComponent(field) +
		'&oper=' + encodeURIComponent(oper) +
		'&' + $.param(values, true)
		;
		
		var cb = function(o) {
			var $view_filters = $('#viewCustomFilters'+view_id);
			
			if(0 != $view_filters.length) {
				$view_filters.html(o);
				$view_filters.trigger('view_refresh')
			}
		}
		
		var options = {};
		options.type = 'POST';
		options.data = post_str; //$('#'+formName).serialize();
		options.url = DevblocksAppPath+'ajax.php';//+(null!=args?('?'+args):''),
		options.cache = false;
		options.success = cb;
		
		if(null == options.headers)
			options.headers = {};
		
		options.headers['X-CSRF-Token'] = $('meta[name="_csrf_token"]').attr('content');
		
		$.ajax(options);
	}
	
	this.viewRemoveFilter = function(view_id, fields) {
		var $view = $('#view'+view_id);
		
		var post_str = 'c=internal' +
			'&a=viewAddFilter' + 
			'&id=' + view_id
			;
		
		for(field in fields) {
			post_str += '&field_deletes[]=' + encodeURIComponent(fields[field]);
		}
		
		cb = function(o) {
			var $view_filters = $('#viewCustomFilters'+view_id);
			
			if(0 != $view_filters.length) {
				$view_filters.html(o);
				$view_filters.trigger('view_refresh')
			}
		}
		
		var options = {};
		options.type = 'POST';
		options.data = post_str; //$('#'+formName).serialize();
		options.url = DevblocksAppPath+'ajax.php';//+(null!=args?('?'+args):''),
		options.cache = false;
		options.success = cb;
		
		if(null == options.headers)
			options.headers = {};
		
		options.headers['X-CSRF-Token'] = $('meta[name="_csrf_token"]').attr('content');
		
		$.ajax(options);
	}	
	
	this.viewUndo = function(view_id) {
		genericAjaxGet('','c=tickets&a=viewUndo&view_id=' + view_id,
			function(html) {
				$('#view'+view_id).html(html).trigger('view_refresh');
			}
		);		
	}

	this.emailAutoComplete = function(sel, options) {
		var url = DevblocksAppPath+'ajax.php?c=internal&a=autocomplete&context=cerberusweb.contexts.address&_csrf_token=' + $('meta[name="_csrf_token"]').attr('content');
		if(null == options) options = { };
		
		if(null == options.delay)
			options.delay = 300;

		if(null == options.minLength)
			options.minLength = 1;
		
		if(null == options.autoFocus)
			options.autoFocus = true;
		
		if(null != options.multiple && options.multiple) {
			options.source = function (request, response) {
				// From the last comma (if exists)
				var pos = request.term.lastIndexOf(',');
				if(-1 != pos) {
					// Split at the comma and trim
					request.term = $.trim(request.term.substring(pos+1));
				}
				
				if(0==request.term.length)
					return;
				
				var ajax_options = {
					url: url,
					dataType: "json",
					data: request,
					success: function(data) {
						response(data);
					}
				};
				
				$.ajax(ajax_options);
			}
			options.select = function(event, ui) {
				var value = $(this).val();
				var pos = value.lastIndexOf(',');
				if(-1 != pos) {
					$(this).val(value.substring(0,pos)+', '+ui.item.label+', ');
				} else {
					$(this).val(ui.item.label+', ');
				}
				return false;
			}
			
			options.focus = function(event, ui) {
				// Don't replace the textbox value
				return false;
			}
			
		} else {
			options.source = function (request, response) {
				var ajax_options = {
					url: url,
					dataType: "json",
					data: request,
					success: function(data) {
						response(data);
					}
				};
				
				$.ajax(ajax_options);
			}
			options.select = function(event, ui) {
				$(this).val(ui.item.label);
				return false;
			}
			
			options.focus = function(event, ui) {
				// Don't replace the textbox value
				return false;
			};
		}
		
		var $sel = $(sel);
		
		$sel.autocomplete(options);
	}

	this.orgAutoComplete = function(sel, options) {
		if(null == options) options = { };
		
		options.source = DevblocksAppPath+'ajax.php?c=contacts&a=getOrgsAutoCompletions&_csrf_token=' + $('meta[name="_csrf_token"]').attr('content');
		
		if(null == options.delay)
			options.delay = 300;
		
		if(null == options.minLength)
			options.minLength = 1;
		
		if(null == options.autoFocus)
			options.autoFocus = true;

		var $sel = $(sel);
		
		$sel.autocomplete(options);
	}
	
	this.countryAutoComplete = function(sel, options) {
		if(null == options) options = { };
		
		options.source = DevblocksAppPath+'ajax.php?c=contacts&a=getCountryAutoCompletions&_csrf_token=' + $('meta[name="_csrf_token"]').attr('content');
		
		if(null == options.delay)
			options.delay = 300;
		
		if(null == options.minLength)
			options.minLength = 1;

		if(null == options.autoFocus)
			options.autoFocus = true;
		
		var $sel = $(sel);
		
		$sel.autocomplete(options);
	}

	this.chooser = function(button, context, field_name, options) {
		if(null == field_name)
			field_name = 'context_id';
		
		if(null == options) 
			options = { };
		
		var $button = $(button);

		// The <ul> buffer
		var $ul = $button.siblings('ul.chooser-container');
		
		// Add the container if it doesn't exist
		if(0==$ul.length) {
			var $ul = $('<ul class="bubbles chooser-container"></ul>');
			$ul.insertAfter($button);
		}
		
		// The chooser search button
		$button.click(function(event) {
			var $button = $(this);
			var $ul = $(this).siblings('ul.chooser-container:first');
			
			var $chooser=genericAjaxPopup('chooser' + new Date().getTime(),'c=internal&a=chooserOpen&context=' + context,null,true,'90%');
			$chooser.one('chooser_save', function(event) {
				// Add the labels
				for(var idx in event.labels)
					if(0==$ul.find('input:hidden[value="'+event.values[idx]+'"]').length) {
						var $li = $('<li/>').text(event.labels[idx]);
						var $hidden = $('<input type="hidden">').attr('name', field_name + '[]').attr('value',event.values[idx]).appendTo($li);
						var $a = $('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>').appendTo($li); 
						
						if(null != options.style)
							$li.addClass(options.style);
						$ul.append($li);
					}
			});
		});
		
		// Autocomplete
		if(null != options.autocomplete && true == options.autocomplete) {
			
			if(null == options.autocomplete_class) {
				options.autocomplete_class = ''; //'input_search';
			}
			
			var $autocomplete = $('<input type="text" size="45">').addClass(options.autocomplete_class);
			$autocomplete.insertBefore($button);
			
			$autocomplete.autocomplete({
				delay: 250,
				source: DevblocksAppPath+'ajax.php?c=internal&a=autocomplete&context=' + context + '&_csrf_token=' + $('meta[name="_csrf_token"]').attr('content'),
				minLength: 1,
				focus:function(event, ui) {
					return false;
				},
				autoFocus:true,
				select:function(event, ui) {
					var $this = $(this);
					var $label = ui.item.label;
					var $value = ui.item.value;
					var $ul = $this.siblings('button:first').siblings('ul.chooser-container:first');
					
					if(undefined != $label && undefined != $value) {
						if(0 == $ul.find('input:hidden[value="'+$value+'"]').length) {
							var $li = $('<li/>').text($label);
							var $hidden = $('<input type="hidden">').attr('name', field_name + '[]').attr('title', $label).attr('value', $value).appendTo($li);
							var $a = $('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>').appendTo($li);
							$ul.append($li);
						}
					}
					
					$this.val('');
					return false;
				}
			});
		}
	}
	
	this.chooserFile = function(button, field_name, options) {
		if(null == field_name)
			field_name = 'context_id';
		
		if(null == options) 
			options = { };
		
		var $button = $(button);

		// The <ul> buffer
		var $ul = $button.next('ul.chooser-container');
		
		if(null == options.single)
			options.single = false;
		
		// Add the container if it doesn't exist
		if(0==$ul.length) {
			var $ul = $('<ul class="bubbles chooser-container"></ul>');
			$ul.insertAfter($button);
		}
		
		// The chooser search button
		$button.click(function(event) {
			var $button = $(button);
			var $ul = $button.nextAll('ul.chooser-container:first');
			var $chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpenFile&single=' + (options.single ? '1' : '0'),null,true,'750');
			
			$chooser.one('chooser_save', function(event) {
				// If in single-selection mode
				if(options.single)
					$ul.find('li').remove();
				
				// Add the labels
				for(var idx in event.labels)
					if(0==$ul.find('input:hidden[value="'+event.values[idx]+'"]').length) {
						var $label = $('<a href="javascript:;" class="cerb-peek-trigger" data-context="cerberusweb.contexts.attachment" />')
							.attr('data-context-id', event.values[idx])
							.text(event.labels[idx])
							.cerbPeekTrigger()
							;
						var $li = $('<li/>').append($label);
						var $hidden = $('<input type="hidden">').attr('name', field_name + (options.single ? '' : '[]')).attr('value', event.values[idx]).appendTo($li);
						var $a = $('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>').appendTo($li);
						
						if(null != options.style)
							$li.addClass(options.style);
						$ul.append($li);
					}
				
				$button.focus();
			});
		});
	}
	
	this.chooserAvatar = function($avatar_chooser, $avatar_image) {
		$avatar_chooser.click(function() {
			var $editor_button = $(this);
			var context = $editor_button.attr('data-context');
			var context_id = $editor_button.attr('data-context-id');
			var image_width = $editor_button.attr('data-image-width');
			var image_height = $editor_button.attr('data-image-height');
			
			var popup_url = 'c=internal&a=chooserOpenAvatar&context=' 
				+ encodeURIComponent(context) 
				+ '&context_id=' + encodeURIComponent(context_id) 
				+ '&image_width=' + encodeURIComponent(image_width) 
				+ '&image_height=' + encodeURIComponent(image_height) 
				;
			
			if($editor_button.attr('data-create-defaults'))
				popup_url += '&defaults=' + encodeURIComponent($editor_button.attr('data-create-defaults'));
			
			var $editor_popup = genericAjaxPopup('avatar_editor', popup_url, null, false, '650');
			
			// Set the default image/url in the chooser
			var evt = new jQuery.Event('cerb-avatar-set-defaults');
			evt.avatar = {
				'imagedata': $avatar_image.attr('src')
			};
			$editor_popup.trigger(evt);
			
			$editor_popup.one('avatar-editor-save', function(e) {
				genericAjaxPopupClose('avatar_editor');
				
				if(undefined == e.avatar || undefined == e.avatar.imagedata)
					return;
				
				if(e.avatar.empty) {
					$avatar_image.attr('src', e.avatar.imagedata);
					$avatar_chooser.next('input:hidden').val('data:null');
				} else {
					$avatar_image.attr('src', e.avatar.imagedata);
					$avatar_chooser.next('input:hidden').val(e.avatar.imagedata);
				}
				
			});
		});
	}
}

var ajax = new cAjaxCalls();

(function ($) {
	
	// Abstract property grid
	
	$.fn.cerbPropertyGrid = function(options) {
		return this.each(function() {
			var $grid = $(this);
			var $properties = $grid.find('> div');
			
			var column_width = parseInt($grid.attr('data-column-width'));
			if(0 == column_width)
				column_width = 100;
			
			$properties.each(function() {
				var $div = $(this);
				var width = $div.width();
				// Round widths to even increments (e.g. auto-span)
				$div.width(Math.ceil(width/column_width)*column_width);
			});
		});
	}
	
	$.fn.cerbCodeEditor = function(options) {
		var langTools = ace.require("ace/ext/language_tools");
	
		return this.each(function(iteration) {
			var $this = $(this);
			
			if(!$this.is('textarea, :text'))
				return;
			
			var mode = $this.attr('data-editor-mode');
			var withTwigAutocompletion = $this.is('.placeholders');
			
			if(null == mode)
				mode = 'ace/mode/twig';
			
			$this
				.removeClass('placeholders')
				.hide()
				;
			
			var editor_id = Devblocks.uniqueId();
			
			var $editor = $('<pre/>')
				.attr('id', editor_id)
				.addClass('placeholders')
				.css('margin', '0')
				.css('position', 'relative')
				.css('width', '100%')
				.insertAfter($this)
			;
			
			var editor = ace.edit(editor_id);
			editor.$blockScrolling = Infinity;
			editor.setTheme("ace/theme/cerb");
			editor.session.setMode(mode);
			editor.session.setValue($this.val());
			
			$this
				.data('$editor', $editor)
				.data('editor', editor)
				;
			
			langTools.setCompleters([]);
			
			$editor.on('cerb.update', function(e) {
				$this.val(editor.session.getValue());
			});
			
			$editor.on('cerb.insertAtCursor', function(e) {
				if(e.replace)
					editor.session.setValue('');
				editor.insertSnippet(e.content);
				editor.focus();
			});
			
			editor.session.on('change', function() {
				$editor.trigger('cerb.update');
			});
			
			editor.setOptions({
				showLineNumbers: true,
				wrap: true,
				enableBasicAutocompletion: true,
				enableSnippets: false,
				enableLiveAutocompletion: true,
				tabSize: 2,
				useSoftTabs: false,
				minLines: 2,
				maxLines: 20
			});	
			
			if(withTwigAutocompletion) {
				var twig_snippets = [
					{ value: "{%", meta: "tag" },
					{ value: "{{", meta: "variable" },
					{ value: "do", snippet: "{% do ${1:1 + 2} %}", meta: "snippet" },
					{ value: "for loop", snippet: "{% for ${1:var} in ${2:array} %}\n${3}\n{% endfor %}", meta: "snippet" },
					{ value: "if...else", snippet: "{% if ${1:placeholder} %}${2}{% else %}${3}{% endif %}", meta: "snippet" },
					{ value: "set object value", snippet: "{% set ${1:obj} = dict_set(${1:obj},\"${2:key.path}\",\"${3:value}\") %}", meta: "snippet" },
					{ value: "set variable", snippet: "{% set var = \"${1}\" %}", meta: "snippet" },
					{ value: "spaceless block", snippet: "{% spaceless %}\n${1}\n{% endspaceless %}\n", meta: "snippet" },
					{ value: "verbatim block", snippet: "{% verbatim %}\n${1}\n{% endverbatim %}\n", meta: "snippet" },
					{ value: "with block", snippet: "{% with %}\n${1}\n{% endwith %}\n", meta: "snippet" },
				];
				
				var twig_tags = [
					{ value: "do", meta: "command" },
					{ value: "endif", meta: "command" },
					{ value: "endfor", meta: "command" },
					{ value: "endspaceless", meta: "command" },
					{ value: "endverbatim", meta: "command" },
					{ value: "endwith", meta: "command" },
					{ value: "filter", meta: "command" },
					{ value: "for", meta: "command" },
					{ value: "if", meta: "command" },
					{ value: "set", meta: "command" },
					{ value: "spaceless", meta: "command" },
					{ value: "verbatim", meta: "command" },
					{ value: "with", meta: "command" },
				];
				
				var twig_filters = [
					{ value: "abs", meta: "filter" },
					{ value: "alphanum", meta: "filter" },
					{ value: "base_convert", snippet: "base_convert(${1:16},${2:10})", meta: "filter" },
					{ value: "base64_decode", meta: "filter" },
					{ value: "base64_encode", meta: "filter" },
					{ value: "base64url_decode", meta: "filter" },
					{ value: "base64url_encode", meta: "filter" },
					{ value: "batch(n,fill)", meta: "filter" },
					{ value: "bytes_pretty()", snippet: "bytes_pretty(${1:2})", meta: "filter" },
					{ value: "capitalize", meta: "filter" },
					{ value: "cerb_translate", meta: "filter" },
					{ value: "context_name()", snippet: "context_name(\"${1:plural}\")", meta: "filter" },
					{ value: "convert_encoding()", snippet: "convert_encoding(${1:to_charset},${2:from_charset})", meta: "filter" },
					{ value: "date('F d, Y')", meta: "filter" },
					{ value: "date_modify('+1 day')", meta: "filter" },
					{ value: "date_pretty", meta: "filter" },
					{ value: "default('text')", meta: "filter" },
					{ value: "escape", meta: "filter" },
					{ value: "first", meta: "filter" },
					{ value: "format", meta: "filter" },
					{ value: "hash_hmac()", snippet: "hash_hmac(\"${1:secret key}\",\"${2:sha256}\")", meta: "filter" },
					{ value: "join(',')", meta: "filter" },
					{ value: "json_encode", meta: "filter" },
					{ value: "json_pretty", meta: "filter" },
					{ value: "keys", meta: "filter" },
					{ value: "last", meta: "filter" },
					{ value: "length", meta: "filter" },
					{ value: "lower", meta: "filter" },
					{ value: "md5", meta: "filter" },
					{ value: "merge", meta: "filter" },
					{ value: "nl2br", meta: "filter" },
					{ value: "number_format(2, '.', ',')", meta: "filter" },
					{ value: "parse_emails", meta: "filter" },
					{ value: "quote", meta: "filter" },
					{ value: "raw", meta: "filter" },
					{ value: "regexp", meta: "filter" },
					{ value: "replace('this', 'that')", meta: "filter" },
					{ value: "reverse", meta: "filter" },
					{ value: "round(0, 'common')", meta: "filter" },
					{ value: "secs_pretty", meta: "filter" },
					{ value: "sha1", meta: "filter" },
					{ value: "slice", meta: "filter" },
					{ value: "sort", meta: "filter" },
					{ value: "split(',')", meta: "filter" },
					{ value: "split_crlf", meta: "filter" },
					{ value: "split_csv", meta: "filter" },
					{ value: "striptags", meta: "filter" },
					{ value: "title", meta: "filter" },
					{ value: "trim", meta: "filter" },
					{ value: "truncate(10)", meta: "filter" },
					{ value: "upper", meta: "filter" },
					{ value: "url_decode", meta: "filter" },
					{ value: "url_decode('json')", meta: "filter" },
					{ value: "url_encode", meta: "filter" },
				];
				
				var twig_functions = [
					{ value: "array_column(array,column_key,index_key)", meta: "function" },
					{ value: "array_combine(keys,values)", meta: "function" },
					{ value: "array_diff(array1,array2)", meta: "function" },
					{ value: "array_intersect(array1,array2)", meta: "function" },
					{ value: "array_sort_keys(array)", meta: "function" },
					{ value: "array_unique(array)", meta: "function" },
					{ value: "array_values(array)", meta: "function" },
					{ value: "attribute(object,attr)", meta: "function" },
					{ value: "cerb_avatar_image(context,id,updated)", meta: "function" },
					{ value: "cerb_avatar_url(context,id,updated)", meta: "function" },
					{ value: "cerb_file_url(file_id,full,proxy)", meta: "function" },
					{ value: "cerb_has_priv(priv,actor_context,actor_id)", meta: "function" },
					{ value: "cerb_placeholders_list()", meta: "function" },
					{ value: "cerb_record_readable(record_context,record_id,actor_context,actor_id)", meta: "function" },
					{ value: "cerb_record_writeable(record_context,record_id,actor_context,actor_id)", meta: "function" },
					{ value: "cerb_url('c=controller&a=action&p=param')", meta: "function" },
					{ value: "cycle(position)", meta: "function" },
					{ value: "date(date,timezone)", meta: "function" },
					{ value: "dict_set(obj,keypath,value)", meta: "function" },
					{ value: "json_decode(string)", meta: "function" },
					{ value: "jsonpath_set(json,keypath,value)", meta: "function" },
					{ value: "max(array)", meta: "function" },
					{ value: "min(array)", meta: "function" },
					{ value: "random(values)", meta: "function" },
					{ value: "random_string(length)", meta: "function" },
					{ value: "range(low,high,step)", snippet: "range(${1:low},${2:high},${3:step})", meta: "function" },
					{ value: "regexp_match_all(pattern,text,group)", meta: "function" },
					{ value: "shuffle(array)", meta: "function" },
					{ value: "validate_email(string)", meta: "function" },
					{ value: "validate_number(string)", meta: "function" },
					{ value: "xml_decode(string,namespaces)", meta: "function" },
					{ value: "xml_encode(xml)", meta: "function" },
					{ value: "xml_path(xml,path,element)", meta: "function" },
					{ value: "xml_path_ns(xml,prefix,ns)", meta: "function" },
				];
				
				var autocompleter = {
					insertMatch: function(editor, data) {
						delete data.completer;
						editor.completer.insertMatch(data);
					},
					getCompletions: function(editor, session, pos, prefix, callback) {
						var token = session.getTokenAt(pos.row, pos.column);
						
						if(token == null) {
							callback(null, twig_snippets.map(function(c) {
								c.completer = autocompleter;
								return c;
							}));
							return;
						}
						
						if(token.type == 'identifier' || (token.type == 'text' && token.start > 0)) {
							var prevToken = session.getTokenAt(pos.row, token.start);
							
							if(prevToken && prevToken.type == 'meta.tag.twig') {
								callback(null, twig_tags.map(function(c) {
									c.completer = autocompleter;
									return c;
								}));
								return;
							}
							
							if(prevToken && prevToken.type == 'keyword.operator.twig') {
								callback(null, twig_functions.map(function(c) {
									c.completer = autocompleter;
									return c;
								}));
								return;
							}
							
							if(prevToken && prevToken.type == 'keyword.operator.other' && prevToken.value == '|') {
								callback(null, twig_filters.map(function(c) {
									c.completer = autocompleter;
									return c;
								}));
								return;
							}
						}
						
						if(token.type == 'meta.tag.twig') {
							var results = [].concat(twig_tags).concat(twig_functions);
							callback(null, results.map(function(c) {
								c.completer = autocompleter;
								return c;
							}));
							return;
						}
						
						if(token.type == 'keyword.operator.other' && token.value == '|') {
							callback(null, twig_filters.map(function(c) {
								c.completer = autocompleter;
								return c;
							}));
							return;
						}
						
						if(token.type == 'variable.other.readwrite.local.twig') {
							callback(null, twig_functions.map(function(c) {
								c.completer = autocompleter;
								return c;
							}));
							return;
						}
						
						callback(false);
					}
				};
				
				langTools.addCompleter(autocompleter);
			}
		});
	};
	
	// Abstract bot interaction trigger
	
	$.fn.cerbBotTrigger = function(options) {
		return this.each(function() {
			var $trigger = $(this);
			
			// Context
			
			$trigger.on('click', function(e) {
				e.stopPropagation();
				
				var interaction = $trigger.attr('data-interaction');
				var interaction_params = $trigger.attr('data-interaction-params');
				var behavior_id = $trigger.attr('data-behavior-id');
				
				var data = {
					"interaction": interaction,
					"browser": {
						"url": window.location.href,
					},
					"params": {}
				};
				
				if(interaction_params && interaction_params.length > 0) {
					var parts = interaction_params.split('&');
					for(var idx in parts) {
						var keyval = parts[idx].split('=');
						data.params[keyval[0]] = decodeURIComponent(keyval[1]);
					}
				}
				
				if(null != behavior_id) {
					data.behavior_id = behavior_id;
				}
				
				// @deprecated
				$.each(this.attributes, function() {
					if('data-interaction-param-' == this.name.substring(0,23)) {
						data.params[this.name.substring(23)] = this.value;
					}
				});
				
				var layer = Devblocks.uniqueId();
				genericAjaxPopup(layer,'c=internal&a=startBotInteraction&' + $.param(data), null, false, '300');
			});
		});
	}
	
	// Abstract query builder
	
	$.fn.cerbQueryTrigger = function(options) {
		return this.each(function() {
			var $trigger = $(this);
			
			if(!($trigger.is('input[type=text]')) && !($trigger.is('textarea')))
				return;
			
			$trigger
				.css('color', 'rgb(100,100,100)')
				.css('cursor', 'text')
				.attr('readonly', 'readonly')
			;
			
			if(null == $trigger.attr('placeholder'))
				$trigger.attr('placeholder', '(click to edit)');
			
			// Context
			
			$trigger.on('click keypress', function(e) {
				e.stopPropagation();
				
				var width = $(window).width()-100;
				var q = $trigger.val();
				var context = $trigger.attr('data-context');
				
				if(!(typeof context == "string") || 0 == context.length)
					return;
				
				var $chooser = genericAjaxPopup("chooser" + Devblocks.uniqueId(),'c=internal&a=chooserOpenParams&context=' + encodeURIComponent(context) + '&q=' + encodeURIComponent(q),null,true,width);
				
				$chooser.on('chooser_save',function(event) {
					$trigger.val(event.worklist_quicksearch);
					
					event.type = 'cerb-query-saved';
					$trigger.trigger(event);
				});
			});
		});
	}
	
	// Abstract template builder
	
	$.fn.cerbTemplateTrigger = function(options) {
		return this.each(function() {
			var $trigger = $(this)
				.css('color', 'rgb(100,100,100)')
				.css('cursor', 'text')
				.attr('readonly', 'readonly')
			;
			
			if(!($trigger.is('textarea')))
				return;
			
			$trigger.on('click', function() {
				var context = $trigger.attr('data-context');
				var label_prefix = $trigger.attr('data-label-prefix');
				var key_prefix = $trigger.attr('data-key-prefix');
				var placeholders_json = $trigger.attr('data-placeholders-json');
				var template = $trigger.val();
				var width = $(window).width()-100;
				
				// Context
				if(!(typeof context == "string") || 0 == context.length)
					return;
				
				var url = 'c=internal&a=editorOpenTemplate&context=' 
					+ encodeURIComponent(context) 
					+ '&template=' + encodeURIComponent(template)
					+ '&label_prefix=' + (label_prefix ? encodeURIComponent(label_prefix) : '')
					+ '&key_prefix=' + (key_prefix ? encodeURIComponent(key_prefix) : '')
					;
				
				
				if(typeof placeholders_json == 'string') {
					var placeholders = JSON.parse(placeholders_json);
					
					if(typeof placeholders == 'object')
					for(key in placeholders) {
						url += "&placeholders[" + encodeURIComponent(key) + ']=' + encodeURIComponent(placeholders[key]);
					}
				}
				
				var $chooser = genericAjaxPopup(
					"template" + Devblocks.uniqueId(),
					url,
					null,
					true,
					width
				);
				
				$chooser.on('template_save',function(event) {
					$trigger.val(event.template);
					event.type = 'cerb-template-saved';
					$trigger.trigger(event);
				});
			});
		});
	}

	// Abstract peeks
	
	$.fn.cerbPeekTrigger = function(options) {
		return this.each(function() {
			var $trigger = $(this);
			
			$trigger.click(function(evt) {
				var context = $trigger.attr('data-context');
				var context_id = $trigger.attr('data-context-id');
				var layer = $trigger.attr('data-layer');
				var width = $trigger.attr('data-width');
				var edit_mode = $trigger.attr('data-edit') ? true : false;
				
				// Context
				if(!(typeof context == "string") || 0 == context.length)
					return;
				
				// Layer
				if(!(typeof layer == "string") || 0 == layer.length)
					//layer = "peek" + Devblocks.uniqueId();
					layer = $.md5(context + ':' + context_id + ':' + (edit_mode ? 'true' : 'false'));
				
				var profile_url = $trigger.attr('data-profile-url');
				
				// Are they also holding SHIFT or CMD?
				if((evt.shiftKey || evt.metaKey) && profile_url) {
					evt.preventDefault();
					evt.stopPropagation();
					window.open(profile_url, '_blank', 'noopener');
					return;
				}
				
				var peek_url = 'c=internal&a=showPeekPopup&context=' + encodeURIComponent(context) + '&context_id=' + encodeURIComponent(context_id);

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
				
				var peek_open_event = new jQuery.Event('cerb-peek-opened');
				peek_open_event.peek_layer = layer;
				peek_open_event.peek_context = context;
				peek_open_event.peek_context_id = context_id;
				peek_open_event.popup_ref = $peek;
				$trigger.trigger(peek_open_event);
				
				$peek.on('peek_saved', function(e) {
					e.type = 'cerb-peek-saved';
					e.context = context;
					$trigger.trigger(e);
				});
				
				$peek.on('peek_deleted', function(e) {
					e.type = 'cerb-peek-deleted';
					e.context = context;
					$trigger.trigger(e);
				});
				
				$peek.on('dialogclose', function(e) {
					$trigger.trigger('cerb-peek-closed');
				});
			});
		});
	}
	
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
				if(!(typeof context == "string") || 0 == context.length)
					return;
				
				// Layer
				if(!(typeof layer == "string") || 0 == layer.length)
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
				
				$peek.on('dialogclose', function(e) {
					$trigger.trigger('cerb-search-closed');
				});
			});
		});
	}
	
	// File drag/drop zones
	
	$.fn.cerbAttachmentsDropZone = function() {
		return this.each(function() {
			var $attachments = $(this);
			
			$attachments.on('dragover', function(e) {
				e.preventDefault();
				e.stopPropagation();
				return false;
			});
			
			$attachments.on('dragenter', function(e) {
				$attachments.css('border', '2px dashed rgb(0,120,0)');
				e.preventDefault();
				e.stopPropagation();
				return false;
			});
			
			$attachments.on('dragleave', function(e) {
				$attachments.css('border', '');
				e.preventDefault();
				e.stopPropagation();
				return false;
			});
			
			$attachments.on('drop', function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				var $spinner = $('<span class="cerb-ajax-spinner"/>').appendTo($attachments);
				
				$attachments.css('border', '');
				
				// Uploads
				
				var jobs = [];
				var labels = [];
				var values = [];
				
				var uploadFunc = function(f, labels, values, callback) {
					var xhr = new XMLHttpRequest();
					var file = f;
					
					if(xhr.upload) {
						xhr.open('POST', DevblocksAppPath + 'ajax.php?c=internal&a=chooserOpenFileAjaxUpload', true);
						xhr.setRequestHeader('X-File-Name', encodeURIComponent(f.name));
						xhr.setRequestHeader('X-File-Type', f.type);
						xhr.setRequestHeader('X-File-Size', f.size);
						xhr.setRequestHeader('X-CSRF-Token', $('meta[name="_csrf_token"]').attr('content'));
						
						xhr.onreadystatechange = function(e) {
							if(xhr.readyState == 4) {
								var json = {};
								if(xhr.status == 200) {
									json = JSON.parse(xhr.responseText);
									labels.push(json.name + ' (' + json.size_label + ')');
									values.push(json.id);
									
								} else {
								}
								
								callback(null, json);
							}
						};
						
						xhr.send(f);
					}
				};
				
				var files = e.originalEvent.dataTransfer.files;
				
				for(var i = 0, f; f = files[i]; i++) {
					jobs.push(
						async.apply(uploadFunc, f, labels, values)
					);
				}
				
				async.series(jobs, function(err, json) {
					var $ul = $attachments.find('ul.chooser-container');
					$attachments.find('span.cerb-ajax-spinner').first().remove();
					
					for(var i = 0; i < json.length; i++) {
						if(0 == $ul.find('input:hidden[value="' + json[i].id + '"]').length) {
							var $hidden = $('<input type="hidden" name="file_ids[]"/>').val(json[i].id);
							var $remove = $('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>');
							var $a = $('<a href="javascript:;"/>')
								.attr('data-context', 'attachment')
								.attr('data-context-id', json[i].id)
								.text(json[i].name + ' (' + json[i].size_label + ')')
								.cerbPeekTrigger()
								;
							var $li = $('<li/>').append($a).append($hidden).append($remove);
							$ul.append($li);
						}
					}
				});
			});
		});
	}
	
	// Abstract choosers
	
	$.fn.cerbChooserTrigger = function() {
		return this.each(function() {
			var $trigger = $(this);
			var $ul = $trigger.siblings('ul.chooser-container');
			
			var field_name = $trigger.attr('data-field-name');
			var context = $trigger.attr('data-context');
			
			// [TODO] If $ul is null, create it
			
			$trigger.click(function() {
				var field_name = $trigger.attr('data-field-name');
				var context = $trigger.attr('data-context');
				
				var query = $trigger.attr('data-query');
				var query_req = $trigger.attr('data-query-required');
				var chooser_url = 'c=internal&a=chooserOpen&context=' + encodeURIComponent(context);
				
				if($trigger.attr('data-single'))
					chooser_url += '&single=1';
				
				if(typeof query == 'string' && query.length > 0) {
					chooser_url += '&q=' + encodeURIComponent(query);
				}
				
				if(typeof query_req == 'string' && query_req.length > 0) {
					chooser_url += '&qr=' + encodeURIComponent(query_req);
				}
				
				var $chooser = genericAjaxPopup(Devblocks.uniqueId(), chooser_url, null, true, '90%');
				
				// [TODO] Trigger open event (if defined)
				
				// [TODO] Bind close event (if defined)
				
				$chooser.one('chooser_save', function(event) {
					// Trigger a selected event
					var evt = jQuery.Event('cerb-chooser-selected');
					evt.labels = event.labels;
					evt.values = event.values;
					$trigger.trigger(evt);
					
					if(typeof event.values == "object" && event.values.length > 0) {
						// Clear previous selections
						if($trigger.attr('data-single'))
							$ul.find('li').remove();
						
						// Check for dupes
						for(i in event.labels) {
							var evt = jQuery.Event('bubble-create');
							evt.label = event.labels[i];
							evt.value = event.values[i];
							$ul.trigger(evt);
						}
						
						$trigger.trigger('cerb-chooser-saved');
					}
				});
			});
			
			// Add remove icons with events
			$ul.find('li').each(function() {
				var $li = $(this);
				var $close = $('<span class="glyphicons glyphicons-circle-remove"></span>').appendTo($li);
			});
			
			// Abstractly create new bubbles
			$ul.on('bubble-create', function(e) {
				var field_name = $trigger.attr('data-field-name');
				var context = $trigger.attr('data-context');
				
				e.stopPropagation();
				var $label = e.label;
				var $value = e.value;
				var icon_url = e.icon;
				
				if(undefined != $label && undefined != $value) {
					if(0 == $ul.find('input:hidden[value="'+$value+'"]').length) {
						var $li = $('<li/>');
						
						var $a = $('<a/>')
							.text($label)
							.attr('href','javascript:;')
							.attr('data-context',context)
							.attr('data-context-id',$value)
							.appendTo($li)
							.cerbPeekTrigger()
							;
						
						if(icon_url && icon_url.length > 0) {
							var $img = $('<img class="cerb-avatar">').attr('src',icon_url).prependTo($li);
						}
						
						var $hidden = $('<input type="hidden">').attr('name', field_name).attr('title', $label).attr('value', $value).appendTo($li);
						var $a = $('<span class="glyphicons glyphicons-circle-remove"></span>').appendTo($li);
						$ul.append($li);
					}
				}
			});
			
			// Catch bubble remove events at the container
			$ul.on('click','> li span.glyphicons-circle-remove', function(e) {
				e.stopPropagation();
				$(this).closest('li').remove();
				$trigger.trigger('cerb-chooser-saved');
			});
			
			// Create
			if($trigger.attr('data-create')) {
				var field_name = $trigger.attr('data-field-name');
				var context = $trigger.attr('data-context');
				var is_create_ifnull = $trigger.attr('data-create') == 'if-null';
				
				var $button = $('<button type="button"/>')
					.addClass('chooser-create')
					.attr('data-context', context)
					.attr('data-context-id', '0')
					.append($('<span class="glyphicons glyphicons-circle-plus"/>'))
					.insertAfter($trigger)
					;
				
				if($trigger.attr('data-create-defaults')) {
					$button.attr('data-edit', $trigger.attr('data-create-defaults'));
				}
				
				$button.cerbPeekTrigger();
				
				// When the record is saved, retrieve the id+label and make a chooser bubble
				$button.on('cerb-peek-saved', function(e) {
					var evt = jQuery.Event('bubble-create');
					evt.label = e.label;
					evt.value = e.id;
					$ul.trigger(evt);
					
					$trigger.trigger('cerb-chooser-saved');
				});
				
				if(is_create_ifnull) {
					if($ul.find('>li').length > 0)
						$button.hide();
					
					$trigger.on('cerb-chooser-saved', function() {
						// If we have zero bubbles, show autocomplete
						if($ul.find('>li').length == 0) {
							$button.show();
						} else { // otherwise, hide it.
							$button.hide();
						}
					});
				}
			}
			
			// Autocomplete
			if(undefined != $trigger.attr('data-autocomplete')) {
				var field_name = $trigger.attr('data-field-name');
				var context = $trigger.attr('data-context');
				var is_single = $trigger.attr('data-single');
				var placeholder = $trigger.attr('data-placeholder');
				var is_autocomplete_ifnull = $trigger.attr('data-autocomplete-if-empty');
				var autocomplete_placeholders = $trigger.attr('data-autocomplete-placeholders');
				var shortcuts = null == $trigger.attr('data-shortcuts') || 'false' != $trigger.attr('data-shortcuts');
				
				var $autocomplete = $('<input type="search" size="32">');
				
				if(placeholder)
					$autocomplete.attr('placeholder', placeholder);
				
				$autocomplete.insertAfter($trigger);
				
				$autocomplete.autocomplete({
					delay: 300,
					source: function(request, response) {
						genericAjaxGet(
							'',
							'c=internal&a=autocomplete&term=' + encodeURIComponent(request.term) + '&context=' + context + '&query=' + encodeURIComponent($trigger.attr('data-autocomplete')),
							function(json) {
								response(json);
							}
						);
					},
					minLength: 1,
					focus:function(event, ui) {
						return false;
					},
					response: function(event, ui) {
						if(!(typeof autocomplete_placeholders == 'string') || 0 == autocomplete_placeholders.length)
							return;
						
						var placeholders = autocomplete_placeholders.split(',');
						
						if(0 == placeholders.length)
							return;
						
						for(var i = 0; i < placeholders.length; i++) {
							var placeholder = $.trim(placeholders[i]);
							ui.content.push({ "label": '(variable) ' + placeholder, "value": placeholder });
						}
					},
					autoFocus:false,
					select:function(event, ui) {
						var $this = $(this);
						
						if($trigger.attr('data-single'))
							$ul.find('li').remove();
						
						var evt = jQuery.Event('bubble-create');
						evt.label = ui.item.label;
						evt.value = ui.item.value;
						
						if(ui.item.icon)
							evt.icon = ui.item.icon;
						
						$ul.trigger(evt);
						
						$trigger.trigger('cerb-chooser-saved');
						
						$this.val('');
						return false;
					}
				});
				
				$autocomplete.autocomplete("instance")._renderItem = function(ul, item) {
					var $div = $("<div/>").text(item.label);
					var $li = $("<li/>").append($div);
					
					if(item.icon) {
						var $img = $('<img class="cerb-avatar" style="height:28px;width:28px;border-radius:28px;float:left;margin-right:5px;">').attr('src',item.icon).prependTo($div);
						$li.css('min-height','32px');
					}
					
					if(typeof item.meta == 'object') {
						for(k in item.meta) {
							var $div = $('<div/>').append($('<small/>').text(item.meta[k]));
							$li.append($div);
						}
					}
					
					$li.appendTo(ul);
					return $li;
				};
				
				if(is_autocomplete_ifnull || is_single) {
					if($ul.find('>li').length > 0) {
						$autocomplete.hide();
					}
					
					$trigger.on('cerb-chooser-saved', function() {
						// If we have zero bubbles, show autocomplete
						if($ul.find('>li').length == 0) {
							$autocomplete.show();
						} else { // otherwise, hide it.
							$autocomplete.hide();
						}
					});
				}
			}
			
			// Show a 'me' shortcut on worker choosers
			if(shortcuts && context == 'cerberusweb.contexts.worker') {
				var $account = $('#lnkSignedIn');
				
				var $button = $('<button type="button"/>')
					.addClass('chooser-shortcut')
					.text('me')
					.click(function() {
						var evt = jQuery.Event('bubble-create');
						evt.label = $account.attr('data-worker-name');
						evt.value = $account.attr('data-worker-id');
						evt.icon = $account.closest('td').find('img:first').attr('src');
						$ul.trigger(evt);
						$trigger.trigger('cerb-chooser-saved');
					})
					.insertAfter($trigger)
					;
				
				if($ul.find('>li').length > 0)
					$button.hide();
				
				$trigger.on('cerb-chooser-saved', function() {
					// If we have zero bubbles, show autocomplete
					if($ul.find('>li').length == 0) {
						$button.show();
					} else { // otherwise, hide it.
						$button.hide();
					}
				});
			}
			
		});
	}
	
}(jQuery));