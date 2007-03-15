<!--
// [JAS]: [TODO] This should move into the plugin

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
	
	this.historyPanel = null;
	this.showHistoryPanel = function(target) {
		
		if(null != this.historyPanel) {
			this.historyPanel.hide();
		}
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=tickets&a=showHistoryPanel', {
				success: function(o) {
					var caller = o.argument.caller;
					var target = o.argument.target;
					
					if(null == caller.historyPanel) {
						caller.historyPanel = new YAHOO.widget.Panel("historyPanel", 
							{ width : "300px",
							  fixedcenter : false,
							  visible : false, 
							  constraintoviewport : true,
							  underlay:"none",
							  modal: false,
							  close: false,
							  draggable: false
							});

						caller.historyPanel.setBody('');
						caller.historyPanel.render(document.body);
					}
					
					caller.historyPanel.hide();
					caller.historyPanel.setBody(o.responseText);
					caller.historyPanel.cfg.setProperty('context',[target,"tr","br"]);
					caller.historyPanel.show();
				},
				failure: function(o) {},
				argument:{caller:this,target:target}
			}
		);	
	}
	
	this.mailboxPanel = null;
	this.showMailboxPanel = function(target) {
		
		if(null != this.mailboxPanel) {
			this.mailboxPanel.hide();
		}
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=tickets&a=showMailboxPanel', {
				success: function(o) {
					var caller = o.argument.caller;
					var target = o.argument.target;
					
					if(null == caller.mailboxPanel) {
						caller.mailboxPanel = new YAHOO.widget.Panel("mailboxPanel", 
							{ width : "300px",
							  fixedcenter : false,
							  visible : false, 
							  constraintoviewport : true,
							  underlay:"none",
							  modal: false,
							  close: false,
							  draggable: false
							});

						caller.mailboxPanel.setBody('');
						caller.mailboxPanel.render(document.body);
					}
					
					caller.mailboxPanel.hide();
					caller.mailboxPanel.setBody(o.responseText);
					caller.mailboxPanel.cfg.setProperty('context',[target,"tr","br"]);
					caller.mailboxPanel.show();
				},
				failure: function(o) {},
				argument:{caller:this,target:target}
			}
		);	
	}
	
	this.teamPanel = null;
	this.showTeamPanel = function(target) {
		
		if(null != this.teamPanel) {
			this.teamPanel.hide();
		}
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=tickets&a=showTeamPanel', {
				success: function(o) {
					var caller = o.argument.caller;
					var target = o.argument.target;
					
					if(null == caller.teamPanel) {
						caller.teamPanel = new YAHOO.widget.Panel("teamPanel", 
							{ width : "300px",
							  fixedcenter : false,
							  visible : false, 
							  constraintoviewport : true,
							  underlay:"none",
							  modal: false,
							  close: false,
							  draggable: false
							});

						caller.teamPanel.setBody('');
						caller.teamPanel.render(document.body);
					}
					
					caller.teamPanel.hide();
					caller.teamPanel.setBody(o.responseText);
					caller.teamPanel.cfg.setProperty('context',[target,"tr","br"]);
					caller.teamPanel.show();
				},
				failure: function(o) {},
				argument:{caller:this,target:target}
			}
		);	
	}
	
	this.assignPanel = null;
	this.showAssignPanel = function(target) {
		
		if(null != this.assignPanel) {
			this.assignPanel.hide();
		}
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=tickets&a=showAssignPanel', {
				success: function(o) {
					var caller = o.argument.caller;
					var target = o.argument.target;
					
					if(null == caller.assignPanel) {
						caller.assignPanel = new YAHOO.widget.Panel("assignPanel", 
							{ width : "300px",
							  fixedcenter : true,
							  visible : false, 
							  constraintoviewport : true,
							  underlay:"none",
							  modal: true,
							  close: false,
							  draggable: false
							});

						caller.assignPanel.setBody('');
						caller.assignPanel.render(document.body);
					}
					
					caller.assignPanel.hide();
					caller.assignPanel.setBody(o.responseText);
//					caller.assignPanel.cfg.setProperty('context',[target,"tr","br"]);
					caller.assignPanel.show();
				},
				failure: function(o) {},
				argument:{caller:this,target:target}
			}
		);	
	}
	
	this.contactPanel = null;
	this.showContactPanel = function(address,target) {
		
		if(null != this.contactPanel) {
			this.contactPanel.hide();
		}
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=tickets&a=showContactPanel&address=' + address, {
				success: function(o) {
					var caller = o.argument.caller;
					var target = o.argument.target;
					
					if(null == caller.contactPanel) {
						caller.contactPanel = new YAHOO.widget.Panel("contactPanel", 
							{ width : "350px",
							  fixedcenter : false,
							  zIndex: 9001,
							  visible : false, 
							  constraintoviewport : true,
							  underlay:"none",
							  modal: false,
							  close: false,
							  draggable: false
							});

						caller.contactPanel.setBody('');
						caller.contactPanel.render(document.body);
					}
					
					caller.contactPanel.hide();
					caller.contactPanel.setBody(o.responseText);
					caller.contactPanel.cfg.setProperty('context',[target,"tl","br"]);
					caller.contactPanel.show();
				},
				failure: function(o) {},
				argument:{caller:this,target:target}
			}
		);	
	}

	this.manageViewActionPanel = null;
	this.showViewActionPanel = function(id,view_id,target) {
		
		if(null != this.manageViewActionPanel) {
			this.manageViewActionPanel.hide();
		}
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=tickets&a=showViewActions&id=' + id + '&view_id=' + view_id, {
				success: function(o) {
					var caller = o.argument.caller;
					var target = o.argument.target;
					
					if(null == caller.manageViewActionPanel) {
						caller.manageViewActionPanel = new YAHOO.widget.Panel("manageViewActionPanel", 
							{ width : "450px",
							  fixedcenter : false,
							  zIndex: 9001,
							  visible : false, 
							  constraintoviewport : true,
							  underlay:"none",
							  modal: false,
							  close: false,
							  draggable: false
							});

						caller.manageViewActionPanel.setBody('');
						caller.manageViewActionPanel.render(document.body);
					}
					
					caller.manageViewActionPanel.hide();
					caller.manageViewActionPanel.setBody(o.responseText);
					caller.manageViewActionPanel.cfg.setProperty('context',[target,"tl","br"]);
					caller.manageViewActionPanel.show();
				},
				failure: function(o) {},
				argument:{caller:this,target:target}
			}
		);	
	}
	
	this.saveViewActionPanel = function(id,view_id) {
		YAHOO.util.Connect.setForm('formViewActions');
		
		var cObj = YAHOO.util.Connect.asyncRequest('POST', DevblocksAppPath+'ajax.php', {
				success: function(o) {
					var caller = o.argument.caller;
					
					if(null != caller.manageViewActionPanel) {
						caller.manageViewActionPanel.hide();
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
	
	// [JAS]: [TODO] This should use the generic clearDiv function (same with anything similar)
	this.discard = function(id) {
		var div = document.getElementById('reply' + id);
		if(null == div) return;
		div.innerHTML='';		
	}
	
	this.reply = function(id) {
		var div = document.getElementById('reply' + id);
		if(null == div) return;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=display&a=reply&id=' + id, {
				success: function(o) {
					var id = o.argument.id;
					var caller = o.argument.caller;
					
					var div = document.getElementById('reply' + id);
					if(null == div) return;
					
					div.innerHTML = o.responseText;
//					div.style.display = 'block';
				},
				failure: function(o) {},
				argument:{caller:this,id:id}
		});	
	}
	
	this.forward = function(id) {
		var div = document.getElementById('reply' + id);
		if(null == div) return;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=display&a=forward&id=' + id, {
				success: function(o) {
					var id = o.argument.id;
					var caller = o.argument.caller;
					
					var div = document.getElementById('reply' + id);
					if(null == div) return;
					
					div.innerHTML = o.responseText;
//					div.style.display = 'block';
				},
				failure: function(o) {},
				argument:{caller:this,id:id}
		});	
	}
	
	this.comment = function(id) {
		var div = document.getElementById('reply' + id);
		if(null == div) return;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=display&a=comment&id=' + id, {
				success: function(o) {
					var id = o.argument.id;
					var caller = o.argument.caller;
					
					var div = document.getElementById('reply' + id);
					if(null == div) return;
					
					div.innerHTML = o.responseText;
//					div.style.display = 'block';
				},
				failure: function(o) {},
				argument:{caller:this,id:id}
		});	
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

	this.refreshRequesters = function(id) {
		var div = document.getElementById('displayTicketRequesters');
		if(null == div) return;
	
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=display&a=refreshRequesters&id=' + id, {
				success: function(o) {
					var id = o.argument.id;
					var div = document.getElementById('displayTicketRequesters');
					if(null == div) return;
					
					div.innerHTML = o.responseText;
					
					ajax.addAddressAutoComplete("addRequesterEntry","addRequesterContainer", true);
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