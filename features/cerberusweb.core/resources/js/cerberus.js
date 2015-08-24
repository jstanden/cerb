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
		{name:'Link', key:'L', openWith:'[', closeWith:'](http://www.example.com/)', placeHolder:'link text', className:'a' },
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

var atwho_twig_commands = [
	{ name: "{% if placeholder %}{% else %}{% endif %}", content: "If...else" },
	{ name: "{% for placeholder in array %}\n{% endfor %}", content: "For loop" },
	{ name: "{% set var = 'Value' %}", content: "Set temporary variable" },
	{ name: "{% spaceless%}\n{% endspaceless %}\n", content: "Ignore whitespace" },
];

var atwho_twig_modifiers = [
	{ name: "capitalize", content: "Capitalize text" },
	{ name: "date('F d, Y')", content: "Format timestamp as a date" },
	{ name: "date_pretty", content: "Format text as relative date" },
	{ name: "default('text')", content: "Set a default value for an empty placeholder" },
	{ name: "lower", content: "Convert text to lowercase" },
	{ name: "secs_pretty", content: "Format a number of seconds as a time elapsed" },
	{ name: "title", content: "Titlecase text by capitalizing each word" },
	{ name: "upper", content: "Convert text to uppercase" },
	
	{ name: "abs", content: "Return the absolute value of a number" },
	{ name: "bytes_pretty(2)", content: "Format a number as human-readable bytes" },
	{ name: "date_modify('+1 day')", content: "Modify a date or timestamp" },
	{ name: "escape", content: "Escape text for html, js, css, or url" },
	{ name: "first", content: "Return the first element of an array or text" },
	{ name: "format", content: "Replace %s in formatted text with placeholders" },
	{ name: "join(',')", content: "Join array elements into one string" },
	{ name: "json_encode", content: "Encode text as JSON" },
	{ name: "json_pretty", content: "Prettify JSON formatted text" },
	{ name: "keys", content: "Return the keys of an array" },
	{ name: "last", content: "Return the last element of an array or text" },
	{ name: "length", content: "Calculate the length of an array or text" },
	{ name: "md5", content: "Convert text to an MD5 hash" },
	{ name: "merge", content: "Merge multiple arrays together" },
	{ name: "nl2br", content: "Convert newlines to HTML breaks" },
	{ name: "nlp_parse(patterns)", content: "Parse natural language with patterns" },
	{ name: "number_format(2, '.', ',')", content: "Format a number" },
	{ name: "regexp", content: "Match a regular expression" },
	{ name: "replace('this', 'that')", content: "Replace text" },
	{ name: "reverse", content: "Reverse an array or text" },
	{ name: "round(0, 'common')", content: "Round a number: common, ceil, floor" },
	{ name: "slice", content: "Extract a slice of an array or text" },
	{ name: "sort", content: "Sort an array" },
	{ name: "split(',')", content: "Split text into an array by delimiter" },
	{ name: "split_crlf", content: "Split text into an array by linefeeds" },
	{ name: "split_csv", content: "Split text into an array by commas" },
	{ name: "striptags", content: "Strip HTML/XML tags in text" },
	{ name: "trim", content: "Trim whitespace or given characters from the ends of text" },
	{ name: "truncate(10)", content: "Truncate text" },
	{ name: "url_encode", content: "Encode an array or text for use in a URL" },
];

