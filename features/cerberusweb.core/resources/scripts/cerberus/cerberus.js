// ***********
function CreateKeyHandler(cb) {
//	if(is_ie) {
//		func = window.eval("blank=function(e) {return window.cb(e);}");
//	} else {
//		func =  function(e) {return window.cb(e);}
//	}

	if(window.Event) {
		document.captureEvents(Event.KEYDOWN);
	}
	
	document.onkeydown = cb;
}

function getKeyboardKey(evt,as_code) {
	var browser=navigator.userAgent.toLowerCase();
	var is_ie=(browser.indexOf("msie")!=-1 && document.all);
	
	  if(window.Event) {
	  	if(evt.altKey || evt.metaKey || evt.ctrlKey) {
	  		return;
	  	}
	    mykey = evt.which;
	  }
	  else if(event) {
	  	evt = event;
	  	if((evt.modifiers & event.ALT_MASK) || (evt.modifiers & event.CTRL_MASK)) {
			return;
		}
	  	if(evt.altKey || evt.metaKey || evt.ctrlKey) { // new style
	  		return;
	  	}
   		mykey = evt.keyCode
	  }
	  
	  mychar = String.fromCharCode(mykey);
	  
	var src=null;
	
	try {
		if(evt.srcElement) src=evt.srcElement;
		else if(evt.target) src=evt.target;
	}
	catch(e) {}

	if(null == src) {
		return;
	}
  
	for(var element=src;element!=null;element=element.parentNode) {
		var nodename=element.nodeName;
		if(nodename=="TEXTAREA"	
			|| (nodename=="SELECT")
			|| (nodename=="INPUT") //  && element.type != "checkbox"
			|| (nodename=="BUTTON")
			)
			{ return; }
	}
	
	return (null==as_code||!as_code) ? mychar : mykey;
}

// ***********

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
	
		genericAjaxPanel('c=tickets&a=showBatchPanel&view_id=' + view_id + '&ids=' + ticket_ids,target,false,'500');
	}

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

		genericAjaxPost('formBatchUpdate', '', 'c=tickets&a=doBatchUpdate', function(html) {
			$('#'+divName).html(html);

			if(null != genericPanel) {
				genericPanel.dialog("close");
			}
			
			document.location = '#top';
			genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
			
			hideLoadingPanel();
		});
	}

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
	
		genericAjaxPanel('c=contacts&a=showAddressBatchPanel&view_id=' + view_id + '&ids=' + row_ids,null,false,'500');
	}
	
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

			if(null != genericPanel) {
				genericPanel.dialog("close");
			}
			
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
				
				try {
					genericPanel.dialog("close");
				} catch(e) { } 
			} 
		);
	}

	this.viewMoveTickets = function(view_id) {
		var divName = 'view'+view_id;
		var formName = 'viewForm'+view_id;

		genericAjaxPost(formName, divName, 'c=tickets&a=viewMoveTickets&view_id='+view_id, function(html) {
			$('#'+divName).html(html);
			genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
		});
	}

	this.viewTicketsAction = function(view_id,action) {
		var divName = 'view'+view_id;
		var formName = 'viewForm'+view_id;

		showLoadingPanel();

		switch(action) {
			case 'merge':
				genericAjaxPost(formName, '', 'c=tickets&a=viewMergeTickets&view_id='+view_id, function(html) {
					$('#'+divName).html(html);
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
					hideLoadingPanel();
				});
				break;
			case 'not_spam':
				genericAjaxPost(formName, '', 'c=tickets&a=viewNotSpamTickets&view_id='+view_id, function(html) {
					$('#'+divName).html(html);
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
					hideLoadingPanel();
				});
				break;
			case 'take':
				genericAjaxPost(formName, '', 'c=tickets&a=viewTakeTickets&view_id='+view_id, function(html) {
					$('#'+divName).html(html);
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
					hideLoadingPanel();
				});
				break;
			case 'surrender':
				genericAjaxPost(formName, '', 'c=tickets&a=viewSurrenderTickets&view_id='+view_id, function(html) {
					$('#'+divName).html(html);
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
					hideLoadingPanel();
				});
				break;
			case 'waiting':
				genericAjaxPost(formName, '', 'c=tickets&a=viewWaitingTickets&view_id='+view_id, function(html) {
					$('#'+divName).html(html);
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
					hideLoadingPanel();
				});
				break;
			case 'not_waiting':
				genericAjaxPost(formName, '', 'c=tickets&a=viewNotWaitingTickets&view_id='+view_id, function(html) {
					$('#'+divName).html(html);
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
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
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
					hideLoadingPanel();
				});
				break;
			case 2: // delete
				genericAjaxPost(formName, '', 'c=tickets&a=viewDeleteTickets&view_id=' + view_id, function(html) {
					$('#'+divName).html(html);
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
					hideLoadingPanel();
				});
				break;
			default: // close
				genericAjaxPost(formName, '', 'c=tickets&a=viewCloseTickets&view_id=' + view_id, function(html) {
					$('#'+divName).html(html);
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
					hideLoadingPanel();
				});
				break;
		}
	}
	
	this.postAndReloadView = function(frm,view_id) {
		
		$('#'+view_id).fadeTo("slow", 0.2);
		
		genericAjaxPost(frm,view_id,'',
			function(html) {
				$('#'+view_id).html(html);
				genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');					
	
				$('#'+view_id).fadeTo("slow", 1.0);
	
				if(null != genericPanel) {
					try {
						genericPanel.dialog('close');
						genericPanel = null;
					} catch(e) {}
				}
			}
		);
	}
	
	this.viewUndo = function(view_id) {
		genericAjaxGet('','c=tickets&a=viewUndo&view_id=' + view_id,
			function(html) {
				$('#view'+view_id).html(html);
				genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
			}
		);		
	}

	this.emailAutoComplete = function(sel, options) {
		if(null == options) options = { };
		
		url = DevblocksAppPath+'ajax.php?c=contacts&a=getEmailAutoCompletions';
		$(sel).autocomplete(url, options);
	}

	this.orgAutoComplete = function(sel, options) {
		if(null == options) options = { };
		
		url = DevblocksAppPath+'ajax.php?c=contacts&a=getOrgsAutoCompletions';
		$(sel).autocomplete(url, options);
	}

	this.countryAutoComplete = function(sel, options) {
		if(null == options) options = { };
		
		url = DevblocksAppPath+'ajax.php?c=contacts&a=getCountryAutoCompletions';
		$(sel).autocomplete(url, options);
	}
}

