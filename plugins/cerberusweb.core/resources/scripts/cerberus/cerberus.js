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

/*
	this.addAddressAutoComplete = function(txt,con,single) {
		// [JAS]: [TODO] Move to a tag autocompletion shared method
		myXHRDataSource = new YAHOO.widget.DS_XHR(DevblocksAppPath+"ajax.php", ["\n", "\t"]);
		myXHRDataSource.scriptQueryParam = "q"; 
		myXHRDataSource.scriptQueryAppend = "c=core.display.module.workflow&a=autoAddress"; 
		myXHRDataSource.responseType = myXHRDataSource.TYPE_FLAT;
		myXHRDataSource.maxCacheEntries = 60;
		myXHRDataSource.queryMatchSubset = true;
		myXHRDataSource.connTimeout = 3000;

		var myAutoComp = new YAHOO.widget.AutoComplete(txt, con, myXHRDataSource); 
		if(null == single || false == single) myAutoComp.delimChar = ",";
		myAutoComp.queryDelay = 1;
		myAutoComp.useIFrame = true; 
		myAutoComp.typeAhead = false;
		myAutoComp.useShadow = true;
//					myAutoComp.prehighlightClassName = "yui-ac-prehighlight"; 
		myAutoComp.allowBrowserAutocomplete = false;
		myAutoComp.formatResult = function(oResultItem, sQuery) {
       var sKey = oResultItem[0];
       var aMarkup = [sKey];
       return (aMarkup.join(""));
		}
	}
*/

	this.showBatchPanel = function(view_id,team_id) {
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
	
		genericAjaxPanel('c=tickets&a=showBatchPanel&view_id=' + view_id + '&ids=' + ticket_ids + '&team_id=' + team_id,null,true,'500px');
	}

	this.saveBatchPanel = function(view_id) {
		var frm = document.getElementById('formBatchUpdate');
		
		// [JAS]: Compile a list of checked ticket IDs
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
		
		frm.ticket_ids.value = ids.join(',');		

		YAHOO.util.Connect.setForm('formBatchUpdate');
		
		var cObj = YAHOO.util.Connect.asyncRequest('POST', DevblocksAppPath+'ajax.php', {
				success: function(o) {
					var caller = o.argument.caller;
					
					if(null != genericPanel) {
						genericPanel.hide();
					}
					
					var view_id = o.argument.view_id;
					caller.getRefresh(view_id);
					
					document.location = '#top';
					
					genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
				},
				failure: function(o) {},
				argument:{caller:this,view_id:view_id}
		});	
	}

	this.previewTip = null;	
	this.scheduleTicketPreview = function(id, at) {
//		var func = function() {
			genericAjaxPanel('c=tickets&a=showPreview&id=' + id, at, false, '500px');
//		}
//		this.previewTip = setTimeout(func, 1);
	};
