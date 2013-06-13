var markitupMarkdownSettings = {
	previewParserPath:	DevblocksAppPath + 'ajax.php?c=internal&a=transformMarkupToHTML&format=markdown',
	onShiftEnter:		{keepDefault:false, openWith:'\n\n'},
	markupSet: [
		{name:'Heading 1', key:'1', openWith:'# ', placeHolder:'Your title here...', className:'h1' },
		{name:'Heading 2', key:'2', openWith:'## ', placeHolder:'Your title here...', className:'h2' },
		{name:'Heading 3', key:'3', openWith:'### ', placeHolder:'Your title here...', className:'h3' },
		{name:'Heading 4', key:'4', openWith:'#### ', placeHolder:'Your title here...', className:'h4' },
		{name:'Heading 5', key:'5', openWith:'##### ', placeHolder:'Your title here...', className:'h5' },
		{name:'Heading 6', key:'6', openWith:'###### ', placeHolder:'Your title here...', className:'h6' },
		{separator:'---------------', className:'sep' },		
		{name:'Bold', key:'B', openWith:'**', closeWith:'**', className:'b'},
		{name:'Italic', key:'I', openWith:'_', closeWith:'_', className:'i'},
		{separator:'---------------', className:'sep' },
		{name:'Bulleted List', openWith:'- ', className:'ul' },
		{name:'Numeric List', className:'ol', openWith:function(markItUp) {
			return markItUp.line+'. ';
		}},
		{separator:'---------------', className:'sep' },
		{name:'Picture', key:'P', replaceWith:'![[![Alternative text]!]]([![Url:!:http://]!] "[![Title]!]")', className:'img'},
		{name:'Link', key:'L', openWith:'[', closeWith:']([![Url:!:http://]!] "[![Title]!]")', placeHolder:'Your text to link here...', className:'a' },
		{separator:'---------------', className:'sep'},	
		{name:'Quotes', openWith:'> ', className:'blockquote'},
		{name:'Code Format (Class / Variable / File)', openWith:'`', closeWith:'`', className:'code'},
		//{name:'Code Block / Code', openWith:'(!(\t|!|`)!)', closeWith:'(!(`)!)'},
		{separator:'---------------'},
		{name:'Preview', call:'preview', className:"preview"}
	]
}

