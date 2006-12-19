var cDisplayTicketAjax = function(ticket_id, workflow_div) {
	this.ticket_id = ticket_id;
	this.workflow_div = workflow_div;
	this.tagDialog = null;
	this.agentDialog = null;
	
	this.refreshWorkflow = function() {
		var div = document.getElementById(this.workflow_div);
		if(null == div) return;

//		var anim = new YAHOO.util.Anim(div, { opacity: { to: 0.2 } }, 1, YAHOO.util.Easing.easeOut);
//		anim.animate();
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.display.module.workflow&a=refresh&id=' + this.ticket_id, {
				success: function(o) {
					var caller = o.argument.caller;
					var div = document.getElementById(caller.workflow_div);
					if(null == div) return;
					
					div.innerHTML = o.responseText;
					
//					var anim = new YAHOO.util.Anim(div, { opacity: { to: 1 } }, 1, YAHOO.util.Easing.easeOut);
//					anim.animate();
					
				},
				failure: function(o) {},
				argument:{caller:this}
				}
		);
	}
	
	this.showTag = function(tagId,target) {
		
		if(null != this.tagDialog) {
			this.tagDialog.hide();
		}
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.display.module.workflow&a=getTagDialog&id=' + tagId + '&ticket_id=' + this.ticket_id, {
				success: function(o) {
					var caller = o.argument.caller;
					var target = o.argument.target;
					
					if(null == caller.tagDialog) {
						caller.tagDialog = new YAHOO.widget.Panel("tagDialog", 
							{ width : "400px",
							  fixedcenter : false,
							  visible : false, 
							  constraintoviewport : true,
							  modal: false,
							  close: false,
							  draggable: false
							});

						caller.tagDialog.setBody('');
						caller.tagDialog.render(document.body);
					}
					
					caller.tagDialog.hide();
					caller.tagDialog.setBody(o.responseText);
					caller.tagDialog.cfg.setProperty('context',[target,"tl","bl"]);
					caller.tagDialog.show();
				},
				failure: function(o) {},
				argument:{caller:this,target:target}
			}
		);	
	}
	
	this.postShowTag = function() {
		var frm = document.getElementById('tagPanel');
		if(null == frm) return;
		
		YAHOO.util.Connect.setForm(frm);
		var cObj = YAHOO.util.Connect.asyncRequest('POST', 'ajax.php', {
				success: function(o) {
					var caller = o.argument.caller;

					caller.tagDialog.hide();
					caller.refreshWorkflow();
				},
				failure: function(o) {},
				argument:{caller:this}
				}
		);
	}
	
	this.showAgent = function(agentId,target) {
		
		if(null != this.agentDialog) {
			this.agentDialog.hide();
		}
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.display.module.workflow&a=getAgentDialog&id=' + agentId + '&ticket_id=' + this.ticket_id, {
				success: function(o) {
					var caller = o.argument.caller;
					var target = o.argument.target;
					
					if(null == caller.agentDialog) {
						caller.agentDialog = new YAHOO.widget.Panel("agentDialog", 
							{ width : "400px",
							  fixedcenter : false,
							  visible : false, 
							  constraintoviewport : true,
							  modal: false,
							  close: false,
							  draggable: false
							});

						caller.agentDialog.setBody('');
						caller.agentDialog.render(document.body);
					}
					
					caller.agentDialog.hide();
					caller.agentDialog.setBody(o.responseText);
					caller.agentDialog.cfg.setProperty('context',[target,"tl","bl"]);
					caller.agentDialog.show();
				},
				failure: function(o) {},
				argument:{caller:this,target:target}
			}
		);	
	}
	
	this.postShowAgent = function() {
		var frm = document.getElementById('agentForm');
		if(null == frm) return;
		
		YAHOO.util.Connect.setForm(frm);
		var cObj = YAHOO.util.Connect.asyncRequest('POST', 'ajax.php', {
				success: function(o) {
					var caller = o.argument.caller;

					caller.agentDialog.hide();
					caller.refreshWorkflow();
				},
				failure: function(o) {},
				argument:{caller:this}
				}
		);
	}
	
	this.showApplyTags = function() {
		var div = document.getElementById('displayWorkflowOptions');
		if(null == div) return;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.display.module.workflow&a=showApplyTags&id=' + this.ticket_id, {
				success: function(o) {
					var div = document.getElementById('displayWorkflowOptions');
					if(null == div) return;
					
					div.innerHTML = o.responseText;
					toggleDiv('displayWorkflowOptions','block');
					
					var myArray1 = ["abc", "bcd", "cde"]; 
					var myDataSource1 = new YAHOO.widget.DS_JSArray(myArray1);

					myXHRDataSource = new YAHOO.widget.DS_XHR("ajax.php", ["\n", "\t"]);
					myXHRDataSource.scriptQueryParam = "q"; 
					myXHRDataSource.scriptQueryAppend = "c=core.display.module.workflow&a=autoTag"; 
					myXHRDataSource.responseType = myXHRDataSource.TYPE_FLAT;
					myXHRDataSource.maxCacheEntries = 60;
					myXHRDataSource.queryMatchSubset = true;
					myXHRDataSource.connTimeout = 3000;

					var myAutoComp = new YAHOO.widget.AutoComplete("tagEntry","myTagContainer", myXHRDataSource); 
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
            };
				},
				failure: function(o) {},
				argument:{caller:this}
				}
		);
	}
	
	this.submitWorkflow = function() {
		var div = document.getElementById(this.workflow_div);
		if(null == div) return;
		
		YAHOO.util.Connect.setForm(this.workflow_div);
		var cObj = YAHOO.util.Connect.asyncRequest('POST', 'ajax.php', {
				success: function(o) {
					var caller = o.argument.caller;
					caller.refreshWorkflow();
				},
				failure: function(o) {},
				argument:{caller:this}
				}
		);
	}
	
	this.showFavTags = function() {
		var div = document.getElementById('displayWorkflowOptions');
		if(null == div) return;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.display.module.workflow&a=showFavTags', {
				success: function(o) {
					var div = document.getElementById('displayWorkflowOptions');
					if(null == div) return;

					div.innerHTML = o.responseText;
					toggleDiv('displayWorkflowOptions','block');
				},
				failure: function(o) {},
				argument:{caller:this}
				}
		);
	}
	
	this.saveFavTags = function() {
		var div = document.getElementById(this.workflow_div);
		if(null == div) return;
		
		YAHOO.util.Connect.setForm(this.workflow_div);
		var cObj = YAHOO.util.Connect.asyncRequest('POST', 'ajax.php', {
				success: function(o) {
					var caller = o.argument.caller;
					caller.refreshWorkflow();
				},
				failure: function(o) {},
				argument:{caller:this}
				}
		);
	}
	
	this.showFavWorkers = function() {
		var div = document.getElementById('displayWorkflowOptions');
		if(null == div) return;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.display.module.workflow&a=showFavWorkers', {
				success: function(o) {
					var div = document.getElementById('displayWorkflowOptions');
					if(null == div) return;

					div.innerHTML = o.responseText;
					toggleDiv('displayWorkflowOptions','block');
				},
				failure: function(o) {},
				argument:{caller:this}
				}
		);
	}
	
	this.saveFavWorkers = function() {
		var div = document.getElementById(this.workflow_div);
		if(null == div) return;
		
		YAHOO.util.Connect.setForm(this.workflow_div);
		var cObj = YAHOO.util.Connect.asyncRequest('POST', 'ajax.php', {
				success: function(o) {
					var caller = o.argument.caller;
					caller.refreshWorkflow();
				},
				failure: function(o) {},
				argument:{caller:this}
				}
		);
	}
	
	this.showFlagAgents = function() {
		var div = document.getElementById('displayWorkflowOptions');
		if(null == div) return;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.display.module.workflow&a=showFlagAgents&id=' + this.ticket_id, {
				success: function(o) {
					var div = document.getElementById('displayWorkflowOptions');
					if(null == div) return;

					div.innerHTML = o.responseText;
					toggleDiv('displayWorkflowOptions','block');
				},
				failure: function(o) {},
				argument:{caller:this}
				}
		);
	}
	
	this.showSuggestAgents = function() {
		var div = document.getElementById('displayWorkflowOptions');
		if(null == div) return;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.display.module.workflow&a=showSuggestAgents&id=' + this.ticket_id, {
				success: function(o) {
					var div = document.getElementById('displayWorkflowOptions');
					if(null == div) return;

					div.innerHTML = o.responseText;
					toggleDiv('displayWorkflowOptions','block');
				},
				failure: function(o) {},
				argument:{caller:this}
				}
		);
	}
	
	
}