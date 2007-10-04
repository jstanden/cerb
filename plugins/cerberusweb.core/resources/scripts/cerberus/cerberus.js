<!--
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

function getKeyboardKey(evt) {
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
	  	if((evt.modifiers & event.ALT_MASK) || (evt.modifiers & evt.CTRL_MASK)) {
			return;
		}
   		mykey = evt.keyCode
	  }
	  
	  mykey = String.fromCharCode(mykey);
	  
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
	
	return mykey;
}

// ***********

function appendFileInput(divName,fieldName) {
	var frm = document.getElementById(divName);
	if(null == frm) return;

	var fileInput = document.createElement('input');
	fileInput.setAttribute('type','file');
	fileInput.setAttribute('name',fieldName);
	fileInput.setAttribute('size','45');
	
	frm.appendChild(fileInput);
	
	frm.innerHTML += "<BR>";
}

var cAjaxCalls = function() {
	this.showBatchPanel = function(view_id,team_id,target) {
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
		
		if(null == team_id)
			team_id = 0;
	
		var ticket_ids = ids.join(','); // [TODO] Encode?
	
		genericAjaxPanel('c=tickets&a=showBatchPanel&view_id=' + view_id + '&ids=' + ticket_ids + '&team_id=' + team_id,target,false,'500px');
	}

	this.saveBatchPanel = function(view_id) {
		var divName = 'view'+view_id;
		var formName = 'viewForm'+view_id;
		var viewDiv = document.getElementById(divName);
		var viewForm = document.getElementById(formName);
		if(null == viewForm || null == viewDiv) return;

		var frm = document.getElementById('formBatchUpdate');
		// [JAS]: Compile a list of checked ticket IDs
	//	var viewForm = document.getElementById('viewForm'+view_id);
	//	if(null == viewForm) return;

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
		
		frm.ticket_ids.value = ids.join(',');		

		genericAjaxPost('formBatchUpdate', '', 'c=tickets&a=doBatchUpdate', function(o) {
			viewDiv.innerHTML = o.responseText;

			if(null != genericPanel) {
				genericPanel.hide();
			}
			
			document.location = '#top';
			genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
		});
	}

	this.viewMoveTickets = function(view_id) {
		var divName = 'view'+view_id;
		var formName = 'viewForm'+view_id;
		var viewDiv = document.getElementById(divName);
		var viewForm = document.getElementById(formName);
		if(null == viewForm || null == viewDiv) return;

		genericAjaxPost(formName, '', 'c=tickets&a=viewMoveTickets&view_id='+view_id, function(o) {
			viewDiv.innerHTML = o.responseText;
			genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
		});
	}

	this.viewTicketsAction = function(view_id,action) {
		var divName = 'view'+view_id;
		var formName = 'viewForm'+view_id;
		var viewDiv = document.getElementById(divName);
		var viewForm = document.getElementById(formName);
		if(null == viewForm || null == viewDiv) return;

		switch(action) {
			case 'merge':
				genericAjaxPost(formName, '', 'c=tickets&a=viewMergeTickets&view_id='+view_id, function(o) {
					viewDiv.innerHTML = o.responseText;
					genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
				});
				break;
			case 'not_spam':
				genericAjaxPost(formName, '', 'c=tickets&a=viewNotSpamTickets&view_id='+view_id, function(o) {
					viewDiv.innerHTML = o.responseText;
					genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
				});
				break;
		}
	}
	
	this.viewCloseTickets = function(view_id,mode) {
		var divName = 'view'+view_id;
		var formName = 'viewForm'+view_id;
		var viewDiv = document.getElementById(divName);
		var viewForm = document.getElementById(formName);
		if(null == viewForm || null == viewDiv) return;

		switch(mode) {
			case 1: // spam
				genericAjaxPost(formName, '', 'c=tickets&a=viewSpamTickets&view_id=' + view_id, function(o) {
					viewDiv.innerHTML = o.responseText;
					genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
				});
				break;
			case 2: // delete
				genericAjaxPost(formName, '', 'c=tickets&a=viewDeleteTickets&view_id=' + view_id, function(o) {
					viewDiv.innerHTML = o.responseText;
					genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
				});
				break;
			case 3: // release/surrender
				genericAjaxPost(formName, '', 'c=tickets&a=viewSurrenderTickets&view_id=' + view_id, function(o) {
					viewDiv.innerHTML = o.responseText;
					genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
				});
				break;
			default: // close
				genericAjaxPost(formName, '', 'c=tickets&a=viewCloseTickets&view_id=' + view_id, function(o) {
					viewDiv.innerHTML = o.responseText;
					genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
				});
				break;
		}
	}
	
	this.viewUndo = function(view_id) {
		var viewDiv = document.getElementById('view'+view_id);
		if(null == viewDiv) return;
	
		genericAjaxGet('','c=tickets&a=viewUndo&view_id=' + view_id,
			function(o) {
				viewDiv.innerHTML = o.responseText;
				genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
			}
		);		
	}

	this.cbAddressPeek = function(o) {
		var myDataSource = new YAHOO.widget.DS_XHR(DevblocksAppPath+"ajax.php", ["\n", "\t"] );
		myDataSource.scriptQueryAppend = "c=contacts&a=getOrgsAutoCompletions"; 
	
		myDataSource.responseType = YAHOO.widget.DS_XHR.TYPE_FLAT;
		myDataSource.maxCacheEntries = 60;
		myDataSource.queryMatchSubset = true;
		myDataSource.connTimeout = 3000;
	
	 			var myInput = document.getElementById('contactinput'); 
	    var myContainer = document.getElementById('contactcontainer'); 
	
		var myAutoComp = new YAHOO.widget.AutoComplete(myInput,myContainer, myDataSource);
		// myAutoComp.delimChar = ",";
		myAutoComp.queryDelay = 1;
		//myAutoComp.useIFrame = true; 
		myAutoComp.typeAhead = false;
		myAutoComp.useShadow = true;
		//myAutoComp.prehighlightClassName = "yui-ac-prehighlight"; 
		myAutoComp.allowBrowserAutocomplete = false;
	
		var contactOrgAutoCompSelected = function contactOrgAutoCompSelected(sType, args, me) {
					org_str = new String(args[2]);
					org_arr = org_str.split(',');
					document.formAddressPeek.contact_orgid.value=org_arr[1];
				};
		
		obj=new Object();
		myAutoComp.itemSelectEvent.subscribe(contactOrgAutoCompSelected, obj);
	}

}

var ajax = new cAjaxCalls();
-->