var ajax = new cAjaxCalls();

var cDisplayTicketAjax = function(ticket_id) {
	this.ticket_id = ticket_id;

	this.saveRequesterPanel = function(div,label) {
		genericAjaxPost(div,'','',
			function(html) {
				if(null != genericPanel) {
					try {
						genericPanel.dialog('close');
						genericPanel = null;
					} catch(e) {}
				}
				
				$('#'+label).html(html);
			}
		);
	}

	this.reply = function(msgid,is_forward) {
		var div = document.getElementById('reply' + msgid);
		if(null == div) return;
		is_forward = (null == is_forward || 0 == is_forward) ? 0 : 1;
		
		genericAjaxGet('', 'c=display&a=reply&forward='+is_forward+'&id=' + msgid,
			function(html) {
				var div = document.getElementById('reply' + msgid);
				if(null == div) return;
				
				$('#reply'+msgid).html(html);
				
				document.location = '#reply' + msgid;
	
				var frm_reply = document.getElementById('reply' + msgid + '_part2');
				
				if(null != frm_reply.content) {
					if(!is_forward) {
						frm_reply.content.focus();
						setElementSelRange(frm_reply.content, 0, 0);
					} else {
						frm_reply.to.focus();
					}
				}
			}
		);
	}
	
	this.addNote = function(msgid) {
		var div = document.getElementById('reply' + msgid);
		if(null == div) return;
		
		genericAjaxGet('','c=display&a=addNote&id=' + msgid,
			function(html) {
				var div = document.getElementById('reply' + msgid);
				if(null == div) return;
				
				$('#reply'+msgid).html(html);
				document.location = '#reply' + msgid;
				
				var frm = document.getElementById('reply' + msgid + '_form');
				if(null != frm && null != frm.content) {
					frm.content.focus();
					setElementSelRange(frm.content, 0, 0);
				}
			}
		);
	}
}