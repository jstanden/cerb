var cerbAutocompleteSuggestions = {
	yamlDashboardFilters: {
		'': [
			'placeholder:',
			'label:',
			'type:',
			'default:',
			{
				caption: '#chooser',
				snippet: '---\nplaceholder: ${1:input_placeholder}\nlabel: ${2:Label}\ntype: chooser\ndefault: ~\nparams:\n  context: ${3:group}\n  single: ${4:false}\n'
			},
			{
				caption: '#date_range',
				snippet: '---\nplaceholder: ${1:input_placeholder}\nlabel: ${2:Label}\ntype: date_range\ndefault: ${3:first day of this month -12 months}\n'
			},
			{
				caption: '#picklist',
				snippet: '---\nplaceholder: ${1:input_placeholder}\nlabel: "${2:By:}"\ntype: picklist\ndefault: ${3:month}\nparams:\n  options:\n  - ${4:day}\n  - week\n  - month\n  - year\n'
			},
		],
		'type:': [
			'date_range',
			'picklist',
			'chooser'
		]
	},
	yamlSheetSchema: {
		'': [
			{
				caption: 'layout:',
				snippet: 'layout:\n  style: ${1:table}\n  headings: ${2:true}\n  paging: ${3:true}\n  #title_column: _label\n',
			},
			{
				caption: 'columns:',
				snippet: 'columns:\n- ',
			}
		],
		
		// Layout
		'layout:': [
			{
				caption: 'style:',
				snippet: 'style: ${1:table}'
			},
			{
				caption: 'headings:',
				snippet: 'headings: ${1:true}'
			},
			{
				caption: 'paging:',
				snippet: 'paging: ${1:true}'
			},
			'title_column: '
		],
		'layout:style:': [
			'table',
			'fieldsets'
		],
		'layout:headings:': [
			'true',
			'false'
		],
		'layout:paging:': [
			'true',
			'false'
			],
		
		// Column types
		'columns:-:': [
			{
				caption: 'text:',
				snippet: 'text:\n    key: ${1:_label}\n    label: ${2:Label}\n    params:\n      #value: literal text\n      #value_key: some_key\n      #value_template: "{{some_key}}"\n      #bold: true\n      #value_map: { 0: No, 1: Yes }\n- '
			},
			{
				caption: 'card:',
				snippet: 'card:\n    key: ${1:_label}\n    label: ${2:Label}\n    params:\n      #image: true\n      #bold: true\n      #underline: false\n- '
			},
			{
				caption: 'date:',
				snippet: 'date:\n    key: ${1:_label}\n    label: ${2:Label}\n    params:\n      #format: d-M-Y H:i:s T # See: https://php.net/date\n      #format: r\n      #value: 1577836800\n      #value_key: updated\n- '
			},
			{
				caption: 'icon:',
				snippet: 'icon:\n    key: ${1:_icon}\n    label: ${2:Icon}\n    params:\n      # See: Setup->Developers->Icon Reference\n      image: ${3:circle-ok}\n- '
			},
			{
				caption: 'link:',
				snippet: 'link:\n    key: ${1:_label}\n    label: ${2:Label}\n    params:\n      href: ${3:/some/path}\n      #href_key: some_key\n      #href_template: /some/path/{{placeholder}}\n      text: ${4:Link text}\n      #text_key: some_key\n- '
			},
			{
				caption: 'search:',
				snippet: 'search:\n    key: ${1:_label}\n    label: ${2:Label}\n    params:\n      context: ticket\n      #context_key: _context\n      query: status:o\n      #query_key: query\n      label: Label\n      #label: count\n- '
			},
			{
				caption: 'search_button:',
				snippet: 'search_button:\n    key: ${1:_label}\n    label: ${2:Label}\n    params:\n      context: ticket\n      #context_key: _context\n      query: status:o\n      #query_key: query      #query_template: status:o owner.id:{{id}}\n- '
			},
			{
				caption: 'slider:',
				snippet: 'slider:\n    key: ${1:_label}\n    label: ${2:Label}\n    params:\n      #value: 75\n      #value_key: some_key\n      #value_template: "{{some_key+50}}"\n      min: 0\n      max: 100\n- '
			},
			{
				caption: 'time_elapsed:',
				snippet: 'time_elapsed:\n    key: ${1:key}\n    label: ${2:Label}\n    params:\n      precision: ${3:2}\n- '
			}
		],
		
		// Text
		'columns:-:text:': [
			'key:',
			'label:',
			'params:'
		],
		'columns:-:text:params:': [
			'value:',
			'value_key:',
			'value_template:',
			'value_map:',
			'bold:',
			'icon:'
		],
		'columns:-:text:params:bold:': [
			'true',
			'false'
		],
		'columns:-:text:params:icon:': [
			'image:',
			'image_key:',
			'image_template:'
		],

		// Cards
		'columns:-:card:': [
			'key:',
			'label:',
			'params:'
		],
		'columns:-:card:params:': [
			'image:',
			'bold:',
			'underline:',
			'context:',
			'context_key:',
			'context_template:',
			'icon:',
			'id:',
			'id_key:',
			'id_template:',
			'label:',
			'label_key:',
			'label_template:'
		],
		'columns:-:card:params:icon:': [
			'image:',
			'image_key:',
			'image_template:'
		],
		'columns:-:card:params:image:': [
			'true',
			'false'
		],
		'columns:-:card:params:bold:': [
			'true',
			'false'
		],
		'columns:-:card:params:underline:': [
			'true',
			'false'
		],
		
		// Dates
		'columns:-:date:': [
			'key:',
			'label:',
			'params:'
		],
		'columns:-:date:params:': [
			'value:',
			'format:',
			'value_key:',
			'value_template:',
			'bold:'
		],
		'columns:-:date:params:bold:': [
			'true',
			'false'
		],
		'columns:-:date:params:format:': [
			'r',
			'Y-m-d H:i:s a'
		],

		// Icon
		'columns:-:icon:': [
			'key:',
			'label:',
			'params:'
		],
		'columns:-:icon:params:': [
			'image:',
			'image_key:',
			'image_template:',
		],

		// Links
		'columns:-:link:': [
			'key:',
			'label:',
			'params:'
		],
		'columns:-:link:params:': [
			'href:',
			'href_key:',
			'href_template:',
			'text:',
			'text_key:',
			'text_template:',
			'bold:'
		],
		'columns:-:link:params:bold:': [
			'true',
			'false'
		],
		
		// Search
		'columns:-:search:': [
			'key:',
			'label:',
			'params:'
		],
		'columns:-:search:params:': [
			'context:',
			'context_key:',
			'context_template:',
			'query:',
			'query_key:',
			'query_template:',
			'label:',
			'label_key:',
			'label_template:',
			'bold:'
		],
		'columns:-:search:params:bold:': [
			'true',
			'false'
		],
		
		// Search button
		'columns:-:search_button:': [
			'key:',
			'label:',
			'params:'
		],
		'columns:-:search_button:params:': [
			'context:',
			'context_key:',
			'context_template:',
			'query:',
			'query_key:',
			'query_template:'
		],
		
		// Slider
		'columns:-:slider:': [
			'key:',
			'label:',
			'params:'
		],
		'columns:-:slider:params:': [
			'value:',
			'value_key:',
			'value_template:',
			'min:',
			'max:'
		],
		
		// Time elapsed
		'columns:-:time_elapsed:': [
			'key:',
			'label:',
			'params:'
		],
		'columns:-:time_elapsed:params:': [
			'value:',
			'value_key:',
			'value_template:',
			'precision:',
			'bold:'
		],
		'columns:-:time_elapsed:params:bold:': [
			'true',
			'false'
		]
	},
	getYamlFormInteractions: function(cb) {
		genericAjaxGet('', 'c=ui&a=yamlSuggestionsFormInteractions', function(json) {
			cb(json);
		});
	}
};

