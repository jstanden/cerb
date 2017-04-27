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

var atwho_twig_commands = [
	{ name: "{% do 1 + 2 %}", content: "Perform an operation without output" },
	{ name: "{% if placeholder %}{% else %}{% endif %}", content: "If...else" },
	{ name: "{% filter upper %}\n{% endfilter %}", content: "Apply filters to a block of text" },
	{ name: "{% for placeholder in array %}\n{% endfor %}", content: "For loop" },
	{ name: "{% set var = 'Value' %}", content: "Set temporary variable" },
	{ name: "{% spaceless %}\n{% endspaceless %}\n", content: "Ignore whitespace" },
	{ name: "{% set obj = dict_set(obj,keypath,value) %}", content: "Set a nested value on an object" },
	{ name: "{% verbatim %}\n{% endverbatim %}\n", content: "Ignore scripting code in a text block" },
	{ name: "{% with %}\n{% endwith %}\n", content: "Define temporary values in an inner scope" },
];

var atwho_twig_functions = [
	{ name: "{{array_diff(arr1,arr2)}}", content: "Find the difference of two arrays" },
	{ name: "{{cerb_avatar_image(context,id,updated)}}", content: "Render avatar image tag" },
	{ name: "{{cerb_avatar_url(context,id,updated)}}", content: "Generate an avatar URL" },
	{ name: "{{cerb_file_url(file_id,full,proxy)}}", content: "Generate a file URL" },
	{ name: "{{cerb_url('c=controller&a=action&p=param')}}", content: "Generate a URL" },
	{ name: "{{dict_set(obj,keypath,value)}}", content: "Set a nested value on an object" },
	{ name: "{{json_decode(string)}}", content: "Decode a JSON encoded string" },
	{ name: "{{jsonpath_set(json,keypath,value)}}", content: "Set a nested key value in a JSON object" },
	{ name: "{{random_string(length)}}", content: "Generate a random string of the given length" },
	{ name: "{{regexp_match_all(pattern,text,group)}}", content: "Extract regular expression matches from text" },
	{ name: "{{xml_decode(string,namespaces)}}", content: "Convert text to an XML object" },
	{ name: "{{xml_encode(xml)}}", content: "Convert an XML object to text" },
	{ name: "{{xml_path(xml,path,element)}}", content: "Extract an element from an XML object by XPath" },
	{ name: "{{xml_path_ns(xml,prefix,ns)}}", content: "Register an XML namespace using a prefix" },

	{ name: "{{attribute(object,attr)}}", content: "Dynamically retrieve an attribute on an object" },
	{ name: "{{cycle(position)}}", content: "Cycle through a list of positions on an array" },
	{ name: "{{date(date,timezone)}}", content: "Create a date object" },
	//{ name: "{{dump(var)}}", content: "Debug the contents of a variable" },
	{ name: "{{max(array)}}", content: "Return the largest value in an array" },
	{ name: "{{min(array)}}", content: "Return the smallest value in an array" },
	{ name: "{{random(values)}}", content: "Return a random number or array member" },
	{ name: "{{range(low,high,step)}}", content: "Create a numeric sequence array" },
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
	{ name: "alphanum", content: "Return only the alphanumeric characters from text" },
	{ name: "base64_encode", content: "Encode text in Base64 format" },
	{ name: "base64_decode", content: "Decode Base64 encoded text" },
	{ name: "batch(n,fill)", content: "Batch an array into subarrays of equal size" },
	{ name: "bytes_pretty(2)", content: "Format a number as human-readable bytes" },
	{ name: "convert_encoding(to_charset,from_charset)", content: "Convert between charset encodings" },
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
	{ name: "number_format(2, '.', ',')", content: "Format a number" },
	{ name: "parse_emails", content: "Parse a delimited list of email addresses in text to an array of objects" },
	{ name: "quote", content: "Quote and wrap a block of text like an email response" },
	{ name: "raw", content: "Prevent automatic escaping" },
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
	{ name: "url_decode", content: "Decode URL escaped text" },
	{ name: "url_decode('json')", content: "Decode URL escaped parameters to JSON" },
];

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
	
	this.viewAddFilter = function(view_id, field, oper, values, replace) {
		var $view = $('#view'+view_id);
		
		var post_str = 'c=internal' +
			'&a=viewAddFilter' + 
			'&id=' + view_id +
			'&replace=' + encodeURIComponent(replace ? 1 : 0) +
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
		$sel.autocomplete('widget').css('max-width', $sel.closest('form').width());
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
		$sel.autocomplete('widget').css('max-width', $sel.closest('form').width());
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
				delay: 300,
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
			
			$autocomplete.autocomplete('widget').css('max-width', $autocomplete.closest('form').width());
		}
	}
	
	this.chooserSnippet = function(layer, $textarea, contexts) {
		var ctx = [];
		for(x in contexts)
			ctx.push(x + ":" + contexts[x]);
		
		$textarea.focus();
		
		var $chooser = genericAjaxPopup(layer,'c=internal&a=chooserOpenSnippet&context=cerberusweb.contexts.snippet&contexts=' + ctx.join(','),null,false,'70%');
		
		$chooser.on('snippet_select', function(event) {
			event.stopPropagation();
			
			var snippet_id = event.snippet_id;
			var context = event.context;
			
			if(null == snippet_id || null == context)
				return;
			
			// Now we need to read in each snippet as either 'raw' or 'parsed' via Ajax
			var url = 'c=internal&a=snippetPaste&id=' + encodeURIComponent(snippet_id);
			
			// Context-dependent arguments
			if(null != contexts[context])
				url += "&context_id=" + encodeURIComponent(contexts[context]);
			
			// Ajax the content (synchronously)
			genericAjaxGet('', url, function(json) {
				if(json.has_custom_placeholders) {
					$textarea.focus();
					
					var $popup_paste = genericAjaxPopup('snippet_paste', 'c=internal&a=snippetPlaceholders&id=' + encodeURIComponent(json.id) + '&context_id=' + encodeURIComponent(json.context_id),null,false,'50%');
					
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
			
			var popup_url = 'c=internal&a=chooserOpenAvatar&context=' + encodeURIComponent(context) + '&context_id=' + encodeURIComponent(context_id);
			
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
					$avatar_chooser.siblings('input:hidden[name=avatar_image]').val('data:null');
				} else {
					$avatar_image.attr('src', e.avatar.imagedata);
					$avatar_chooser.siblings('input:hidden[name=avatar_image]').val(e.avatar.imagedata);
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
	
	$.fn.cerbTwigCodeCompletion = function(options) {
		return this.each(function() {
			var $trigger = $(this);
			
			$trigger
				.atwho({
					at: '{%',
					limit: 20,
					displayTpl: '<li>${content} <small style="margin-left:10px;">${name}</small></li>',
					insertTpl: '${name}',
					data: atwho_twig_commands,
					suffix: ''
				})
				.atwho({
					at: '{{',
					limit: 20,
					displayTpl: '<li>${content} <small style="margin-left:10px;">${name}</small></li>',
					insertTpl: '${name}',
					data: atwho_twig_functions,
					suffix: ''
				})
				.atwho({
					at: '|',
					limit: 20,
					startWithSpace: false,
					searchKey: "content",
					displayTpl: '<li>${content} <small style="margin-left:10px;">${name}</small></li>',
					insertTpl: '|${name}',
					data: atwho_twig_modifiers,
					suffix: ''
				})
			;
		});
	};
	
	// Abstract peeks
	
	$.fn.cerbPeekTrigger = function(options) {
		return this.each(function() {
			var $trigger = $(this);
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
			
			$trigger.click(function(evt) {
				var profile_url = $trigger.attr('data-profile-url');
				
				// Are they also holding SHIFT or CMD?
				if((evt.shiftKey || evt.metaKey) && profile_url) {
					evt.preventDefault();
					evt.stopPropagation();
					window.open(profile_url, '_blank');
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
			var context = $trigger.attr('data-context');
			var layer = $trigger.attr('data-layer');
			
			// Context
			if(!(typeof context == "string") || 0 == context.length)
				return;
			
			// Layer
			if(!(typeof layer == "string") || 0 == layer.length)
				layer = "search" + Devblocks.uniqueId();
			
			$trigger.click(function() {
				var query = $trigger.attr('data-query');
				
				var search_url = 'c=search&a=openSearchPopup&context=' + encodeURIComponent(context) + '&q=' + encodeURIComponent(query);
				
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
				
				var files = e.originalEvent.dataTransfer.files;
				var formdata = new FormData();
				
				formdata.append('_csrf_token', $('meta[name="_csrf_token"]').attr('content'));
				
				for(var i = 0; i < files.length; i++) {
					formdata.append('file_data[]', files[i]);
				}
				
				var xhr = new XMLHttpRequest();
				
				xhr.open('POST', DevblocksAppPath + 'ajax.php?c=internal&a=chooserOpenFileUpload');
				xhr.onload = function(e) {
					var $ul = $attachments.find('ul.chooser-container');
					
					$attachments.find('span.cerb-ajax-spinner').first().remove();
					
					if(200 == this.status) {
						var json = JSON.parse(this.responseText);
						
						for(var i = 0; i < json.length; i++) {
							// Only add unique files
							if(0 == $ul.find('input:hidden[value="' + json[i].id + '"]').length) {
								var $hidden = $('<input type="hidden" name="file_ids[]"/>').val(json[i].id);
								var $remove = $('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>');
								var $li = $('<li/>').text(json[i].name + ' (' + json[i].size + ' bytes)').append($hidden).append($remove);
								$ul.append($li);
							}
						}
					}
				};
				
				xhr.upload.onprogress = function(event) {
					if(!event.lengthComputable)
						return;
					
					var complete = (event.loaded / event.total * 100 | 0);
				};
				
				xhr.send(formdata);
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
				var query = $trigger.attr('data-query');
				var chooser_url = 'c=internal&a=chooserOpen&context=' + encodeURIComponent(context);
				
				if($trigger.attr('data-single'))
					chooser_url += '&single=1';
				
				if(typeof query == 'string' && query.length > 0) {
					chooser_url += '&q=' + encodeURIComponent(query);
				}
				
				var $chooser = genericAjaxPopup(Devblocks.uniqueId(), chooser_url, null, true, '90%');
				
				// [TODO] Trigger open event (if defined)
				
				// [TODO] Bind close event (if defined)
				
				$chooser.one('chooser_save', function(event) {
					// [TODO] Trigger choose event (if defined)
					
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
						
						$trigger.trigger('cerb-chooser-saved');
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
				var is_single = $trigger.attr('data-single');
				var is_autocomplete_ifnull = $trigger.attr('data-autocomplete-if-empty');
				var autocomplete_placeholders = $trigger.attr('data-autocomplete-placeholders');
				
				var $autocomplete = $('<input type="search" size="32">');
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
				
				$autocomplete.autocomplete('widget').css('max-width', $autocomplete.closest('form').width());
				
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
			if(context == 'cerberusweb.contexts.worker') {
				var $account = $('#lnkSignedIn');
				
				var $button = $('<button type="button"/>')
					.addClass('.chooser-shortcut')
					.text('me')
					.click(function() {
						var evt = jQuery.Event('bubble-create');
						evt.label = $account.attr('data-worker-name');
						evt.value = $account.attr('data-worker-id');
						evt.icon = $account.closest('td').find('img:first').attr('src');
						$ul.trigger(evt);
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