$.fn.cerbDateInputHelper = function(options) {
	var options = (typeof options == 'object') ? options : {};
	
	return this.each(function() {
		var $this = $(this);
		
		$this.datepicker({
			showOn: 'button',
			dateFormat: 'D, d M yy',
			defaultDate: 'D, d M yy',
			onSelect: function(dateText, inst) {
				inst.input.addClass('changed').focus();
			}
		});
		
		$this
			.attr('placeholder', '+2 hours; +4 hours @Calendar; Jan 15 2018 2pm; 5pm America/New York')
			.autocomplete({
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
					var $li = $('<li></li>')
						.data('ui-autocomplete-item', item)
						.append($('<a></a>').html(item.label))
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
			case 'merge_popup':
				genericAjaxPopup('merge','c=tickets&a=viewMergeTicketsPopup&view_id=' + view_id,null,true,'550');
				break;
			case 'merge':
				showLoadingPanel();
				genericAjaxPost(formName, '', 'c=tickets&a=viewMergeTickets&view_id='+view_id, function(html) {
					$('#'+divName).html(html).trigger('view_refresh');
					hideLoadingPanel();
				});
				break;
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
	
	this.viewAddFilter = function(view_id, field, oper, values) {
		var $view = $('#view'+view_id);
		
		var post_str = 'c=internal' +
			'&a=viewAddFilter' + 
			'&id=' + view_id +
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
	
	this.postAndReloadView = function(frm,view_id) {
		
		$('#'+view_id).fadeTo("slow", 0.2);
		
		genericAjaxPost(frm,view_id,'',
			function(html) {
				$('#'+view_id).html(html);
				$('#'+view_id).fadeTo("slow", 1.0);
	
				genericAjaxPopupClose('peek');
			}
		);
	}
	
	this.viewUndo = function(view_id) {
		genericAjaxGet('','c=tickets&a=viewUndo&view_id=' + view_id,
			function(html) {
				$('#view'+view_id).html(html).trigger('view_refresh');
			}
		);		
	}

	this.emailAutoComplete = function(sel, options) {
		var url = DevblocksAppPath+'ajax.php?c=contacts&a=getEmailAutoCompletions&_csrf_token=' + $('meta[name="_csrf_token"]').attr('content');
		if(null == options) options = { };

		if(null == options.minLength)
			options.minLength = 2;
		
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
					$(this).val(value.substring(0,pos)+', '+ui.item.value+', ');
				} else {
					$(this).val(ui.item.value+', ');
				}
				return false;
			}
			
			options.focus = function(event, ui) {
				// Don't replace the textbox value
				return false;
			}
			
		} else {
			options.source = url;
		}
		
		var $sel = $(sel);
		
		$sel.autocomplete(options);
		$sel.autocomplete('widget').css('max-width', $sel.closest('form').width());
	}

	this.orgAutoComplete = function(sel, options) {
		if(null == options) options = { };
		
		options.source = DevblocksAppPath+'ajax.php?c=contacts&a=getOrgsAutoCompletions&_csrf_token=' + $('meta[name="_csrf_token"]').attr('content');
		
		if(null == options.minLength)
			options.minLength = 1;
		
		if(null == options.autoFocus)
			options.autoFocus = true;

		var $sel = $(sel);
		
		$sel.autocomplete(options);
		$sel.autocomplete('widget').css('max-width', $sel.closest('form').width());
	}
	
	this.countryAutoComplete = function(sel, options) {
		if(null == options) options = { };
		
		options.source = DevblocksAppPath+'ajax.php?c=contacts&a=getCountryAutoCompletions&_csrf_token=' + $('meta[name="_csrf_token"]').attr('content');
		
		if(null == options.minLength)
			options.minLength = 1;

		if(null == options.autoFocus)
			options.autoFocus = true;
		
		var $sel = $(sel);
		
		$sel.autocomplete(options);
		$sel.autocomplete('widget').css('max-width', $sel.closest('form').width());
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
			
			var $chooser=genericAjaxPopup('chooser' + new Date().getTime(),'c=internal&a=chooserOpen&context=' + context,null,true,'750');
			$chooser.one('chooser_save', function(event) {
				// Add the labels
				for(var idx in event.labels)
					if(0==$ul.find('input:hidden[value="'+event.values[idx]+'"]').length) {
						var $li = $('<li>'+event.labels[idx]+'<input type="hidden" name="' + field_name + '[]" value="'+event.values[idx]+'"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>');
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
			
			var $autocomplete = $('<input type="text" class="'+options.autocomplete_class+'" size="45">');
			$autocomplete.insertBefore($button);
			
			$autocomplete.autocomplete({
				source: DevblocksAppPath+'ajax.php?c=internal&a=autocomplete&context=' + context + '&_csrf_token=' + $('meta[name="_csrf_token"]').attr('content'),
				minLength: 1,
				focus:function(event, ui) {
					return false;
				},
				autoFocus:true,
				select:function(event, ui) {
					var $this = $(this);
					var $label = ui.item.label;
					var $labelEscaped = $label.replace("<","&lt;").replace(">","&gt;");
					var $value = ui.item.value;
					var $ul = $this.siblings('button:first').siblings('ul.chooser-container:first');
					
					if(undefined != $labelEscaped && undefined != $value) {
						if(0 == $ul.find('input:hidden[value="'+$value+'"]').length) {
							var $li = $('<li>'+$labelEscaped+'<input type="hidden" name="' + field_name + '[]" title="'+$labelEscaped+'" value="'+$value+'"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>');
							$ul.append($li);
						}
					}
					
					$this.val('');
					return false;
				}
			});
			
			$autocomplete.autocomplete('widget').css('max-width', $autocomplete.closest('form').width());
		}
	}
	
	this.chooserSnippet = function(layer, $textarea, contexts) {
		var ctx = [];
		for(x in contexts)
			ctx.push(x + ":" + contexts[x]);
		
		$textarea.focus();
		
		var $chooser = genericAjaxPopup(layer,'c=internal&a=chooserOpenSnippet&context=cerberusweb.contexts.snippet&contexts=' + ctx.join(','),null,false,'600');
		
		$chooser.on('snippet_select', function(event) {
			event.stopPropagation();
			
			var snippet_id = event.snippet_id;
			var context = event.context;
			
			if(null == snippet_id || null == context)
				return;
			
			// Now we need to read in each snippet as either 'raw' or 'parsed' via Ajax
			var url = 'c=internal&a=snippetPaste&id='+encodeURIComponent(snippet_id);
			
			// Context-dependent arguments
			if(null != contexts[context])
				url += "&context_id=" + encodeURIComponent(contexts[context]);
			
			// Ajax the content (synchronously)
			genericAjaxGet('', url, function(json) {
				if(json.has_custom_placeholders) {
					$textarea.focus();
					
					var $popup_paste = genericAjaxPopup('snippet_paste', 'c=internal&a=snippetPlaceholders&id=' + encodeURIComponent(json.id) + '&context_id=' + encodeURIComponent(json.context_id),null,false,'600');
					
					$popup_paste.bind('snippet_paste', function(event) {
						if(null == event.text)
							return;
						
						$textarea.insertAtCursor(event.text);
					});
					
				} else {
					$textarea.insertAtCursor(json.text);
				}
				
			}, { async: false });
		});
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
						var $li = $('<li>'+event.labels[idx]+'<input type="hidden" name="' + field_name + (options.single ? '' : '[]') + '" value="'+event.values[idx]+'"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>');
						if(null != options.style)
							$li.addClass(options.style);
						$ul.append($li);
					}
				
				$button.focus();
			});
		});
	}
}

var ajax = new cAjaxCalls();