$.fn.cerbDateInputHelper = function(options) {
	options = (typeof options == 'object') ? options : {};
	
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
				$view_filters.trigger('view_refresh');
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
				if(-1 !== pos) {
					// Split at the comma and trim
					request.term = $.trim(request.term.substring(pos+1));
				}
				
				if(0 === request.term.length)
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
				if(-1 !== pos) {
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

		$button.on('cerb-chooser-save', function(event) {
			// If in single-selection mode
			if(options.single)
				$ul.find('li').remove();

			// Add the labels
			for(var idx in event.labels) {
				if (0 === $ul.find('input:hidden[value="' + event.values[idx] + '"]').length) {
					var $label = $('<a href="javascript:;" class="cerb-peek-trigger" data-context="cerberusweb.contexts.attachment" />')
						.attr('data-context-id', event.values[idx])
						.text(event.labels[idx])
						.cerbPeekTrigger()
					;
					var $li = $('<li/>').append($label);
					$('<input type="hidden">').attr('name', field_name + (options.single ? '' : '[]')).attr('value', event.values[idx]).appendTo($li);
					$('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>').appendTo($li);

					if (null != options.style)
						$li.addClass(options.style);

					$ul.append($li);
				}
			}
		});
		
		// The chooser search button
		$button.click(function(event) {
			var $chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpenFile&single=' + (options.single ? '1' : '0'),null,true,'750');
			
			$chooser.one('chooser_save', function(event) {
				var new_event = $.Event(event.type, event);
				new_event.type = 'cerb-chooser-save';
				event.stopPropagation();
				$button.triggerHandler(new_event);
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
			var maxLines = $this.attr('data-editor-lines') || 20;
			var showGutter = $this.attr('data-editor-gutter');
			var showLineNumbers = $this.attr('data-editor-line-numbers');
			var isReadOnly = $this.attr('data-editor-readonly');
			var withTwigAutocompletion = $this.hasClass('placeholders');
			
			var aceOptions = {
				showLineNumbers: 'false' == showLineNumbers ? false : true,
				showGutter: 'false' == showGutter ? false : true,
				showPrintMargin: false,
				wrap: true,
				enableBasicAutocompletion: [],
				enableSnippets: false,
				tabSize: 2,
				useSoftTabs: false,
				minLines: 2,
				maxLines: maxLines,
				readOnly: 'true' == isReadOnly ? true : false
			};
			
			if(null == mode)
				mode = 'ace/mode/twig';
			
			$this
				.removeClass('placeholders')
				.hide()
				;
			
			var editor_id = Devblocks.uniqueId('editor');
			
			var $editor = $('<pre/>')
				.attr('id', editor_id)
				.css('margin', '0')
				.css('position', 'relative')
				.css('width', '100%')
				.insertAfter($this)
				;
			
			if(withTwigAutocompletion)
				$editor.addClass('placeholders');
			
			var editor = ace.edit(editor_id);
			editor.$blockScrolling = Infinity;
			editor.setTheme("ace/theme/cerb");
			editor.session.setMode(mode);
			editor.session.setValue($this.val());

			editor.session.setOption('indentedSoftWrap', false);
			editor.setOption('wrap', true);

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
					{ value: "context_alias", snippet: "context_alias", meta: "filter" },
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
					{ value: "permalink", meta: "filter" },
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
					{ value: "unescape", meta: "filter" },
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
				
				var autocompleterTwig = {
					insertMatch: function(editor, data) {
						data.completer = null;
						editor.completer.insertMatch(data);
					},
					getCompletions: function(editor, session, pos, prefix, callback) {
						var token = session.getTokenAt(pos.row, pos.column);
						
						if(null == token)
							return;
						
						// This should only happen for the Twig editor (not embeds)
						if('ace/mode/twig' == session.getMode().$id) {
							if(token == null) {
								callback(null, twig_snippets.map(function(c) {
									c.score = 5000;
									c.completer = autocompleterTwig;
									return c;
								}));
								return;
							}
						}
						
						if(token.type == 'identifier' || (token.type == 'text' && token.start > 0)) {
							var prevToken = session.getTokenAt(pos.row, token.start);
							
							if(prevToken && prevToken.type == 'meta.tag.twig') {
								callback(null, twig_tags.map(function(c) {
									c.score = 5000;
									c.completer = autocompleterTwig;
									return c;
								}));
								return;
							}
							
							if(prevToken && prevToken.type == 'keyword.operator.twig') {
								callback(null, twig_functions.map(function(c) {
									c.score = 5000;
									c.completer = autocompleterTwig;
									return c;
								}));
								return;
							}
							
							if(prevToken && prevToken.type == 'keyword.operator.other' && prevToken.value == '|') {
								callback(null, twig_filters.map(function(c) {
									c.score = 5000;
									c.completer = autocompleterTwig;
									return c;
								}));
								return;
							}
						}
						
						if(token.type == 'meta.tag.twig') {
							var results = [].concat(twig_tags).concat(twig_functions);
							callback(null, results.map(function(c) {
								c.score = 5000;
								c.completer = autocompleterTwig;
								return c;
							}));
							return;
						}
						
						if(token.type == 'keyword.operator.other' && token.value == '|') {
							callback(null, twig_filters.map(function(c) {
								c.score = 5000;
								c.completer = autocompleterTwig;
								return c;
							}));
							return;
						}
						
						if(token.type == 'variable.other.readwrite.local.twig' && token.value == '{{') {
							callback(null, twig_functions.map(function(c) {
								c.score = 5000;
								c.completer = autocompleterTwig;
								return c;
							}));
							return;
						}
						
						callback(false);
					}
				};
				
				aceOptions.enableBasicAutocompletion.push(autocompleterTwig);
			}
			
			if(mode == 'ace/mode/cerb_query') {
				aceOptions.useSoftTabs = true;
				
			} else if(mode == 'ace/mode/yaml') {
				aceOptions.useSoftTabs = true;
			}
			
			editor.setOptions(aceOptions);
		});
	};

	$.fn.cerbCodeEditorToolbarHtml = function() {
	  return this.each(function() {
	      var $editor_toolbar = $(this);

	      var $pre = $editor_toolbar.nextAll('pre.ace_editor');

	      var editor = ace.edit($pre.attr('id'));

          // Bold
          $editor_toolbar.find('.cerb-html-editor-toolbar-button--bold').on('click', function () {
              var selected_text = editor.getSelectedText();

              if (0 === selected_text.length)
                  return;

              editor.session.replace(editor.getSelectionRange(), '<b>' + selected_text + '</b>');
              editor.focus();
          });

          // Italics
          $editor_toolbar.find('.cerb-html-editor-toolbar-button--italic').on('click', function () {
              var selected_text = editor.getSelectedText();

              if (0 === selected_text.length)
                  return;

              editor.session.replace(editor.getSelectionRange(), '<i>' + selected_text + '</i>');
              editor.focus();
          });

		  // Headings
		  $editor_toolbar.find('.cerb-html-editor-toolbar-button--heading').on('click', function () {
			  var selected_text = editor.getSelectedText();

			  if (0 === selected_text.length)
				  return;

			  editor.session.replace(editor.getSelectionRange(), '<h1>' + selected_text + '</h1>');
			  editor.focus();
		  });

		  // Link
          $editor_toolbar.find('.cerb-html-editor-toolbar-button--link').on('click', function () {
              var selected_text = editor.getSelectedText();

              if (0 === selected_text.length) {
				  editor.insertSnippet('<a href="${1:https://example.com}">${2:link text}</a>');
				  editor.focus();
				  return;
			  }

			  editor.session.replace(editor.getSelectionRange(), '');
              editor.insertSnippet('<a href="${1:https://example.com}">' + selected_text + '</a>');
			  editor.focus();
          });

          // List
          $editor_toolbar.find('.cerb-html-editor-toolbar-button--list').on('click', function () {
              var range = editor.getSelectionRange();

              // [TODO]

              editor.session.indentRows(range.start.row, range.end.row, '* ');
              editor.focus();
          });

          // Image
          $editor_toolbar.find('.cerb-html-editor-toolbar-button--image').on('click', function () {
              var $chooser = genericAjaxPopup('chooser', 'c=internal&a=chooserOpenFile&single=1', null, true, '750');

              $chooser.one('chooser_save', function (event) {
                  var file_id = event.values[0];
                  var file_label = event.labels[0];
                  var file_name = file_label.substring(0, file_label.lastIndexOf(' ('));

                  var url =
                      document.location.protocol
                      + '//'
                      + document.location.host
                      + DevblocksWebPath
                      + 'files/'
                      + encodeURIComponent(file_id) + '/'
                      + encodeURIComponent(file_name)
                  ;

                  $editor_toolbar.triggerHandler(
                      $.Event(
                          'cerb-editor-toolbar-image-inserted',
                          { labels: event.labels, values: event.values, url: url }
                      )
                  );
              });
          });

          // Quote
          $editor_toolbar.find('.cerb-html-editor-toolbar-button--quote').on('click', function () {
			  var selected_text = editor.getSelectedText();

			  if (0 === selected_text.length)
				  return;

			  editor.session.replace(editor.getSelectionRange(), '<blockquote>' + selected_text + '</blockquote>');
			  editor.focus();
          });

          // Code
          $editor_toolbar.find('.cerb-html-editor-toolbar-button--code').on('click', function () {
              var selected_text = editor.getSelectedText();

              if (0 === selected_text.length) {
	              editor.insertSnippet("<pre><code>\n${1:your code goes here}\n</code></pre>\n");
                  editor.focus();
                  return;
              }

              var range = editor.getSelectionRange();

              // If multiple lines, use block format. Otherwise use backticks on vars
              if (range.start.row !== range.end.row) {
                  range.start.column = 0;
                  range.end.row++;
                  range.end.column = 0;
                  editor.selection.setRange(range);
                  editor.session.replace(range, "<pre><code>\n" + editor.getSelectedText() + "</code></pre>\n");

              } else {
                  editor.session.replace(editor.getSelectionRange(), '<var>' + selected_text + '</var>');
              }

              editor.focus();
          });

          // Table
		  $editor_toolbar.find('.cerb-html-editor-toolbar-button--table').on('click', function () {
			editor.insertSnippet('<table>\n<tr>\n<th>Column 1</th>\n<th>Column 2</th>\n</tr>\n<tr>\n<td>Cell 1</td>\n<td>Cell 2</td>\n</tr>\n</table>\n');
		  	editor.focus();
          });
      });
    };

	$.widget('cerb.cerbTextEditor', {
		options: {

		},

		_create: function() {
			this.editor = this.element[0];

			this.element
				.css('width', '100%')
				.css('height', '20em')
			;
		},

		getCursorPosition: function() {
			return this.editor.selectionEnd;
		},

		setCursorPosition: function(index) {
			this.editor.selectionStart = index;
			this.editor.selectionEnd = index;
		},

		getCurrentWordPos: function() {
			var start = this.editor.selectionStart-1;
			var end = this.editor.selectionStart;

			for(var x = start; x >= 0; x--) {
				var char = this.editor.value[x];

				if(char.match(/\s/)) {
					start = x + 1;
					break;
				}

				if(0 === x) {
					start = x;
				}
			}

			return {
				start: start,
				end: end
			};
		},

		getCurrentWord: function() {
			var pos = this.getCurrentWordPos();
			return this.editor.value.substring(pos.start,pos.end);
		},

		selectCurrentWord: function() {
			var pos = this.getCurrentWordPos();
			this.editor.selectionStart = pos.start;
			this.editor.selectionEnd = pos.end;
		},

		replaceCurrentWord: function(replaceWith) {
			this.selectCurrentWord();
			this.replaceSelection(replaceWith);
		},

		getSelection: function() {
			var start = this.editor.selectionStart;
			var end = this.editor.selectionEnd;
			var selectedText = this.editor.value.substring(start,end);
			return selectedText;
		},

		setSelection: function(start, end) {
			this.editor.selectionStart = start;
			this.editor.selectionEnd = end;
		},

		insertText: function(insertText) {
			var start = this.editor.selectionStart;

			var newValue =
				this.editor.value.substring(0,start)
				+ insertText
				+ this.editor.value.substring(start)
			;

			var offset = newValue.length - this.editor.value.length;

			this.editor.value = newValue;

			this.editor.selectionStart = start + offset;
			this.editor.selectionEnd = start + offset;
			this.editor.focus();
		},

		replaceSelection: function(replaceWith) {
			var start = this.editor.selectionStart;
			var end = this.editor.selectionEnd;

			var newValue =
				this.editor.value.substring(0,start)
				+ replaceWith
				+ this.editor.value.substring(end)
			;

			var offset = newValue.length - this.editor.value.length;

			this.editor.value = newValue;

			this.editor.selectionStart = end + offset;
			this.editor.selectionEnd = end + offset;
			this.editor.focus();
		},

		prefixCurrentLine: function(prefixWith) {
			var start = Math.max(0, this.editor.value.substring(0, this.editor.selectionStart).lastIndexOf('\n')+1);
			this.editor.selectionStart = start;
			this.prefixSelection(prefixWith);
		},

		prefixSelectedLines: function(prefixWith) {
			// [TODO]
		},

		prefixSelection: function(prefixWith) {
			var selectedText = this.getSelection();
			this.replaceSelection(prefixWith + selectedText);
		},

		wrapSelection: function(wrapWith) {
			var selectedText = this.getSelection();
			this.replaceSelection(wrapWith + selectedText + wrapWith);
		}
	});

	$.fn.cerbTextEditorToolbarMarkdown = function() {
	  return this.each(function() {
	      var $editor_toolbar = $(this);
	      var $editor = $editor_toolbar.nextAll('textarea');

	      if(0 === $editor.length)
	      	return;

          // Bold
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--bold').on('click', function () {
          	$editor.cerbTextEditor('wrapSelection', '**');
          });

          // Italics
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--italic').on('click', function () {
          	$editor.cerbTextEditor('wrapSelection', '_');
          });

          // Headings
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--heading').on('click', function () {
          	$editor.cerbTextEditor('prefixSelection', '# ');
          });

          // Link
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--link').on('click', function () {
			  var selectedText = $editor.cerbTextEditor('getSelection');

              if (0 === selectedText.length) {
				$editor.cerbTextEditor('insertText', '[link text](https://example.com)');
				return;
			  }

              $editor.cerbTextEditor('replaceSelection', '[' + selectedText + '](https://example.com)');
		  });

          // Image
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--image').on('click', function () {
              var $chooser = genericAjaxPopup('chooser', 'c=internal&a=chooserOpenFile&single=1', null, true, '750');

              $chooser.one('chooser_save', function (event) {
				  var file_id = event.values[0];
				  var file_label = event.labels[0];
				  var file_name = file_label.substring(0, file_label.lastIndexOf(' ('));

				  var url =
					  document.location.protocol
					  + '//'
					  + document.location.host
					  + DevblocksWebPath
					  + 'files/'
					  + encodeURIComponent(file_id) + '/'
					  + encodeURIComponent(file_name)
				  ;

				  $editor_toolbar.triggerHandler(
					  $.Event(
						  'cerb-editor-toolbar-image-inserted',
						  { labels: event.labels, values: event.values, file_id: file_id, file_name: file_name, url: url }
					  )
				  );
              });
          });

		  // List
		  $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--list').on('click', function () {
			  var selectedText = $editor.cerbTextEditor('getSelection');

			  if (0 === selectedText.length) {
			  	$editor.cerbTextEditor('prefixCurrentLine', '* ');
			    return;
			  }

			  if(-1 === selectedText.indexOf("\n")) {
			  	$editor.cerbTextEditor('prefixCurrentLine', '* ');
			  } else {
			  	var quotedText = $.trim(selectedText).replace(new RegExp('\n', 'g'),'\n* ');
			  	$editor.cerbTextEditor('replaceSelection', '* ' + quotedText + '\n');
			  }
		  });

		  // Quote
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--quote').on('click', function () {
			  var selectedText = $editor.cerbTextEditor('getSelection');

			  if (0 === selectedText.length) {
			  	$editor.cerbTextEditor('prefixCurrentLine', '> ');
			    return;
			  }

			  if(-1 === selectedText.indexOf("\n")) {
			  	$editor.cerbTextEditor('prefixCurrentLine', '> ');
			  } else {
			  	var quotedText = $.trim(selectedText).replace(new RegExp('\n', 'g'),'\n> ');
			  	$editor.cerbTextEditor('replaceSelection', '> ' + quotedText + '\n');
			  }
          });

          // Code
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--code').on('click', function () {
			  var selectedText = $editor.cerbTextEditor('getSelection');

              if (0 === selectedText.length) {
              	  $editor.cerbTextEditor('insertText', "~~~\nyour code goes here\n~~~\n");
                  return;
              }

              if(-1 === selectedText.indexOf("\n")) {
              	 $editor.cerbTextEditor('wrapSelection', '`');
			  } else {
              	 $editor.cerbTextEditor('wrapSelection', '~~~\n');
			  }
          });

          // Table
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--table').on('click', function () {
          	  $editor.cerbTextEditor('insertText', "Column | Column\n--- | ---\nValue | Value\n");
          });
      });
    };

	$.fn.cerbCodeEditorToolbarMarkdown = function() {
	  return this.each(function() {
	      var $editor_toolbar = $(this);

	      var $pre = $editor_toolbar.nextAll('pre.ace_editor');

	      var editor = ace.edit($pre.attr('id'));

          // Bold
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--bold').on('click', function () {
              var selected_text = editor.getSelectedText();

              if (0 === selected_text.length)
                  return;

              editor.session.replace(editor.getSelectionRange(), '**' + selected_text + '**');
              editor.focus();
          });

          // Italics
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--italic').on('click', function () {
              var selected_text = editor.getSelectedText();

              if (0 === selected_text.length)
                  return;

              editor.session.replace(editor.getSelectionRange(), '_' + selected_text + '_');
              editor.focus();
          });

          // Headings
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--heading').on('click', function () {
			  var range = editor.getSelectionRange();

			  editor.session.indentRows(range.start.row, range.end.row, '#');
			  editor.focus();
          });

          // Link
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--link').on('click', function () {
              var selected_text = editor.getSelectedText();

              if (0 === selected_text.length) {
                  editor.insertSnippet('[${1:link text}](${2:https://example.com})');
                      editor.focus();
                      return;
                  }

                  editor.session.replace(editor.getSelectionRange(), '');
                  editor.insertSnippet('[' + selected_text + '](${1:https://example.com})');
                      editor.focus();
                  });

          // List
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--list').on('click', function () {
              var range = editor.getSelectionRange();

              editor.session.indentRows(range.start.row, range.end.row, '* ');
              editor.focus();
          });

          // Image
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--image').on('click', function () {
              var $chooser = genericAjaxPopup('chooser', 'c=internal&a=chooserOpenFile&single=1', null, true, '750');

              $chooser.one('chooser_save', function (event) {
				  var file_id = event.values[0];
				  var file_label = event.labels[0];
				  var file_name = file_label.substring(0, file_label.lastIndexOf(' ('));

				  var url =
					  document.location.protocol
					  + '//'
					  + document.location.host
					  + DevblocksWebPath
					  + 'files/'
					  + encodeURIComponent(file_id) + '/'
					  + encodeURIComponent(file_name)
				  ;

				  $editor_toolbar.triggerHandler(
					  $.Event(
						  'cerb-editor-toolbar-image-inserted',
						  { labels: event.labels, values: event.values, file_id: file_id, file_name: file_name, url: url }
					  )
				  );
              });
          });

          // Quote
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--quote').on('click', function () {
              var range = editor.getSelectionRange();

              editor.session.indentRows(range.start.row, range.end.row, '> ');
              editor.focus();
          });

          // Code
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--code').on('click', function () {
              var selected_text = editor.getSelectedText();

              if (0 === selected_text.length) {
              editor.insertSnippet("~~~\n${1:your code goes here}\n~~~\n");
                  editor.focus();
                  return;
              }

              var range = editor.getSelectionRange();

              // If multiple lines, use block format. Otherwise use backticks on vars
              if (range.start.row != range.end.row) {
                  range.start.column = 0;
                  range.end.row++;
                  range.end.column = 0;
                  editor.selection.setRange(range);
                  editor.session.replace(range, "~~~\n" + editor.getSelectedText() + "~~~~\n");

              } else {
                  editor.session.replace(editor.getSelectionRange(), '`' + selected_text + '`');
              }

              editor.focus();
          });

          // Table
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--table').on('click', function () {
              editor.insertSnippet("Column | Column\n--- | ---\nValue | Value\n");
              editor.focus();
          });
      });
    };
	
	$.fn.cerbCodeEditorAutocompleteYaml = function(autocomplete_options) {
		var Autocomplete = require('ace/autocomplete').Autocomplete;
		
		var doCerbLiveAutocomplete = function(e) {
			e.stopPropagation();

			if(!e.editor.completer) {
				var Autocomplete = require('ace/autocomplete').Autocomplete;
				e.editor.completer = new Autocomplete();
			}
			
			if('insertstring' == e.command.name) {
				if(!e.editor.completer.activated || e.editor.completer.isDynamic) {
					if(1 == e.args.length) {
						e.editor.completer.showPopup(e.editor);
					}
				}
			}
		};
		
		return this.each(function() {
			var $editor = $(this)
				.nextAll('pre.ace_editor')
				;
				
			var editor = ace.edit($editor.attr('id'));
			
			if(!editor.completer) {
				editor.completer = new Autocomplete();
			}
			
			editor.completer.autocomplete_suggestions = {};
			
			if(autocomplete_options.autocomplete_suggestions)
				editor.completer.autocomplete_suggestions = autocomplete_options.autocomplete_suggestions;
			
			var autocompleterYaml = {
				formatData: function(scope_key) {
					return editor.completer.autocomplete_suggestions[scope_key].map(function(data) {
						if('object' == typeof data) {
							if(!data.hasOwnProperty('score'))
								data.score = 1000;
							
							return data;
							
						} else if('string' == typeof data) {
							return {
								caption: data,
								snippet: data,
								score: 1000
							};
						}
					});
				},
				getCompletions: function(editor, session, pos, prefix, callback) {
					var token_path = Devblocks.cerbCodeEditor.getYamlTokenPath(pos, editor);

					// Normalize path (remove namespaces)
					token_path = token_path.map(function(v) {
						var pos = v.indexOf('/');
						
						if(-1 == pos)
							return v;
						
						return v.substr(0,pos) + ':';
					});
					
					var scope_key = token_path.join('');

					// Simple static path full match
					if(editor.completer.autocomplete_suggestions.hasOwnProperty(scope_key)) {
						callback(null, autocompleterYaml.formatData(scope_key));
						return;
						
					} else if (editor.completer.autocomplete_suggestions.hasOwnProperty('*')) {
						var regexps = editor.completer.autocomplete_suggestions['*'];
						
						for(var regexp in regexps) {
							if(scope_key.match(new RegExp('^' + regexp + '$'))) {
								editor.completer.autocomplete_suggestions[scope_key] = regexps[regexp];
								callback(null, autocompleterYaml.formatData(scope_key));
								return;
							}
						}
						
						// Negative lookup cache
						editor.completer.autocomplete_suggestions[scope_key] = [];
						
						callback(false);
						return;
						
					} else {
						callback(false);
						return;
					}
				}
			};
			
			editor.setOption('enableBasicAutocompletion', []);
			editor.completers.push(autocompleterYaml);
			editor.commands.on('afterExec', doCerbLiveAutocomplete);
		});
	}

	$.fn.cerbTextEditorAutocompleteComments = function() {
		return this.each(function() {
			var $editor = $(this);
			var editor = $editor[0];

			$editor.autocomplete({
				appendTo: $editor.parent(),
				autoFocus: true,

				_sourceMentions: function(request, response, token) {
					var term = token.substring(1);
					var ajax_requests = [];

					ajax_requests.push(function(callback) {
						var query = 'type:worklist.records of:worker query:(isDisabled:n'
							+ (term.length === 0
								? ' mention:!""'
								: ' (mention:{}*)'.replace(/\{\}/g, term)
							)
							+ ')'
						;

						genericAjaxGet('', 'c=ui&a=dataQuery&q=' + encodeURIComponent(query), function(json) {
							if ('object' != typeof json || !json.hasOwnProperty('data')) {
								return callback(null, []);
							}

							var results = [];

							for (var i in json.data) {
								var worker = json.data[i];

								results.push({
									_type: 'worker',
									label: worker['_label'],
									value: '@' + worker['at_mention_name'],
									title: worker['title'],
									mention: '@' + worker['at_mention_name'],
									image_url: worker['_image_url'],
									id: worker['id']
								});
							}

							return callback(null, results);
						});
					});

					ajax_requests.push(function(callback) {
						var query = 'type:worklist.records of:saved_search query:(context:worker'
							+ (term.length === 0
									? ' tag:!""'
									: ' (tag:{}*)'.replace(/\{\}/g, term)
							)
							+ ')'
						;

						genericAjaxGet('', 'c=ui&a=dataQuery&q=' + encodeURIComponent(query), function(json) {
							if ('object' != typeof json || !json.hasOwnProperty('data')) {
								return callback(null, []);
							}

							var results = [];

							for (var i in json.data) {
								var search = json.data[i];

								results.push({
									_type: 'saved_search',
									label: search['_label'],
									value: '@' + search['tag'],
									image_url: search['_image_url'],
									mention: '@' + search['tag'],
									id: search['id']
								});
							}

							return callback(null, results);
						});
					});

					async.parallelLimit(ajax_requests, 2, function(err, json) {
						if(err)
							return response([]);

						var results = json.reduce(function(arr,val) { return arr.concat(val); });

						return response(results);
					});
				},

				source: function(request, response) {
					var token = $editor.cerbTextEditor('getCurrentWord');

					if(token.startsWith('@')) {
						return this.options._sourceMentions(request, response, token);
					} else {
						response([]);
					}

				},

				select: function(event, ui)  {
					$editor.cerbTextEditor('replaceCurrentWord', ui.item.value);
					return false;
				},

				focus: function(event, ui) {
					return false;
				},

				open: function(event, ui) {
					var $menu = $editor.autocomplete('widget');
					var pos = getCaretCoordinates(editor, editor.selectionEnd);

					$menu
						.css('width', '400px')
						.css('top', (editor.offsetTop - editor.scrollTop + pos.top + 15) + 'px')
						.css('left', (editor.offsetLeft - editor.scrollLeft + pos.left + 5) + 'px')
					;
				}
			})
			.autocomplete( "instance" )._renderItem = function( ul, item ) {
				var $li = $('<li/>');

				if(item.image_url) {
					$('<img/>')
						.addClass('cerb-avatar')
						.attr('src', item.image_url)
						.appendTo($li)
					;
				}

				$('<span/>')
					.text(item.label)
					.appendTo($li)
					;

				if(item.mention) {
					$('<span/>')
						.text(item.mention)
						.css('margin-left', '10px')
						.appendTo($li)
					;
				}

				if(item.title) {
					$('<span/>')
						.text(item.title)
						.css('margin-left', '10px')
						.css('font-weight', 'normal')
						.appendTo($li);
					;
				}

				$li.appendTo(ul);

				return $li;
			};
		});
	}

	$.fn.cerbTextEditorAutocompleteReplies = function() {
		return this.each(function() {
			var $editor = $(this);
			var editor = $editor[0];

			$editor.autocomplete({
				appendTo: $editor.parent(),
				autoFocus: true,

				_sourceCommand: function(request, response, token) {
					var commands = [
						{
							label: '#attach',
							value: '#attach ',
							description: 'Attach a file bundle by alias'
						},
						{
							label: '#comment',
							value: '#comment ',
							description: 'Add a comment with @mention notifications'
						},
						{
							label: '#cut',
							value: '#cut\n',
							description: 'Ignore everything below this line'
						},
						{
							label: '#delete_quote_from_here',
							value: '#delete_quote_from_here',
							description: 'Remove remaining quoted text from this line'
						},
						{
							label: '#signature',
							value: '#signature\n',
							description: 'Insert the signature placeholder'
						},
						{
							label: '#unwatch',
							value: '#unwatch\n',
							description: 'Stop watching this ticket'
						},
						{
							label: '#watch',
							value: '#watch\n',
							description: 'Start watching this ticket'
						}
					];

					// Filter
					if(token.length > 1) {
						return response(commands.filter(function (command) {
							return command.label.startsWith(token);
						}));
					}

					return response(commands);
				},

				_sourceMention: function(request, response, token) {
					var term = token.substring(1);
					var ajax_requests = [];

					ajax_requests.push(function(callback) {
						var query = 'type:worklist.records of:worker query:(isDisabled:n'
							+ (term.length === 0
								? ' mention:!""'
								: ' (mention:{}*)'.replace(/\{\}/g, term)
							)
							+ ')'
						;

						genericAjaxGet('', 'c=ui&a=dataQuery&q=' + encodeURIComponent(query), function(json) {
							if ('object' != typeof json || !json.hasOwnProperty('data')) {
								return callback(null, []);
							}

							var results = [];

							for (var i in json.data) {
								var worker = json.data[i];

								results.push({
									_type: 'worker',
									label: worker['_label'],
									value: '@' + worker['at_mention_name'],
									title: worker['title'],
									mention: '@' + worker['at_mention_name'],
									image_url: worker['_image_url'],
									id: worker['id']
								});
							}

							return callback(null, results);
						});
					});

					ajax_requests.push(function(callback) {
						var query = 'type:worklist.records of:saved_search query:(context:worker'
							+ (term.length === 0
								? ' tag:!""'
								: ' (tag:{}*)'.replace(/\{\}/g, term)
							)
							+ ')'
						;

						genericAjaxGet('', 'c=ui&a=dataQuery&q=' + encodeURIComponent(query), function(json) {
							if ('object' != typeof json || !json.hasOwnProperty('data')) {
								return callback(null, []);
							}

							var results = [];

							for (var i in json.data) {
								var search = json.data[i];

								results.push({
									_type: 'saved_search',
									label: search['_label'],
									value: '@' + search['tag'],
									image_url: search['_image_url'],
									mention: '@' + search['tag'],
									id: search['id']
								});
							}

							return callback(null, results);
						});
					});

					async.parallelLimit(ajax_requests, 2, function(err, json) {
						if(err)
							return response([]);

						var results = json.reduce(function(arr,val) { return arr.concat(val); });

						return response(results);
					});
				},

				source: function(request, response) {
					var token = $editor.cerbTextEditor('getCurrentWord');

					if(token.startsWith('#')) {
						return this.options._sourceCommand(request, response, token);
					} else if(token.startsWith('@')) {
						return this.options._sourceMention(request, response, token);
					} else {
						response([]);
					}
				},

				select: function(event, ui)  {
					if('#delete_quote_from_here' === ui.item.value) {
						$editor.cerbTextEditor('replaceCurrentWord', '');
						var start = $editor.cerbTextEditor('getCursorPosition');
						var value = $editor.val();

						var lines = value.substring(start).split(/\r?\n/g);
						var remainder = [];
						var finished = false;

						for (var i in lines) {
							if (!finished && lines[i].startsWith('>')) {
								continue;
							} else {
								finished = true;
							}

							remainder.push(lines[i]);
						}

						$editor.cerbTextEditor('setSelection', start, value.length);
						$editor.cerbTextEditor('replaceSelection', remainder.join('\n'));
						$editor.cerbTextEditor('setCursorPosition', start);

					} else {
						$editor.cerbTextEditor('replaceCurrentWord', ui.item.value);
					}

					return false;
				},

				focus: function(event, ui) {
					return false;
				},

				open: function(event, ui) {
					var $menu = $editor.autocomplete('widget');
					var pos = getCaretCoordinates(editor, editor.selectionEnd);

					$menu
						.css('width', '400px')
						.css('top', (editor.offsetTop - editor.scrollTop + pos.top + 15) + 'px')
						.css('left', (editor.offsetLeft - editor.scrollLeft + pos.left + 5) + 'px')
					;
				}
			})
			.autocomplete( "instance" )._renderItem = function( ul, item ) {
				var $li = $('<li/>');

				// #commands
				if(item.label.startsWith('#')) {
					$('<span/>')
						.text(item.label)
						.appendTo($li)
					;

					if(item.description) {
						$('<span/>')
							.text(item.description)
							.css('display', 'block')
							.css('margin-left', '10px')
							.css('font-weight', 'normal')
							.appendTo($li)
						;
					}

				// @mentions
				} else {
					if(item.image_url) {
						$('<img/>')
							.addClass('cerb-avatar')
							.attr('src', item.image_url)
							.appendTo($li)
						;
					}

					$('<span/>')
						.text(item.label)
						.appendTo($li)
					;

					if(item.mention) {
						$('<span/>')
							.text(item.mention)
							.css('margin-left', '10px')
							.appendTo($li)
						;
					}

					if(item.title) {
						$('<span/>')
							.text(item.title)
							.css('margin-left', '10px')
							.css('font-weight', 'normal')
							.appendTo($li);
						;
					}
				}

				$li.appendTo(ul);

				return $li;
			};
		});
	}

	$.fn.cerbCodeEditorAutocompleteSearchQueries = function(autocomplete_options) {
		var Autocomplete = require('ace/autocomplete').Autocomplete;
		
		var doCerbLiveAutocomplete = function(e) {
			e.stopPropagation();
			
			if(!e.editor.completer) {
				var Autocomplete = require('ace/autocomplete').Autocomplete;
				e.editor.completer = new Autocomplete();
			}
			
			if('insertstring' == e.command.name) {
				if(!e.editor.completer.activated || e.editor.completer.isDynamic) {
					if(1 == e.args.length) {
						e.editor.completer.showPopup(e.editor);
					}
				}
			}
		};
		
		return this.each(function() {
			var $editor = $(this)
				.nextAll('pre.ace_editor')
				;
				
			var editor = ace.edit($editor.attr('id'));
			
			if(!editor.completer) {
				editor.completer = new Autocomplete();
			}
			
			editor.completer.exactMatch = true;
			
			editor.completer.autocomplete_suggestions = {
				'_contexts': {}
			};
			
			if(autocomplete_options && autocomplete_options.context)
				editor.completer.autocomplete_suggestions._contexts[''] = autocomplete_options.context;
			
			$editor.on('cerb-code-editor-change-context', function(e, context) {
				e.stopPropagation();
				
				var editor = ace.edit($(this).attr('id'));
				
				if(!editor.completer || !editor.completer.autocomplete_suggestions)
					return;
				
				editor.completer.autocomplete_suggestions = {
					'_contexts': {
						'': context || ''
					}
				};
			});
			
			var completer = {
				identifierRegexps: [/[a-zA-Z_0-9\*\#\@\.\$\-\u00A2-\uFFFF]/],
				formatData: function(scope_key) {
					return editor.completer.autocomplete_suggestions[scope_key].map(function(data) {
						if('object' == typeof data) {
							if(!data.hasOwnProperty('score'))
								data.score = 1000;
							
							data.completer = {
								insertMatch: Devblocks.cerbCodeEditor.insertMatchAndAutocomplete
							};
							return data;
							
						} else if('string' == typeof data) {
							return {
								caption: data,
								snippet: data,
								score: 1000,
								completer: {
									insertMatch: Devblocks.cerbCodeEditor.insertMatchAndAutocomplete
								}
							};
						}
					});
				},
				returnCompletions: function(editor, session, pos, prefix, callback) {
					var token = session.getTokenAt(pos.row, pos.column);
					
					// Don't give suggestions inside Twig elements or at the end of `)` sets
					if(token) {
						if(
							('paren.rparen' === token.type)
							|| 'variable.other.readwrite.local.twig' === token.type
							|| ('keyword.operator.other' === token.type && token.value === '|')
						){
							callback(false);
							return;
						}
					}
					
					var token_path = Devblocks.cerbCodeEditor.getQueryTokenPath(pos, editor);
					var scope_key = token_path.scope.join('');
					
					autocomplete_suggestions = editor.completer.autocomplete_suggestions;
					
					editor.completer.isDynamic = false;
					
					// Do we need to lazy load?
					if(autocomplete_suggestions.hasOwnProperty(scope_key)) {
						if($.isArray(autocomplete_suggestions[scope_key])) {
							var results = completer.formatData(scope_key);
							callback(null, results);
							
						} else if('object' == typeof autocomplete_suggestions[scope_key] 
							&& autocomplete_suggestions[scope_key].hasOwnProperty('_type')) {
							
							var key = autocomplete_suggestions[scope_key].hasOwnProperty('key') ? autocomplete_suggestions[scope_key].key : null;
							var limit = autocomplete_suggestions[scope_key].hasOwnProperty('limit') ? autocomplete_suggestions[scope_key].limit : 0;
							var min_length = autocomplete_suggestions[scope_key].hasOwnProperty('min_length') ? autocomplete_suggestions[scope_key].min_length : 0;
							var query = autocomplete_suggestions[scope_key].query.replace('{{term}}', prefix);
							
							if(min_length && prefix.length < min_length) {
								callback(null, []);
								return;
							}
							
							genericAjaxGet('', 'c=ui&a=dataQuery&q=' + encodeURIComponent(query), function(json) {
								var results = [];
								
								if('object' != typeof json || !json.hasOwnProperty('data')) {
									callback('error');
									return;
								}
								
								for(i in json.data) {
									if(!json.data[i].hasOwnProperty(key) || 0 == json.data[i][key].length)
										continue;
									
									var value = json.data[i][key];
									
									results.push({
										value: -1 != value.indexOf(' ') ? ('"' + value + '"') : value
									});
								}
								
								// If we have the full set, persist it
								if('' == prefix && limit && limit > json.data.length) {
									autocomplete_suggestions[scope_key] = results;
									
								} else {
									editor.completer.isDynamic = true;
								}
								
								callback(null, results);
							});
						}
						
					} else {
						// If pasting or existing value, work backwards
						
						(function() {
							var expand = '';
							var expand_prefix = '';
							var expand_context = autocomplete_suggestions._contexts[''] || '';
							
							if(autocomplete_suggestions[scope_key]) {
								editor.completer.showPopup(editor);
								return;
								
							} else if(autocomplete_suggestions._contexts && autocomplete_suggestions._contexts.hasOwnProperty(scope_key)) {
								expand_context = autocomplete_suggestions._contexts[scope_key];
								expand_prefix = scope_key;
								
							} else {
								var stack = [];
								
								for(key in token_path.scope) {
									stack.push(token_path.scope[key]);
									var stack_key = stack.join('');
									
									if(autocomplete_suggestions[stack_key]) {
										expand_prefix += token_path.scope[key];
										
									} else if (autocomplete_suggestions._contexts && autocomplete_suggestions._contexts[stack_key]) {
										expand_context = autocomplete_suggestions._contexts[stack_key];
										expand_prefix += token_path.scope[key];
										
									} else {
										expand += token_path.scope[key];
									}
								}
							}
							
							if('' == expand_context) {
								callback(null, []);
								return;
							}
							
							// [TODO] localStorage cache?
							genericAjaxGet('', 'c=ui&a=querySuggestions&context=' + encodeURIComponent(expand_context) + '&expand=' + encodeURIComponent(expand), function(json) {
								if('object' != typeof json) {
									callback(null, []);
									return;
								}
								
								for(path_key in json) {
									if(path_key == '_contexts') {
										if(!autocomplete_suggestions['_contexts'])
											autocomplete_suggestions['_contexts'] = {};
										
										for(context_key in json[path_key]) {
											autocomplete_suggestions['_contexts'][expand_prefix + context_key] = json[path_key][context_key];
										}
										
									} else {
										autocomplete_suggestions[expand_prefix + path_key] = json[path_key];
									}
								}
								
								if(autocomplete_suggestions[scope_key]) {
									editor.completer.showPopup(editor);
									
								} else {
									callback(null, []);
								}
								return;
							});
						})();
					}
				},
				getCompletions: function(editor, session, pos, prefix, callback) {
					completer.returnCompletions(editor, session, pos, prefix, callback);
				}
			};
		
			editor.setOption('enableBasicAutocompletion', []);
			editor.completers.push(completer);
			editor.commands.on('afterExec', doCerbLiveAutocomplete);
		});
	};

	$.fn.cerbCodeEditorAutocompleteDataQueries = function() {
		var Autocomplete = require('ace/autocomplete').Autocomplete;
		
		var autocomplete_scope = {
			'type': '',
			'of': ''
		};
		
		var autocomplete_suggestions = [];
		var autocomplete_contexts = [];
		
		var autocomplete_suggestions_types = {
			'': [
				'type:'
			],
			'type:': []
		};
		
		var doCerbLiveAutocomplete = function(e) {
			e.stopPropagation();
			
			if(!(
				'insertstring' === e.command.name
				|| 'paste' === e.command.name
				|| 'Return' === e.command.name
				|| 'backspace' === e.command.name)) {
				return;
			}
			
			if(!e.editor.completer) {
				e.editor.completer = new Autocomplete();
			}
			
			var value = e.editor.session.getValue();
			var pos = e.editor.getCursorPosition();
			var current_field = Devblocks.cerbCodeEditor.getQueryTokenPath(pos, e.editor, 1);
			var is_dirty = false;
			
			// If we're in the middle of typing a dynamic series alias, ignore it
			if(1 === current_field.scope.length
				&& 0 === current_field.nodes.length
				&& -1 !== ['series.','values.'].indexOf(current_field.scope[0].substr(0,7))
				) {
				return;
			}
			
			if(0 === value.length) {
				autocomplete_suggestions = {};
				autocomplete_scope.type = '';
				autocomplete_scope.of = '';
				is_dirty = true;
				
			// If we pasted content, rediscover the scope
			} else if('paste' === e.command.name) {
				autocomplete_suggestions = {};
				autocomplete_scope.type = Devblocks.cerbCodeEditor.getQueryTokenValueByPath(e.editor, 'type:') || '';
				autocomplete_scope.of = Devblocks.cerbCodeEditor.getQueryTokenValueByPath(e.editor, 'of:') || '';
				is_dirty = true;
				
			// If we're typing
			} else if(current_field.hasOwnProperty('scope')) {
				var current_field_name = current_field.scope.slice(-1)[0];
				
				if(current_field_name === 'type:' && current_field.nodes[0]) {
					var token_path = Devblocks.cerbCodeEditor.getQueryTokenPath(pos, e.editor);
					
					if(1 === token_path.scope.length) {
						var type = current_field.nodes[0].value;
						
						if(autocomplete_scope.type !== type) {
							autocomplete_scope.type = type;
							autocomplete_scope.of = Devblocks.cerbCodeEditor.getQueryTokenValueByPath(e.editor, 'of:') || '';
							is_dirty = true;
						}
					}
					
				} else if(current_field_name === 'of:' && current_field.nodes[0]) {
					var token_path = Devblocks.cerbCodeEditor.getQueryTokenPath(pos, e.editor);
					
					if(1 === token_path.scope.length) {
						var of = current_field.nodes[0].value;
						
						if(autocomplete_scope.of !== of) {
							autocomplete_scope.of = of;
							
							// If it's not a known context, ignore
							if(-1 !== autocomplete_contexts.indexOf(of)) {
								is_dirty = true;
							}
						}
					
					} else if(-1 !== ['series.','values.'].indexOf(token_path.scope[0].substr(0,7))) {
						var series_key = token_path.scope[0];
						var series_of = token_path.nodes[0].value;
						
						if(autocomplete_scope[series_key + 'of:'] !== series_of) {
							autocomplete_scope[series_key + 'of:'] = series_of;
							
							for(key in autocomplete_suggestions._contexts) {
								if(series_key === key.substr(0,series_key.length))
									autocomplete_suggestions._contexts[key] = null;
							}
							
							for(key in autocomplete_suggestions) {
								if(series_key === key.substr(0,series_key.length))
									autocomplete_suggestions[key] = null;
							}
							
							if(-1 !== autocomplete_contexts.indexOf(series_of)) {
								autocomplete_scope[series_key + 'x:'] = {
									'_type': 'series_of_field'
								};
								
								autocomplete_scope[series_key + 'y:'] = {
									'_type': 'series_of_field'
								};
								
								autocomplete_scope[series_key + 'query:'] = {
									'_type': 'series_of_query'
								};
								
								autocomplete_scope[series_key + 'query.required:'] = {
									'_type': 'series_of_query'
								};
							}
						}
					}
				}
			}
			
			if(is_dirty) {
				var type = autocomplete_scope.type;
				var of = autocomplete_scope.of;
				
				// If type: is invalid
				if('' === type || -1 === autocomplete_suggestions_types['type:'].indexOf(type)) {
					autocomplete_suggestions = autocomplete_suggestions_types;
					
				} else {
					genericAjaxGet('', 'c=ui&a=dataQuerySuggestions&type=' + encodeURIComponent(type) + '&of=' + encodeURIComponent(of), function(json) {
						if('object' == typeof json) {
							autocomplete_suggestions = json;
						} else {
							autocomplete_suggestions = autocomplete_suggestions_types;
						}
					});
				}
			}
			
			if('Return' !== e.command.name && (!e.editor.completer.activated || e.editor.completer.isDynamic)) {
				if(e.args && 1 === e.args.length) {
					e.editor.completer.showPopup(e.editor);
				}
			}
		};
		
		return this.each(function() {
			var $editor = $(this)
				.nextAll('pre.ace_editor')
				;
				
			var editor = ace.edit($editor.attr('id'));
			
			if(!editor.completer) {
				editor.completer = new Autocomplete();
			}
			
			editor.completer.exactMatch = true;
			
			var completer = {
				identifierRegexps: [/[a-zA-Z_0-9\*\#\@\.\$\-\u00A2-\uFFFF]/],
				formatData: function(scope_key) {
					if(!autocomplete_suggestions.hasOwnProperty(scope_key)
						|| undefined == autocomplete_suggestions.hasOwnProperty(scope_key))
						return [];
					
					return autocomplete_suggestions[scope_key].map(function(data) {
						if('object' == typeof data) {
							if(!data.hasOwnProperty('score'))
								data.score = 1000;
							
							data.completer = {
								insertMatch: Devblocks.cerbCodeEditor.insertMatchAndAutocomplete
							};
							return data;
							
						} else if('string' == typeof data) {
							return {
								caption: data,
								snippet: data,
								score: 1000,
								completer: {
									insertMatch: Devblocks.cerbCodeEditor.insertMatchAndAutocomplete
								}
							};
						}
					});
				},
				getCompletions: function(editor, session, pos, prefix, callback) {
					var token = session.getTokenAt(pos);
					
					// Don't give suggestions inside Twig elements
					if(token) {
						if(
							'variable.other.readwrite.local.twig' === token.type
							|| ('keyword.operator.other' === token.type && token.value === '|')
						){
							callback(false);
							return;
						}
					}
					
					var token_path = Devblocks.cerbCodeEditor.getQueryTokenPath(pos, editor);
					var scope_key = token_path.scope.join('');
					
					if(autocomplete_suggestions[scope_key]) {
						if($.isArray(autocomplete_suggestions[scope_key])) {
							var results = completer.formatData(scope_key);
							callback(null, results);
							
						} else if('object' == typeof autocomplete_suggestions[scope_key] 
							&& autocomplete_suggestions[scope_key].hasOwnProperty('_type')) {
							
							if('autocomplete' === autocomplete_suggestions[scope_key]._type) {
								var key = autocomplete_suggestions[scope_key].hasOwnProperty('key') ? autocomplete_suggestions[scope_key].key : null;
								var limit = autocomplete_suggestions[scope_key].hasOwnProperty('limit') ? autocomplete_suggestions[scope_key].limit : 0;
								var min_length = autocomplete_suggestions[scope_key].hasOwnProperty('min_length') ? autocomplete_suggestions[scope_key].min_length : 0;
								var query = autocomplete_suggestions[scope_key].query.replace('{{term}}', prefix);
								
								if(min_length && prefix.length < min_length) {
									callback(null, []);
									return;
								}
								
								genericAjaxGet('', 'c=ui&a=dataQuery&q=' + encodeURIComponent(query), function(json) {
									var results = [];
									
									if('object' != typeof json || !json.hasOwnProperty('data')) {
										callback(null, []);
										return;
									}
									
									for(var i in json.data) {
										if(!json.data[i].hasOwnProperty(key) || 0 == json.data[i][key].length)
											continue;
										
										var value = json.data[i][key];
										
										results.push({
											value: -1 != value.indexOf(' ') ? ('"' + value + '"') : value
										});
									}
									
									// If we have the full set, persist it
									if('' === prefix && limit && limit > json.data.length) {
										autocomplete_suggestions[scope_key] = results;
										
									} else {
										editor.completer.isDynamic = true;
									}
									
									callback(null, results);
									return;
								});
								
							} else if('series_of_query' === autocomplete_suggestions[scope_key]._type) {
								var of = Devblocks.cerbCodeEditor.getQueryTokenValueByPath(editor, token_path.scope[0] + 'of:');
								
								if(!of) {
									callback(null, []);
									return;
								}
								
								genericAjaxGet('', 'c=ui&a=querySuggestions&context=' + encodeURIComponent(of), function(json) {
									if('object' != typeof json) {
										callback(null, []);
										return;
									}
									
									var path_keys = Object.keys(json);
									
									for(var path_key_idx in path_keys) {
										var path_key = path_keys[path_key_idx];
										
										if(path_key === '_contexts') {
											if(!autocomplete_suggestions.hasOwnProperty('_contexts'))
												autocomplete_suggestions['_contexts'] = {};
											
											var context_keys = Object.keys(json[path_key]);
											
											for(context_key_id in context_keys) {
												var context_key = context_keys[context_key_id];
												autocomplete_suggestions['_contexts'][scope_key + context_key] = json[path_key][context_key];
											}
											
										} else {
											autocomplete_suggestions[scope_key + path_key] = json[path_key];
										}
									}
									
									var results = completer.formatData(scope_key);
									callback(null, results);
									return;
								});
								
							} else if('series_of_field' === autocomplete_suggestions[scope_key]._type) {
								var of = autocomplete_scope[token_path.scope[0] + 'of:']
									|| Devblocks.cerbCodeEditor.getQueryTokenValueByPath(editor, token_path.scope[0] + 'of:');
								
								if(!of) {
									callback(null, []);
									return;
								}
								
								var of_types = autocomplete_suggestions[scope_key].of_types || '';
								
								genericAjaxGet('', 'c=ui&a=queryFieldSuggestions&of=' + encodeURIComponent(of) + '&types=' + encodeURIComponent(of_types), function(json) {
									if(!$.isArray(json)) {
										callback(null, []);
										return;
									}
									
									autocomplete_suggestions[scope_key] = json;
									
									var results = completer.formatData(scope_key);
									callback(null, results);
									return;
								});
							}
						}
						
					} else {
						if(
							('object' == typeof token_path && $.isArray(token_path.scope) && 'object' == typeof token_path.scope[0])
							&& (
								('series.' == token_path.scope[0].substr(0,7) && !autocomplete_suggestions[token_path.scope[0]] && autocomplete_suggestions['series.*:'])
								||
								('values.' == token_path.scope[0].substr(0,7) && !autocomplete_suggestions[token_path.scope[0]] && autocomplete_suggestions['values.*:'])
							)) {
							var series_key = token_path.scope[0];
							var series_template_key = token_path.scope[0].substr(0,7) + '*:';
							
							for(var suggest_key in autocomplete_suggestions[series_template_key]) {
								if(autocomplete_suggestions[series_template_key][suggest_key]) {
									autocomplete_suggestions[series_key + suggest_key] = autocomplete_suggestions[series_template_key][suggest_key];
								}
							}
							var series_of = Devblocks.cerbCodeEditor.getQueryTokenValueByPath(editor, token_path.scope[0] + 'of:');
							
							if(series_of && token_path.scope[1] && 'query' === token_path.scope[1].substr(0,5)) {
								if(!autocomplete_suggestions['_contexts'])
									autocomplete_suggestions['_contexts'] = {};
								
								autocomplete_suggestions['_contexts'][token_path.scope.slice(0,2).join('')] = series_of;
								
								// Load the series context
								
								var expand_context = series_of;
								var expand_prefix = token_path.scope.slice(0,2).join('');
								var expand = token_path.scope.slice(2).join('');
								
								genericAjaxGet('', 'c=ui&a=querySuggestions&context=' + encodeURIComponent(expand_context) + '&expand=' + encodeURIComponent(expand), function(json) {
									if('object' != typeof json) {
										callback(null, []);
										return;
									}
									
									for(path_key in json) {
										if(path_key === '_contexts') {
											if(!autocomplete_suggestions['_contexts'])
												autocomplete_suggestions['_contexts'] = {};
											
											for(var context_key in json[path_key]) {
												autocomplete_suggestions['_contexts'][expand_prefix + context_key] = json[path_key][context_key];
											}
											
										} else {
											autocomplete_suggestions[expand_prefix + path_key] = json[path_key];
										}
									}
									
									if(autocomplete_suggestions[scope_key]) {
										editor.completer.showPopup(editor);
										
									} else {
										callback(null, []);
									}
									return;
								});
								
								editor.completer.showPopup(editor);
								return;
								
							} else if(1 === token_path.scope.length) {
								editor.completer.showPopup(editor);
								return;
							}
						}
						
						(function() {
							var expand = '';
							var expand_prefix = '';
							var expand_context = '';
							
							if(autocomplete_suggestions[scope_key]) {
								editor.completer.showPopup(editor);
								return;
								
							} else if(autocomplete_suggestions._contexts && autocomplete_suggestions._contexts.hasOwnProperty(scope_key)) {
								expand_context = autocomplete_suggestions._contexts[scope_key];
								expand_prefix = scope_key;
								
							} else {
								var stack = [];
								
								for(key in token_path.scope) {
									stack.push(token_path.scope[key]);
									var stack_key = stack.join('');
									
									if(autocomplete_suggestions[stack_key]) {
										expand_prefix += token_path.scope[key];
										
									} else if (autocomplete_suggestions._contexts && autocomplete_suggestions._contexts[stack_key]) {
										expand_context = autocomplete_suggestions._contexts[stack_key];
										expand_prefix += token_path.scope[key];
										
									} else {
										expand += token_path.scope[key];
									}
								}
							}
							
							genericAjaxGet('', 'c=ui&a=querySuggestions&context=' + encodeURIComponent(expand_context) + '&expand=' + encodeURIComponent(expand), function(json) {
								if('object' != typeof json) {
									callback(null, []);
									return;
								}
								
								for(path_key in json) {
									if(path_key === '_contexts') {
										if(!autocomplete_suggestions['_contexts'])
											autocomplete_suggestions['_contexts'] = {};
										
										for(var context_key in json[path_key]) {
											autocomplete_suggestions['_contexts'][expand_prefix + context_key] = json[path_key][context_key];
										}
										
									} else {
										autocomplete_suggestions[expand_prefix + path_key] = json[path_key];
									}
								}
								
								if(autocomplete_suggestions[scope_key]) {
									editor.completer.showPopup(editor);
									
								} else {
									callback(null, []);
								}
								return;
							});
						})();
					}
				}
			};
			
			(function() {
				var cerbQuerySuggestionMeta = null;
				
				if(localStorage && localStorage.cerbQuerySuggestionMeta) {
					try {
						cerbQuerySuggestionMeta = JSON.parse(localStorage.cerbQuerySuggestionMeta);
					} catch(ex) {
						cerbQuerySuggestionMeta = null;
					}
				}
				
				// Only run this once everything is ready
				var editor_callback = function() {
					autocomplete_suggestions = autocomplete_suggestions_types;
					
					editor.setOption('enableBasicAutocompletion', []);
					editor.commands.on('afterExec', doCerbLiveAutocomplete);
					editor.completers.push(completer);
					
					editor.on('focus', function(e) {
						var val = editor.getValue();
						
						if(0 === val.length) {
							if(!editor.completer) {
								editor.completer = new Autocomplete();
							}
							
							editor.completer.showPopup(editor);
						}
					});
					
					// If we have default content, trigger a paste
					if(editor.getValue().length > 0) {
						setTimeout(function() {
							editor.commands.exec('paste', editor, {text:''})
						}, 200);
					}
				}
				
				// Do we have a cached copy of the schema meta?
				if(cerbQuerySuggestionMeta
					&& cerbQuerySuggestionMeta.schemaVersion
					&& cerbQuerySuggestionMeta.schemaVersion == CerbSchemaRecordsVersion) {
					
					autocomplete_contexts = cerbQuerySuggestionMeta.recordTypes;
					autocomplete_suggestions_types['type:'] = cerbQuerySuggestionMeta.dataQueryTypes;
					editor_callback.call();
					
				} else {
					genericAjaxGet('', 'c=ui&a=querySuggestionMeta', function(json) {
						if('object' != typeof json)
							return;
						
						autocomplete_contexts = json.recordTypes;
						autocomplete_suggestions_types['type:'] = json.dataQueryTypes;
						
						if(localStorage)
							localStorage.cerbQuerySuggestionMeta = JSON.stringify(json);
						
						editor_callback.call();
					});
				}
			})();
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
				evt.preventDefault();
				evt.stopPropagation();
				
				var context = $trigger.attr('data-context');
				var context_id = $trigger.attr('data-context-id');
				var layer = $trigger.attr('data-layer');
				var width = $trigger.attr('data-width');
				var edit_mode = $trigger.attr('data-edit') ? true : false;
				
				var profile_url = $trigger.attr('data-profile-url');
				
				if(!profile_url && (evt.shiftKey || evt.metaKey))
					edit_mode = true;
				
				// Context
				if(!(typeof context == "string") || 0 == context.length)
					return;
				
				// Layer
				if(!(typeof layer == "string") || 0 == layer.length)
					//layer = "peek" + Devblocks.uniqueId();
					layer = $.md5(context + ':' + context_id + ':' + (edit_mode ? 'true' : 'false'));
				
				if(profile_url && (evt.shiftKey || evt.metaKey)) {
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
					
					if(e.is_new) {
						var new_event = $.Event(e.type, e);
						new_event.type = 'cerb-peek-created';
						$trigger.trigger(new_event);
					}
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

	// Image paste

	$.fn.cerbTextEditorInlineImagePaster = function(options) {
		return this.each(function() {
			var $cursor = $(this);
			var $attachments = options['attachmentsContainer'];
			var $ul = $attachments.find('ul.chooser-container');

			$cursor.on('paste', function(e) {
				e.stopPropagation();

				var files = e.originalEvent.clipboardData.files;

				if(0 === files.length) {
					return;
				}

				e.preventDefault();

				// Uploads

				var jobs = [];
				var labels = [];
				var values = [];

				var uploadFunc = function(f, labels, values, callback) {
					var xhr = new XMLHttpRequest();

					if(xhr.upload) {
						var $spinner = $('<span class="cerb-ajax-spinner"/>')
							.css('zoom', '0.5')
							.css('margin-right', '5px')
						;

						var $status = $('<li/>');

						$status
							.appendTo($ul)
							.append($spinner)
							.append(
								$('<span/>')
									.text('Uploading ' + f.name)
							)
						;

						xhr.open('POST', DevblocksAppPath + 'ajax.php?c=internal&a=chooserOpenFileAjaxUpload', true);
						xhr.setRequestHeader('X-File-Name', encodeURIComponent(f.name));
						xhr.setRequestHeader('X-File-Type', f.type);
						xhr.setRequestHeader('X-File-Size', f.size);
						xhr.setRequestHeader('X-CSRF-Token', $('meta[name="_csrf_token"]').attr('content'));

						xhr.onreadystatechange = function(e) {
							if(xhr.readyState == 4) {
								$status.remove();

								// var json = {};

								if(xhr.status == 200) {
									var json = JSON.parse(xhr.responseText);

									var file_id = json.id;
									var file_name = json.name;
									var file_type = json.type;
									var file_size_label = '(' + json.size_label + ')';

									var url =
										document.location.protocol
										+ '//'
										+ document.location.host
										+ DevblocksWebPath
										+ 'files/'
										+ encodeURIComponent(file_id) + '/'
										+ encodeURIComponent(file_name)
									;

									// Paste at cursor
									if(file_type.lastIndexOf("image/", 0) === 0) {
										$cursor.cerbTextEditor('insertText', '![inline-image](' + url + ")\n");
									}

									// Add to attachments container
									if($ul && 0 === $ul.find('input:hidden[value="' + file_id + '"]').length) {
										var $hidden = $('<input type="hidden" name="file_ids[]"/>').val(file_id);
										var $remove = $('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>');
										var $a = $('<a href="javascript:;"/>')
											.attr('data-context', 'attachment')
											.attr('data-context-id', file_id)
											.text(file_name + ' ' + file_size_label)
											.cerbPeekTrigger()
										;
										var $li = $('<li/>').append($a).append($hidden).append($remove);
										$ul.append($li);
									}
								}

								callback(null);
							}
						};

						xhr.send(f);
					}
				};

				for(var i = 0, f; f = files[i]; i++) {
					jobs.push(
						async.apply(uploadFunc, f, labels, values)
					);
				}

				if(0 === jobs.length)
					return;

				async.parallelLimit(jobs, 2, function(err, json) {
					//if(err)
				});
			});
		});
	}

	$.fn.cerbCodeEditorInlineImagePaster = function(options) {
		return this.each(function() {
			var $cursor = $(this);
			var $attachments = options['attachmentsContainer'];
			var $ul = $attachments.find('ul.chooser-container');

			$cursor.on('paste', function(e) {
				e.preventDefault();
				e.stopPropagation();

				// Uploads

				var jobs = [];
				var labels = [];
				var values = [];

				var uploadFunc = function(f, labels, values, callback) {
					var xhr = new XMLHttpRequest();

					if(xhr.upload) {
						var $spinner = $('<span class="cerb-ajax-spinner"/>')
							.css('zoom', '0.5')
							.css('margin-right', '5px')
						;

						var $status = $('<li/>');

						$status
							.appendTo($ul)
							.append($spinner)
							.append(
								$('<span/>')
									.text('Uploading ' + f.name)
							)
						;

						xhr.open('POST', DevblocksAppPath + 'ajax.php?c=internal&a=chooserOpenFileAjaxUpload', true);
						xhr.setRequestHeader('X-File-Name', encodeURIComponent(f.name));
						xhr.setRequestHeader('X-File-Type', f.type);
						xhr.setRequestHeader('X-File-Size', f.size);
						xhr.setRequestHeader('X-CSRF-Token', $('meta[name="_csrf_token"]').attr('content'));

						xhr.onreadystatechange = function(e) {
							if(xhr.readyState == 4) {
								$status.remove();

								// var json = {};

								if(xhr.status == 200) {
									var json = JSON.parse(xhr.responseText);

									var file_id = json.id;
									var file_name = json.name;
									var file_type = json.type;
									var file_size_label = '(' + json.size_label + ')';

									var url =
										document.location.protocol
										+ '//'
										+ document.location.host
										+ DevblocksWebPath
										+ 'files/'
										+ encodeURIComponent(file_id) + '/'
										+ encodeURIComponent(file_name)
									;

									// Paste at cursor
									if(file_type.lastIndexOf("image/", 0) === 0) {
										options['editor'].insertSnippet('![inline-image](' + url + ")\n");
									}

									// Add to attachments container
									if($ul && 0 === $ul.find('input:hidden[value="' + file_id + '"]').length) {
										var $hidden = $('<input type="hidden" name="file_ids[]"/>').val(file_id);
										var $remove = $('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>');
										var $a = $('<a href="javascript:;"/>')
											.attr('data-context', 'attachment')
											.attr('data-context-id', file_id)
											.text(file_name + ' ' + file_size_label)
											.cerbPeekTrigger()
										;
										var $li = $('<li/>').append($a).append($hidden).append($remove);
										$ul.append($li);
									}
								}

								callback(null);
							}
						};

						xhr.send(f);
					}
				};

				var files = e.originalEvent.clipboardData.files;

				for(var i = 0, f; f = files[i]; i++) {
					jobs.push(
						async.apply(uploadFunc, f, labels, values)
					);
				}

				if(0 === jobs.length)
					return;

				async.parallelLimit(jobs, 2, function(err, json) {
					//if(err)
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