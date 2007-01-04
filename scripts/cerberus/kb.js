var cKbAjax = function() {
	
	this.categoryPanel = null;
	this.showMailboxRouting = function(target) {
		
		if(null != this.categoryPanel) {
			this.categoryPanel.hide();
		}
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.kb&a=getKbCategoryDialog', {
				success: function(o) {
					var caller = o.argument.caller;
					var target = o.argument.target;
					
					if(null == caller.categoryPanel) {
						caller.categoryPanel = new YAHOO.widget.Panel("kbCategoryDialog", 
							{ width : "510px",
							  fixedcenter : false,
							  visible : false, 
							  constraintoviewport : true,
							  underlay:"none",
							  modal: true,
							  close: false,
							  draggable: false
							});

						caller.categoryPanel.setBody('');
						caller.categoryPanel.render(document.body);
					}
					
					caller.categoryPanel.hide();
					caller.categoryPanel.setBody(o.responseText);
					caller.categoryPanel.cfg.setProperty('context',[target,"tl","bl"]);
					caller.categoryPanel.show();
				},
				failure: function(o) {},
				argument:{caller:this,target:target}
			}
		);	
	}
	
	this.postShowMailboxRouting = function(addressId) {
		var frm = document.getElementById('routingDialog');
		if(null == frm) return;
		
		YAHOO.util.Connect.setForm(frm);
		var cObj = YAHOO.util.Connect.asyncRequest('POST', 'ajax.php', {
				success: function(o) {
					var caller = o.argument.caller;
					var addressId = o.argument.id;

					caller.mailboxRoutingDialog.hide();
					
					if(addressId != '0') { // update
						var div = document.getElementById('mbox_routing_' + addressId);
						if(null == div) return;
						div.innerHTML = o.responseText;
						
					} else { // create
						caller.refreshMailboxRouting();
					}
					
//					caller.refreshWorkflow();
				},
				failure: function(o) {},
				argument:{caller:this,id:addressId}
				}
		);
	}	
	
}