<!--
// [JAS]: [TODO] This should move into the plugin

function clearDiv(divName) {
	var div = document.getElementById(divName);
	if(null == div) return;
	
	div.innerHTML = '';
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
	} else if (null != state && (state == 'block' || state == 'none')) {
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
			boxes[x].checked = (state) ? true : false;
		}
	}
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

// [JAS]: [TODO] Make this a little more generic?
function appendTextboxAsCsv(formName, oLink) {
	var frm = document.getElementById(formName);
	if(null == frm) return;
	
	var txt = frm.tagEntry;
	var sAppend = '';
	
	// [TODO]: check that the last character(s) aren't comma or comma space
	if(0 != txt.value.length)
		sAppend += ', ';
		
	sAppend += oLink.innerHTML;
	
	txt.value = txt.value + sAppend;
}

var cAjaxCalls = function() {

	this.getLoadSearch = function(divName) {
		var div = document.getElementById(divName + '_control');
		if(null == div) return;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.search&a=getLoadSearch&divName='+divName, {
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
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.search&a=getSaveSearch&divName='+divName, {
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
			document.location = 'index.php?c=core.module.search&a=deleteSearch&search_id=' + id;
		}
	}
	
	this.saveSearch = function(divName) {
		var div = document.getElementById(divName + '_control');
		if(null == div) return;
		
		YAHOO.util.Connect.setForm(divName + '_control');
		var cObj = YAHOO.util.Connect.asyncRequest('POST', 'ajax.php', {
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
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.search&a=getCriteriaDialog&divName=' + divName, {
				success: function(o) {
					var divName = o.argument.divName;
					var div = document.getElementById(divName);
					if(null == div) return;
					
					div.innerHTML = o.responseText;
					
					searchDialogs[''+divName] = new YAHOO.widget.Panel(divName, { 
						width:"500px",  
						fixedcenter: true,  
						constraintoviewport: true,  
						underlay:"shadow",  
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
			var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.dashboard&a=customize&id=' + id, {
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
		var cObj = YAHOO.util.Connect.asyncRequest('POST', 'ajax.php', {
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
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.display&a=reply&id=' + id, {
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
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.display&a=forward&id=' + id, {
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
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.display&a=comment&id=' + id, {
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
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.dashboard&a=viewSortBy&id=' + id + '&sortBy=' + sortBy, {
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
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.dashboard&a=viewPage&id=' + id + '&page=' + page, {
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
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.dashboard&a=viewRefresh&id=' + id, {
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
	
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.display&a=refreshRequesters&id=' + id, {
				success: function(o) {
					var id = o.argument.id;
					var div = document.getElementById('displayTicketRequesters');
					if(null == div) return;
					
					div.innerHTML = o.responseText;
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
		var cObj = YAHOO.util.Connect.asyncRequest('POST', 'ajax.php', {
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
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.search&a=getCriteria&field=' + field, {
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