//	this.cancelTicketPreview = function() {
//		clearTimeout(this.previewTip);
//		if(null != genericPanel)
//			genericPanel.hide();
//	};

	this.saveViewActionPanel = function(id,view_id) {
		YAHOO.util.Connect.setForm('formViewActions');
		
		var cObj = YAHOO.util.Connect.asyncRequest('POST', DevblocksAppPath+'ajax.php', {
				success: function(o) {
					var caller = o.argument.caller;
					
					if(null != genericPanel) {
						genericPanel.hide();
					}
					
					var view_id = o.argument.view_id;
					caller.getRefresh(view_id);
				},
				failure: function(o) {},
				argument:{caller:this,view_id:view_id}
		});	
	}
	
	this.getLoadSearch = function(divName) {
		var div = document.getElementById(divName + '_control');
		if(null == div) return;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=tickets&a=getLoadSearch&divName='+divName, {
				success: function(o) {
					var divName = o.argument.divName;
					var div = document.getElementById(divName + '_control');
					if(null == div) return;
					
					div.innerHTML = o.responseText;
				},
				failure: function(o) {},
				argument:{caller:this,divName:divName}
				}
		);
	}

	this.getSaveSearch = function(divName) {
		var div = document.getElementById(divName + '_control');
		if(null == div) return;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=tickets&a=getSaveSearch&divName='+divName, {
				success: function(o) {
					var divName = o.argument.divName;
					var div = document.getElementById(divName + '_control');
					if(null == div) return;
					
					div.innerHTML = o.responseText;
				},
				failure: function(o) {},
				argument:{caller:this,divName:divName}
				}
		);
	}
	
	this.deleteSearch = function(id) {
		if(confirm('Are you sure you want to delete this search?')) {
			var url = new DevblocksUrl();
			url.addVar('search');
			url.addVar('deleteSearch');
			url.addVar('id');
		
			document.location = url.getUrl();
		}
	}
	
	this.saveSearch = function(divName) {
		var div = document.getElementById(divName + '_control');
		if(null == div) return;
		
		YAHOO.util.Connect.setForm(divName + '_control');
		var cObj = YAHOO.util.Connect.asyncRequest('POST', DevblocksAppPath+'ajax.php', {
				success: function(o) {
					var divName = o.argument.divName;
					var div = document.getElementById(divName + '_control');
					if(null == div) return;
					
					div.innerHTML = o.responseText;
				},
				failure: function(o) {},
				argument:{caller:this,divName:divName}
				}
		);
	}
	
	this.getCustomize = function(id) {
		var div = document.getElementById('customize' + id);
		if(null == div) return;
	
		if(0 != div.innerHTML.length) {
			div.innerHTML = '';
			div.style.display = 'inline';
		} else {
			var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=tickets&a=customize&id=' + id, {
					success: function(o) {
						var id = o.argument.id;
						var div = document.getElementById('customize' + id);
						if(null == div) return;
						
						div.innerHTML = o.responseText;
						div.style.display = 'block';
					},
					failure: function(o) {},
					argument:{caller:this,id:id}
				}
			);	
		}
	}
	
	this.saveCustomize = function(id) {
		var div = document.getElementById('customize' + id);
		if(null == div) return;

		YAHOO.util.Connect.setForm('customize' + id);
		var cObj = YAHOO.util.Connect.asyncRequest('POST', DevblocksAppPath+'ajax.php', {
				success: function(o) {
					var id = o.argument.id;
					var div = document.getElementById('customize' + id);
					if(null == div) return;
					
					div.innerHTML = '';
					div.style.display = 'inline';
					
					var caller = o.argument.caller;
					caller.getRefresh(id);
				},
				failure: function(o) {},
				argument:{caller:this,id:id}
			}
		);	
	}
	
	this.getSortBy = function(id,sortBy) {
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=tickets&a=viewSortBy&id=' + id + '&sortBy=' + sortBy, {
				success: function(o) {
					var id = o.argument.id;
					var caller = o.argument.caller;
					caller.getRefresh(id);
				},
				failure: function(o) {},
				argument:{caller:this,id:id}
		});	
	}
	
	this.getPage = function(id,page) {
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=tickets&a=viewPage&id=' + id + '&page=' + page, {
				success: function(o) {
					var id = o.argument.id;
					var caller = o.argument.caller;
					caller.getRefresh(id);
				},
				failure: function(o) {},
				argument:{caller:this,id:id}
		});	
	}
	
	this.viewMoveTickets = function(view_id) {
		var formName = 'viewForm'+view_id;
		var viewForm = document.getElementById(formName);
		if(null == viewForm) return;

		genericAjaxPost(formName, '', 'c=tickets&a=viewMoveTickets&view_id='+view_id, function(o) {
			ajax.getRefresh(view_id);
			genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
		});
	}

	this.viewTicketsAction = function(view_id,action) {
		var formName = 'viewForm'+view_id;
		var viewForm = document.getElementById(formName);
		if(null == viewForm) return;

		switch(action) {
			case 'merge':
				genericAjaxPost(formName, '', 'c=tickets&a=viewMergeTickets&view_id='+view_id, function(o) {
					ajax.getRefresh(view_id);
					genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
				});
				break;
			case 'not_spam':
				genericAjaxPost(formName, '', 'c=tickets&a=viewNotSpamTickets&view_id='+view_id, function(o) {
					ajax.getRefresh(view_id);
					genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
				});
				break;
		}
	}
	
	this.viewCloseTickets = function(view_id,mode) {
		var formName = 'viewForm'+view_id;
		var viewForm = document.getElementById(formName);
		if(null == viewForm) return;

		switch(mode) {
			case 1: // spam
				genericAjaxPost(formName, '', 'c=tickets&a=viewSpamTickets&view_id=' + view_id, function(o) {
					ajax.getRefresh(view_id);
					genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
				});
				break;
			case 2: // delete
				genericAjaxPost(formName, '', 'c=tickets&a=viewDeleteTickets&view_id=' + view_id, function(o) {
					ajax.getRefresh(view_id);
					genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
				});
				break;
			case 3: // release/surrender
				genericAjaxPost(formName, '', 'c=tickets&a=viewSurrenderTickets&view_id=' + view_id, function(o) {
					ajax.getRefresh(view_id);
					genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
				});
				break;
			default: // close
				genericAjaxPost(formName, '', 'c=tickets&a=viewCloseTickets&view_id=' + view_id, function(o) {
					ajax.getRefresh(view_id);
					genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
				});
				break;
		}
	}
	
	this.viewUndo = function(view_id) {
		genericAjaxGet('','c=tickets&a=viewUndo&view_id=' + view_id,
			function(o) {
				ajax.getRefresh(view_id);
				genericAjaxGet('dashboardPanel','c=tickets&a=refreshTeamFilters');
			}
		);		
	}

	this.getRefresh = function(id) {
		var div = document.getElementById('view' + id);
		if(null == div) return;

		var anim = new YAHOO.util.Anim(div, { opacity: { to: 0.2 } }, 1, YAHOO.util.Easing.easeOut);
		anim.animate();
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=tickets&a=viewRefresh&id=' + id, {
				success: function(o) {
					var id = o.argument.id;
					var div = document.getElementById('view' + id);
					if(null == div) return;
					
					if(1 == o.responseText.length) {
						div.innerHTML = '';
						div.style.display = 'inline';
						
					} else {
						div.innerHTML = o.responseText;
						div.style.display = 'block';
						
						var anim = new YAHOO.util.Anim(div, { opacity: { to: 1 } }, 1, YAHOO.util.Easing.easeOut);
						anim.animate();
					}
				},
				failure: function(o) {},
				argument:{caller:this,id:id}
		});	
	}
	
	
	this.contactOrgAjaxPanel = function contactOrgAjaxPanel(request,target,modal,width) {
		if(null != genericPanel) {
			genericPanel.hide();
		}
	
		var options = { 
		  width : "300px",
		  fixedcenter : false,
		  visible : false, 
		  constraintoviewport : true,
		  underlay : "shadow",
		  modal : false,
		  close : true,
		  draggable : true
		};
	
		if(null != width) options.width = width;
		if(null != modal) options.modal = modal;
		if(true == modal) options.fixedcenter = true;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?'+request, {
				success: function(o) {
					var caller = o.argument.caller;
					var target = o.argument.target;
					var options = o.argument.options;
						
					genericPanel = new YAHOO.widget.Panel("genericPanel", options);
					
					genericPanel.setBody('');
					genericPanel.render(document.body);
					
					genericPanel.hide();
					genericPanel.setBody(o.responseText);
					
					if(null != target && !options.fixedcenter) {
						genericPanel.cfg.setProperty('context',[target,"bl","tl"]);
					}
					
					genericPanel.show();

					var myDataSource = new YAHOO.widget.DS_XHR(DevblocksAppPath+"ajax.php", ["\n", "\t"] );
					myDataSource.scriptQueryAppend = "c=contacts&a=getOrgsAutoCompletions"; 

					myDataSource.responseType = YAHOO.widget.DS_XHR.TYPE_FLAT;
					myDataSource.maxCacheEntries = 60;
					myDataSource.queryMatchSubset = true;
					myDataSource.connTimeout = 3000;

	    			var myInput = document.getElementById('contact_org'); 
				    var myContainer = document.getElementById('org_autocomplete'); 

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
				
				},
				failure: function(o) {},
				argument:{request:request,target:target,options:options}
			}
		);	
	}	

}

var ajax = new cAjaxCalls();
-->