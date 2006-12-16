var cDisplayTicketAjax = function(ticket_id, workflow_div) {
	this.ticket_id = ticket_id;
	this.workflow_div = workflow_div;
	this.tagDialog = null;
	
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
	
	this.applyTagsToTicket = function() {
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
	
	// [JAS]: [TODO] Encapsulate better (new JS ajax class) so we don't need to pass ticketId + formName all over the place.
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
	
}