var markitupHTMLSettings = {
	previewParserPath:	DevblocksAppPath + 'ajax.php?c=internal&a=transformMarkupToHTML&format=html',
	onShiftEnter:	{keepDefault:false, replaceWith:'<br />\n'},
	onCtrlEnter:	{keepDefault:false, openWith:'\n<p>', closeWith:'</p>\n'},
	onTab:			{keepDefault:false, openWith:'	 '},
	markupSet: [
		{name:'Heading 1', key:'1', openWith:'<h1(!( class="[![Class]!]")!)>', closeWith:'</h1>', placeHolder:'Your title here...', className:'h1' },
		{name:'Heading 2', key:'2', openWith:'<h2(!( class="[![Class]!]")!)>', closeWith:'</h2>', placeHolder:'Your title here...', className:'h2' },
		{name:'Heading 3', key:'3', openWith:'<h3(!( class="[![Class]!]")!)>', closeWith:'</h3>', placeHolder:'Your title here...', className:'h3' },
		{name:'Heading 4', key:'4', openWith:'<h4(!( class="[![Class]!]")!)>', closeWith:'</h4>', placeHolder:'Your title here...', className:'h4' },
		{name:'Heading 5', key:'5', openWith:'<h5(!( class="[![Class]!]")!)>', closeWith:'</h5>', placeHolder:'Your title here...', className:'h5' },
		{name:'Heading 6', key:'6', openWith:'<h6(!( class="[![Class]!]")!)>', closeWith:'</h6>', placeHolder:'Your title here...', className:'h6' },
		{name:'Paragraph', openWith:'<p(!( class="[![Class]!]")!)>', closeWith:'</p>', className:'p' },
		{separator:'---------------', className:'sep' },
		{name:'Bold', key:'B', openWith:'(!(<strong>|!|<b>)!)', closeWith:'(!(</strong>|!|</b>)!)', className:'b' },
		{name:'Italic', key:'I', openWith:'(!(<em>|!|<i>)!)', closeWith:'(!(</em>|!|</i>)!)', className:'i' },
		{name:'Stroke through', key:'S', openWith:'<del>', closeWith:'</del>', className:'strike' },
		{separator:'---------------', className:'sep' },
		{name:'Ul', openWith:'<ul>\n', closeWith:'</ul>\n', className:'ul' },
		{name:'Ol', openWith:'<ol>\n', closeWith:'</ol>\n', className:'ol' },
		{name:'Li', openWith:'<li>', closeWith:'</li>', className:'li' },
		{separator:'---------------', className:'sep' },
		{name:'Picture', key:'P', replaceWith:'<img src="[![Source:!:http://]!]" alt="[![Alternative text]!]" />', className:'img' },
		{name:'Link', key:'L', openWith:'<a href="[![Link:!:http://]!]"(!( title="[![Title]!]")!)>', closeWith:'</a>', placeHolder:'Your text to link...', className:'a' },
		{separator:'---------------', className:'sep' },
		{name:'Clean', className:'clean', replaceWith:function(markitup) { return markitup.selection.replace(/<(.*?)>/g, "") } },
		{name:'Preview', className:'preview', call:'preview' }
	]
} 

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
					
					$.ajax({
						url: url,
						dataType: "json",
						data: request,
						success: function(data) {
							response(data);
						}
					});
					
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
			.data('autocomplete')
				._renderItem = function(ul, item) {
					var $li = $('<li></li>')
						.data('item.autocomplete', item)
						.append($('<a></a>').html(item.label))
						.appendTo(ul);
					
					item.value = $li.text();
					
					return $li;
				}
			;
		
		$this
			.on('send', function(e) {
				var $input_date = $(this);
				
				if(!$input_date.is('.changed')) {
					if(e.keydown_event_caller && e.keydown_event_caller.shiftKey && e.keydown_event_caller.ctrlKey && e.keydown_event_caller.which == 13)
						if(options.submit && typeof options.submit == 'function')
							options.submit();
						
					return;
				}
				
				$input_date.autocomplete('close');
				
				genericAjaxGet('', 'c=internal&a=handleSectionAction&section=calendars&action=parseDateJson&date=' + encodeURIComponent($input_date.val()), function(json) {
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
	// [TODO] We don't really need all this
	this.showBatchPanel = function(view_id,target) {
		var viewForm = document.getElementById('viewForm'+view_id);
		if(null == viewForm) return;
		var elements = viewForm.elements['ticket_id[]'];
		if(null == elements) return;

		var len = elements.length;
		var ids = new Array();

		if(null == len && null != elements.value) {
			ids[0] = elements.value;
		} else {
			for(var x=len-1;x>=0;x--) {
				if(elements[x].checked) {
					//frm.appendChild(elements[x]);
					ids[ids.length] = elements[x].value;
				}
			}
		}
		
		var ticket_ids = ids.join(','); // [TODO] Encode?
	
		genericAjaxPopup('peek','c=tickets&a=showBatchPanel&view_id=' + view_id + '&ids=' + ticket_ids,target,false,'500');
	}

	// [TODO] This isn't necessary with *any* other bulk update panel
	this.saveBatchPanel = function(view_id) {
		var divName = 'view'+view_id;
		var formName = 'viewForm'+view_id;
		var viewForm = document.getElementById(formName);
		if(null == viewForm) return;

		var frm = document.getElementById('formBatchUpdate');
		var elements = viewForm.elements['ticket_id[]'];
		if(null == elements) return;
		
		var len = elements.length;
		var ids = new Array();
		
		if(null == len && null != elements.value) {
			ids[0] = elements.value;
		} else {
			for(var x=len-1;x>=0;x--) {
				if(elements[x].checked) {
					ids[ids.length] = elements[x].value;
				}
			}
		}
		
		frm.ticket_ids.value = ids.join(',');

		showLoadingPanel();

		genericAjaxPost('formBatchUpdate', '', 'c=tickets&a=doBulkUpdate', function(html) {
			$('#'+divName).html(html);

			genericAjaxPopupClose('peek');
			
			hideLoadingPanel();
		});
	}

	// [TODO] This is not necessary
	this.showAddressBatchPanel = function(view_id,target) {
		var viewForm = document.getElementById('viewForm'+view_id);
		if(null == viewForm) return;
		var elements = viewForm.elements['row_id[]'];
		if(null == elements) return;

		var len = elements.length;
		var ids = new Array();

		if(null == len && null != elements.value) {
			ids[0] = elements.value;
		} else {
			for(var x=len-1;x>=0;x--) {
				if(elements[x].checked) {
					//frm.appendChild(elements[x]);
					ids[ids.length] = elements[x].value;
				}
			}
		}
		
		var row_ids = ids.join(','); // [TODO] Encode?
	
		genericAjaxPopup('bulk','c=contacts&a=showAddressBatchPanel&view_id=' + view_id + '&ids=' + row_ids,null,false,'500');
	}
	
	// [TODO] This is not necessary
	this.saveAddressBatchPanel = function(view_id) {
		var divName = 'view'+view_id;
		var formName = 'viewForm'+view_id;
		var viewDiv = document.getElementById(divName);
		var viewForm = document.getElementById(formName);
		if(null == viewForm || null == viewDiv) return;

		var frm = document.getElementById('formBatchUpdate');

		var elements = viewForm.elements['row_id[]'];
		if(null == elements) return;
		
		var len = elements.length;
		var ids = new Array();
		
		if(null == len && null != elements.value) {
			ids[0] = elements.value;
		} else {
			for(var x=len-1;x>=0;x--) {
				if(elements[x].checked) {
					ids[ids.length] = elements[x].value;
				}
			}
		}
		
		frm.address_ids.value = ids.join(',');

		genericAjaxPost('formBatchUpdate', '', 'c=contacts&a=doAddressBatchUpdate', function(html) {
			$('#'+divName).html(html);

			genericAjaxPopupClose('bulk');
		});
	}

	this.viewTicketsAction = function(view_id, action) {
		var divName = 'view'+view_id;
		var formName = 'viewForm'+view_id;

		switch(action) {
			case 'merge_popup':
				$popup=genericAjaxPopup('merge','c=tickets&a=viewMergeTicketsPopup&view_id=' + view_id,null,true,'550');
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
		$view = $('#view'+view_id);
		
		post_str = 'c=internal' +
			'&a=viewAddFilter' + 
			'&id=' + view_id +
			'&field=' + encodeURIComponent(field) +
			'&oper=' + encodeURIComponent(oper) +
			'&' + $.param(values, true)  
			;
		
		cb = function(o) {
			$view_filters = $('#viewCustomFilters'+view_id);
			
			if(0 != $view_filters.length) {
				$view_filters.html(o);
				$view_filters.trigger('view_refresh')
			}
		}
		
		options = {};
		options.type = 'POST';
		options.data = post_str; //$('#'+formName).serialize();
		options.url = DevblocksAppPath+'ajax.php';//+(null!=args?('?'+args):''),
		options.cache = false;
		options.success = cb;
		
		$.ajax(options);
	}
	
	this.viewRemoveFilter = function(view_id, fields) {
		$view = $('#view'+view_id);
		
		post_str = 'c=internal' +
			'&a=viewAddFilter' + 
			'&id=' + view_id
			;
		
		for(field in fields) {
			post_str += '&field_deletes[]=' + encodeURIComponent(fields[field]);
		}
		
		cb = function(o) {
			$view_filters = $('#viewCustomFilters'+view_id);
			
			if(0 != $view_filters.length) {
				$view_filters.html(o);
				$view_filters.trigger('view_refresh')
			}
		}
		
		options = {};
		options.type = 'POST';
		options.data = post_str; //$('#'+formName).serialize();
		options.url = DevblocksAppPath+'ajax.php';//+(null!=args?('?'+args):''),
		options.cache = false;
		options.success = cb;
		
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
		var url = DevblocksAppPath+'ajax.php?c=contacts&a=getEmailAutoCompletions';
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
				
				$.ajax({
					url: url,
					dataType: "json",
					data: request,
					success: function(data) {
						response(data);
					}
				});
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
		
		$(sel).autocomplete(options);
	}

	this.orgAutoComplete = function(sel, options) {
		if(null == options) options = { };
		
		options.source = DevblocksAppPath+'ajax.php?c=contacts&a=getOrgsAutoCompletions';
		
		if(null == options.minLength)
			options.minLength = 1;
		
		if(null == options.autoFocus)
			options.autoFocus = true;

		$(sel).autocomplete(options);
	}
	
	this.countryAutoComplete = function(sel, options) {
		if(null == options) options = { };
		
		options.source = DevblocksAppPath+'ajax.php?c=contacts&a=getCountryAutoCompletions';
		
		if(null == options.minLength)
			options.minLength = 1;

		if(null == options.autoFocus)
			options.autoFocus = true;
		
		$(sel).autocomplete(options);
	}

	this.chooser = function(button, context, field_name, options) {
		if(null == field_name)
			field_name = 'context_id';
		
		if(null == options) 
			options = { };
		
		$button = $(button);

		// The <ul> buffer
		$ul = $button.siblings('ul.chooser-container');
		
		// Add the container if it doesn't exist
		if(0==$ul.length) {
			$ul = $('<ul class="bubbles chooser-container"></ul>');
			$ul.insertAfter($button);
		}
		
		// The chooser search button
		$button.click(function(event) {
			$button = $(this);
			var $ul = $(this).siblings('ul.chooser-container:first');
			
			$chooser=genericAjaxPopup('chooser' + new Date().getTime(),'c=internal&a=chooserOpen&context=' + context,null,true,'750');
			$chooser.one('chooser_save', function(event) {
				// Add the labels
				for(var idx in event.labels)
					if(0==$ul.find('input:hidden[value="'+event.values[idx]+'"]').length) {
						$li = $('<li>'+event.labels[idx]+'<input type="hidden" name="' + field_name + '[]" value="'+event.values[idx]+'"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>');
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
			
			$autocomplete = $('<input type="text" class="'+options.autocomplete_class+'" size="45">');
			$autocomplete.insertBefore($button);
			
			$autocomplete.autocomplete({
				source: DevblocksAppPath+'ajax.php?c=internal&a=autocomplete&context=' + context,
				minLength: 1,
				focus:function(event, ui) {
					return false;
				},
				autoFocus:true,
				select:function(event, ui) {
					$this = $(this);
					$label = ui.item.label;
					$labelEscaped = $label.replace("<","&lt;");
					$labelEscaped = $labelEscaped.replace(">","&gt;");
					$value = ui.item.value;
					$ul = $(this).siblings('button:first').siblings('ul.chooser-container:first');
					
					if($label.length > 0 && $value.length > 0) {
						if(0==$ul.find('input:hidden[value="'+$value+'"]').length) {
							$li = $('<li>'+$labelEscaped+'<input type="hidden" name="' + field_name + '[]" title="'+$labelEscaped+'" value="'+$value+'"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>');
							$ul.append($li);
						}
					}
					
					$this.val('');
					return false;
				}
			});
		}
	}
	
	this.chooserSnippet = function(layer, $textarea, contexts) {
		ctx = [];
		for(x in contexts)
			ctx.push(x + ":" + contexts[x]);
		
		$chooser=genericAjaxPopup(layer,'c=internal&a=chooserOpenSnippet&context=cerberusweb.contexts.snippet&contexts=' + ctx.join(','),null,false,'600');
		$chooser.bind('snippet_select', function(event) {
			event.stopPropagation();
			
			snippet_id = event.snippet_id;
			context = event.context;
			
			if(null == snippet_id || null == context)
				return;
			
			// Now we need to read in each snippet as either 'raw' or 'parsed' via Ajax
			url = 'c=internal&a=snippetPaste&id='+encodeURIComponent(snippet_id);
			
			// Context-dependent arguments
			if(null != contexts[context])
				url += "&context_id=" + encodeURIComponent(contexts[context]);
			
			// Ajax the content (synchronously)
			genericAjaxGet('',url,function(txt) {
				if(txt.match(/\(__(.*?)__\)/)) {
					var $popup_paste = genericAjaxPopup('snippet_paste', 'c=internal&a=snippetPlaceholders&text=' + encodeURIComponent(txt),null,false,'600');
					
					$popup_paste.bind('snippet_paste', function(event) {
						if(null == event.text)
							return;
						
						$textarea.insertAtCursor(event.text).focus();
					});
					
				} else {
					$textarea.insertAtCursor(txt).focus();
				}
				
			}, { async: false });
		});
	}
	
	this.chooserFile = function(button, field_name, options) {
		if(null == field_name)
			field_name = 'context_id';
		
		if(null == options) 
			options = { };
		
		$button = $(button);

		// The <ul> buffer
		$ul = $button.next('ul.chooser-container');
		
		// Add the container if it doesn't exist
		if(0==$ul.length) {
			$ul = $('<ul class="bubbles chooser-container"></ul>');
			$ul.insertAfter($button);
		}
		
		// The chooser search button
		$button.click(function(event) {
			$button = $(this);
			$ul = $(this).nextAll('ul.chooser-container:first');
			$chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpenFile',null,true,'750');
			$chooser.one('chooser_save', function(event) {
				// Add the labels
				for(var idx in event.labels)
					if(0==$ul.find('input:hidden[value="'+event.values[idx]+'"]').length) {
						$li = $('<li>'+event.labels[idx]+'<input type="hidden" name="' + field_name + '[]" value="'+event.values[idx]+'"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>');
						if(null != options.style)
							$li.addClass(options.style);
						$ul.append($li);
					}
			});
		});
	}
}

var ajax = new cAjaxCalls();
