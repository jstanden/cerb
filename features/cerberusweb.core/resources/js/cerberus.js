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

function appendFileInput(divName,fieldName) {
	var frm = document.getElementById(divName);
	if(null == frm) return;

	// Why is IE such a PITA?  it doesn't allow post-creation specification of the "name" attribute.  Who thought that one up?
	try {
		var fileInput = document.createElement('<input type="file" name="'+fieldName+'" size="45">');
	} catch (err) {
		var fileInput = document.createElement('input');
		fileInput.setAttribute('type','file');
		fileInput.setAttribute('name',fieldName);
		fileInput.setAttribute('size','45');
	}
	
	// Gotta add the <br> as a child, see below
	var brTag = document.createElement('br');
	
	frm.appendChild(fileInput);
	frm.appendChild(brTag);

	// This is effectively the same as frm.innerHTML = frm.innerHTML + "<br>".
	// The innerHTML element doesn't know jack about the selected files of the child elements, so it throws that away.	
	//frm.innerHTML += "<BR>";
}

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
			
			document.location = '#top';
			
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
	
		genericAjaxPopup('peek','c=contacts&a=showAddressBatchPanel&view_id=' + view_id + '&ids=' + row_ids,null,false,'500');
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

			genericAjaxPopupClose('peek');
			
			document.location = '#top';
		});
	}

	this.insertReplyTemplate = function(template_id,txt_name,msgid) {
		genericAjaxGet('','c=display&a=getTemplate&id=' + template_id + '&reply_id='+msgid,
			function(text) {
				var div = document.getElementById(txt_name);
				if(null == div) return;
				
				insertAtCursor(div, text);
				div.focus();

				genericAjaxPopupClose('peek');
			} 
		);
	}

	this.viewTicketsAction = function(view_id,action) {
		var divName = 'view'+view_id;
		var formName = 'viewForm'+view_id;

		showLoadingPanel();

		switch(action) {
			case 'merge':
				genericAjaxPost(formName, '', 'c=tickets&a=viewMergeTickets&view_id='+view_id, function(html) {
					$('#'+divName).html(html);
					hideLoadingPanel();
				});
				break;
			case 'not_spam':
				genericAjaxPost(formName, '', 'c=tickets&a=viewNotSpamTickets&view_id='+view_id, function(html) {
					$('#'+divName).html(html);
					hideLoadingPanel();
				});
				break;
			case 'waiting':
				genericAjaxPost(formName, '', 'c=tickets&a=viewWaitingTickets&view_id='+view_id, function(html) {
					$('#'+divName).html(html);
					hideLoadingPanel();
				});
				break;
			case 'not_waiting':
				genericAjaxPost(formName, '', 'c=tickets&a=viewNotWaitingTickets&view_id='+view_id, function(html) {
					$('#'+divName).html(html);
					hideLoadingPanel();
				});
				break;
			default:
				hideLoadingPanel();
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
					$('#'+divName).html(html);
					hideLoadingPanel();
				});
				break;
			case 2: // delete
				genericAjaxPost(formName, '', 'c=tickets&a=viewDeleteTickets&view_id=' + view_id, function(html) {
					$('#'+divName).html(html);
					hideLoadingPanel();
				});
				break;
			default: // close
				genericAjaxPost(formName, '', 'c=tickets&a=viewCloseTickets&view_id=' + view_id, function(html) {
					$('#'+divName).html(html);
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
				$('#view'+view_id).html(html);
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
			$ul = $(this).siblings('ul.chooser-container:first');
			$chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpen&context=' + context,null,true,'750');
			$chooser.one('chooser_save', function(event) {
				// Add the labels
				for(var idx in event.labels)
					if(0==$ul.find('input:hidden[value='+event.values[idx]+']').length) {
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
						if(0==$ul.find('input:hidden[value='+$value+']').length) {
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
			ctx.push(x);
		
		$chooser=genericAjaxPopup(layer,'c=internal&a=chooserOpenSnippet&context=cerberusweb.contexts.snippet&contexts=' + ctx.join(','),null,false,'650');
		$chooser.bind('snippet_select', function(event) {
			event.stopPropagation();
			
			snippet_id = event.snippet_id;
			context = event.context;

			if(null == snippet_id || null == context)
				return;
			
			// Now we need to read in each snippet as either 'raw' or 'parsed' via Ajax
			url = 'c=internal&a=snippetPaste&id='+snippet_id;
			
			// Context-dependent arguments
			if(null != contexts[context])
				url += "&context_id=" + contexts[context];
			
			// Ajax the content (synchronously)
			genericAjaxGet('',url,function(txt) {
				$textarea.insertAtCursor(txt);
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
					if(0==$ul.find('input:hidden[value='+event.values[idx]+']').length) {
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
