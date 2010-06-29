var DevblocksClass = function() {
	/* Source: http://bytes.com/forum/thread90068.html */
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
		// Make sure the view form exists
		var viewForm = document.getElementById(form_id);
		if(null == viewForm) return;

		// Make sure the element is present in the form

		var elements = viewForm.elements[element_name];
		if(null == elements) return;

		var len = elements.length;
		var ids = new Array();

		if(null == len && null != elements.value) { // single element
			if(elements.checked)
				ids[0] = elements.value;

		} else { // array
			for(var x=len-1;x>=0;x--) {
				if(elements[x].checked) {
					ids[ids.length] = elements[x].value;
				}
			}
		}

		return ids.join(',');
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
};
var Devblocks = new DevblocksClass();

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

function getEventTarget(e) {
	if(!e) e = event;
	
	if(e.target) {
		return e.target.nodeName;
	} else if (e.srcElement) {
		return e.srcElement.nodeName;
	}
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
		}
	}
}

// [MDF]
function insertAtCursor(field, value) {
	if (document.selection) {
		field.focus();
		sel = document.selection.createRange();
		sel.text = value;
		field.focus();
	} 
	else if (field.selectionStart || field.selectionStart == '0') {
		var startPos = field.selectionStart;
		var endPos = field.selectionEnd;
		var cursorPos = startPos + value.length;

		field.value = field.value.substring(0, startPos) + value	+ field.value.substring(endPos, field.value.length);

		field.selectionStart = cursorPos;
		field.selectionEnd = cursorPos;
	}
	else{
		field.value += value;
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
		bgiframe : true,
		autoOpen : false,
		closeOnEscape : false,
		draggable : false,
		resizable : false,
		modal : true,
		width : "300",
		title : 'Running...'
	};

	if(0 == $("#loadingPanel").length) {
		$("body").append("<div id='loadingPanel' style='display:none;'></div>");
	}

	// Set the content
	$("#loadingPanel").html("This may take a few moments.  Please wait!");
	
	// Render
	loadingPanel = $("#loadingPanel").dialog(options);
	
	loadingPanel.dialog('open');
}

function hideLoadingPanel() {
	loadingPanel.unbind();
	loadingPanel.dialog('destroy');
	loadingPanel = null;
}

function genericAjaxPopupFetch($ns) {
	$devblocksPopups = $('#devblocksPopups');
	
	if(0 == $devblocksPopups.length) {
		$('body').append("<div id='devblocksPopups' style='display:none;'></div>");
		$devblocksPopups = $('#devblocksPopups');
	}
	
	return $devblocksPopups.data($ns);
}

function genericAjaxPopupClose($ns) {
	$popup = genericAjaxPopupFetch($ns);
	if(null != $popup) {
		try {
			$popup.unbind();
			$popup.dialog('close');
		} catch(e) { }
		return true;
	}
	return false;
}

function genericAjaxPopupDestroy($ns) {
	$popup = genericAjaxPopupFetch($ns);
	if(null != $popup) {
		genericAjaxPopupClose($ns);
		try {
			$popup.dialog('destroy');
		} catch(e) { }
		$devblocksPopups.removeData($popup);
		return true;
	}
	return false;
}

function genericAjaxPopupRegister($ns, $popup) {
	$devblocksPopups.data($ns, $popup);
}

function genericAjaxPopup($ns,request,target,modal,width,cb) {
	// Reset (if exists)
	genericAjaxPopupDestroy($ns);
	
	// Default options
	var options = {
		bgiframe : true,
		autoOpen : false,
		closeOnEscape : true,
		draggable : true,
		modal : false,
		stack: true,
		width : "300",
		close: function(event, ui) { 
			$(this).unbind();
		}
	};
	
	if(null != width) options.width = width;
	if(null != modal) options.modal = modal;
	
	// Load the popup content
	genericAjaxGet('',request,
		function(html) {
			$popup = $("#popup"+$ns);
			if(0 == $popup.length) {
				$("body").append("<div id='popup"+$ns+"' style='display:none;'></div>");
				$popup = $('#popup'+$ns);
			}

			// Persist
			genericAjaxPopupRegister($ns, $popup);
			
			// Set the content
			$popup.html(html);
			
			// Target
			if(null != target) {
				var offset = $(target).offset();
				var left = offset.left - $(document).scrollLeft();
				var top = offset.top - $(document).scrollTop();
				options.position = [left, top];
				
			} else {
				options.position = [ 'center', 'top' ];
			}

			// Render
			$popup.dialog(options);
			$popup.dialog('open');
			$popup.trigger('popup_open');
			
			// Callback
			try { cb(html); } catch(e) { }
		}
	);	
}

function genericAjaxPopupPostCloseReloadView($ns, frm, view_id, has_output) {
	var has_view = (null != view_id && view_id.length > 0 && $('#view'+view_id).length > 0) ? true : false;
	if(null == has_output)
		has_output = false;

	if(has_view)
		$('#view'+view_id).fadeTo("normal", 0.2);
	
	genericAjaxPost(frm,view_id,'',
		function(html) {
			if(has_view && has_output) { // Reload from post content
				if(html.length > 0)
					$('#view'+view_id).html(html);
			} else if (has_view && !has_output) { // Reload from view_id
				genericAjaxGet('view'+view_id, 'c=internal&a=viewRefresh&id=' + view_id);
			}

			if(has_view)
				$('#view'+view_id).fadeTo("normal", 1.0);

			$popup = genericAjaxPopupFetch($ns);
			if(null != $popup) {
				$popup.trigger('popup_saved');
				genericAjaxPopupDestroy($ns);
			}
		}
	);
}

function genericAjaxGet(divName,args,cb) {
	if(null == divName || 0 == divName.length)
		divName = 'null';

	if(null == cb) {
		$('#'+divName).fadeTo("normal", 0.2);
		
		var cb = function(html) {
			$('#'+divName).html(html);
			$('#'+divName).fadeTo("normal", 1.0);
			$('#'+divName).trigger('view_refresh');
		}
	}
	
	$.ajax( {
		type: "GET",
		url: DevblocksAppPath+'ajax.php?'+args,
		cache: false,
		success : cb 
	} );
}

function genericAjaxPost(formName,divName,args,cb) {
	if(null == divName || 0 == divName.length)
		divName = 'null';
	
	if(null == cb) {
		$('#'+divName).fadeTo("normal", 0.2);
		
		var cb = function(html) {
			$('#'+divName).html(html);
			$('#'+divName).fadeTo("normal", 1.0);
			$('#'+divName).trigger('view_refresh');
		};
	}

	$.ajax( {
		type: "POST",
		url: DevblocksAppPath+'ajax.php'+(null!=args?('?'+args):''),
		data: $('#'+formName).serialize(),
		cache: false,
		success: cb 
	} );
}

function devblocksAjaxDateChooser(field, div, options) {
	if(null == options)
		options = { 
			changeMonth: true,
			changeYear: true
		} ;
	
	if(null == options.dateFormat)
		options.dateFormat = 'DD, d MM yy';
			
	if(null == div) {
		var chooser = $(field).datepicker(options);
		
	} else {
		if(null == options.onSelect)
			options.onSelect = function(dateText, inst) {
				$(field).val(dateText);
				chooser.datepicker('destroy');					
			};
		var chooser = $(div).datepicker(options);
	}
}
