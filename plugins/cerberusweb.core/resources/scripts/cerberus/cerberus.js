<!--
// [JAS]: [TODO] This should move into the plugin

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

var searchDialogs = new Array();

function addCriteria(divName) {
	if(null == searchDialogs[''+divName]) {
		ajax.getSearchCriteriaDialog(divName);
	} else {
		try {
			document.getElementById(divName + '_field').selectedIndex = 0;
			document.getElementById(divName + '_render').innerHTML = '';
		} catch(e) {}
		searchDialogs[''+divName].show();
		return;
	}
}

var cAjaxCalls = function() {

/*
	this.addTagAutoComplete = function(txt,con) {
		// [JAS]: [TODO] Move to a tag autocompletion shared method
		myXHRDataSource = new YAHOO.widget.DS_XHR(DevblocksAppPath+"ajax.php", ["\n", "\t"]);
		myXHRDataSource.scriptQueryParam = "q"; 
		myXHRDataSource.scriptQueryAppend = "c=core.display.module.workflow&a=autoTag"; 
		myXHRDataSource.responseType = myXHRDataSource.TYPE_FLAT;
		myXHRDataSource.maxCacheEntries = 60;
		myXHRDataSource.queryMatchSubset = true;
		myXHRDataSource.connTimeout = 3000;

		var myAutoComp = new YAHOO.widget.AutoComplete(txt, con, myXHRDataSource); 
		myAutoComp.delimChar = ",";
		myAutoComp.queryDelay = 1;
		myAutoComp.useIFrame = true; 
		myAutoComp.typeAhead = false;
//					myAutoComp.prehighlightClassName = "yui-ac-prehighlight"; 
		myAutoComp.allowBrowserAutocomplete = false;
		myAutoComp.formatResult = function(oResultItem, sQuery) {
       var sKey = oResultItem[0];
       var aMarkup = [sKey];
       return (aMarkup.join(""));
		}
	}
	
	this.addWorkerAutoComplete = function(txt,con) {
		// [JAS]: [TODO] Move to a tag autocompletion shared method
		myXHRDataSource = new YAHOO.widget.DS_XHR(DevblocksAppPath+"ajax.php", ["\n", "\t"]);
		myXHRDataSource.scriptQueryParam = "q"; 
		myXHRDataSource.scriptQueryAppend = "c=core.display.module.workflow&a=autoWorker"; 
		myXHRDataSource.responseType = myXHRDataSource.TYPE_FLAT;
		myXHRDataSource.maxCacheEntries = 60;
		myXHRDataSource.queryMatchSubset = true;
		myXHRDataSource.connTimeout = 3000;

		var myAutoComp = new YAHOO.widget.AutoComplete(txt, con, myXHRDataSource); 
		myAutoComp.delimChar = ",";
		myAutoComp.queryDelay = 1;
		myAutoComp.useIFrame = true; 
		myAutoComp.typeAhead = false;
//					myAutoComp.prehighlightClassName = "yui-ac-prehighlight"; 
		myAutoComp.allowBrowserAutocomplete = false;
		myAutoComp.formatResult = function(oResultItem, sQuery) {
       var sKey = oResultItem[0];
       var aMarkup = [sKey];
       return (aMarkup.join(""));
		}
	}
*/

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
		
//		for(var x=len-1;x>=0;x--) {
//			if(elements[x].checked) {
//				frm.appendChild(elements[x]);
//			}
//		}

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
	
	this.getSearchCriteriaDialog = function(divName) {
		var div = document.getElementById(divName);
		if(null == div) return;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=tickets&a=getCriteriaDialog&divName=' + divName, {
				success: function(o) {
					var divName = o.argument.divName;
					var div = document.getElementById(divName);
					if(null == div) return;
					
					div.innerHTML = o.responseText;
					
					searchDialogs[''+divName] = new YAHOO.widget.Panel(divName, { 
						width:"500px",  
						fixedcenter: true,  
						constraintoviewport: true,  
						underlay:"none",  
						close:false,  
						visible:true, 
						modal:true,
						draggable:false} ); 		
						
					searchDialogs[''+divName].render();
					
//					div.style.display = 'block';
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
	
	this.viewAssignTicket = function(view_id, ticket_id, owner_id) {
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=tickets&a=takeTicket&ticket_id='+ticket_id+'&owner_id='+owner_id, {
				success: function(o) {
					var id = o.argument.id;
					var caller = o.argument.caller;
					caller.getRefresh(id);
				},
				failure: function(o) {},
				argument:{caller:this,id:view_id}
		});	
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

/*	
	this.viewRunAction = function(id) {
		YAHOO.util.Connect.setForm('viewForm'+id);
		var cObj = YAHOO.util.Connect.asyncRequest('POST', DevblocksAppPath+'ajax.php', {
				success: function(o) {
					var id = o.argument.id;
					var caller = o.argument.caller;
					caller.getRefresh(id);
				},
				failure: function(o) {},
				argument:{caller:this,id:id}
		});	
	}
*/
	
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

/*
	this.refreshRequesters = function(id) {
		var div = document.getElementById('displayTicketRequesters');
		if(null == div) return;
	
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=display&a=refreshRequesters&id=' + id, {
				success: function(o) {
					var id = o.argument.id;
					var div = document.getElementById('displayTicketRequesters');
					if(null == div) return;
					
					div.innerHTML = o.responseText;
					
//					ajax.addAddressAutoComplete("addRequesterEntry","addRequesterContainer", true);
				},
				failure: function(o) {},
				argument:{caller:this,id:id}
			}
		);	
	}

	this.saveRequester = function(id) {
		var div = document.getElementById('displayTicketRequesters');
		if(null == div) return;

		YAHOO.util.Connect.setForm('displayTicketRequesters');
		var cObj = YAHOO.util.Connect.asyncRequest('POST', DevblocksAppPath+'ajax.php', {
				success: function(o) {
					var id = o.argument.id;
					var caller = o.argument.caller;
					caller.refreshRequesters(id);
				},
				failure: function(o) {},
				argument:{caller:this,id:id}
			}
		);	
	}
*/

	this.getSearchCriteria = function(divName,field) {
		var div = document.getElementById(divName + '_render');
		if(null == div) return;

//		var anim = new YAHOO.util.Anim(div, { opacity: { to: 0.2 } }, 1, YAHOO.util.Easing.easeOut);
//		anim.animate();
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=tickets&a=getCriteria&field=' + field, {
				success: function(o) {
//					var id = o.argument.id;
					var div = document.getElementById(divName + '_render');
					if(null == div) return;
					
					div.innerHTML = o.responseText;
//					div.style.display = 'block';
					
//					var anim = new YAHOO.util.Anim(div, { opacity: { to: 1 } }, 1, YAHOO.util.Easing.easeOut);
//					anim.animate();
				},
				failure: function(o) {},
				argument:{caller:this}
		});	
	}

}

var ajax = new cAjaxCalls();
-->