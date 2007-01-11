var cConfigAjax = function() {
	
	this.getPop3Account = function(id) {
		var frm = document.getElementById('configPop3');
		if(null == frm) return;

		var anim = new YAHOO.util.Anim(frm, { opacity: { to: 0.2 } }, 1, YAHOO.util.Easing.easeOut);
		anim.animate();
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=config&a=getPop3Account&id=' + id, {
				success: function(o) {
					var frm = document.getElementById('configPop3');
					if(null == frm) return;
					frm.innerHTML = o.responseText;
					
					var anim = new YAHOO.util.Anim(frm, { opacity: { to: 1.0 } }, 1, YAHOO.util.Easing.easeOut);
					anim.animate();
				},
				failure: function(o) {},
				argument:{caller:this}
				}
		);
	}
	
	this.getMailbox = function(id) {
		var frm = document.getElementById('configMailbox');
		if(null == frm) return;

		var anim = new YAHOO.util.Anim(frm, { opacity: { to: 0.2 } }, 1, YAHOO.util.Easing.easeOut);
		anim.animate();
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=config&a=getMailbox&id=' + id, {
				success: function(o) {
					var frm = document.getElementById('configMailbox');
					if(null == frm) return;
					frm.innerHTML = o.responseText;

					var anim = new YAHOO.util.Anim(frm, { opacity: { to: 1.0 } }, 1, YAHOO.util.Easing.easeOut);
					anim.animate();
				},
				failure: function(o) {},
				argument:{caller:this}
				}
		);
	}
	
	this.getWorker = function(id) {
		var frm = document.getElementById('configWorker');
		if(null == frm) return;

		var anim = new YAHOO.util.Anim(frm, { opacity: { to: 0.2 } }, 1, YAHOO.util.Easing.easeOut);
		anim.animate();
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=config&a=getWorker&id=' + id, {
				success: function(o) {
					var frm = document.getElementById('configWorker');
					if(null == frm) return;
					frm.innerHTML = o.responseText;

					var anim = new YAHOO.util.Anim(frm, { opacity: { to: 1.0 } }, 1, YAHOO.util.Easing.easeOut);
					anim.animate();
				},
				failure: function(o) {},
				argument:{caller:this}
				}
		);
	}
	
	this.getTeam = function(id) {
		var frm = document.getElementById('configTeam');
		if(null == frm) return;

		var anim = new YAHOO.util.Anim(frm, { opacity: { to: 0.2 } }, 1, YAHOO.util.Easing.easeOut);
		anim.animate();
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=config&a=getTeam&id=' + id, {
				success: function(o) {
					var frm = document.getElementById('configTeam');
					if(null == frm) return;
					frm.innerHTML = o.responseText;

					var anim = new YAHOO.util.Anim(frm, { opacity: { to: 1.0 } }, 1, YAHOO.util.Easing.easeOut);
					anim.animate();
				},
				failure: function(o) {},
				argument:{caller:this}
				}
		);
	}
	
	this.refreshMailboxRouting = function() {
		var div = document.getElementById('configMailboxRouting');
		if(null == div) return;
		
		var anim = new YAHOO.util.Anim(div, { opacity: { to: 0.2 } }, 1, YAHOO.util.Easing.easeOut);
		anim.animate();
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=config&a=ajaxGetRouting', {
				success: function(o) {
					var caller = o.argument.caller;
					
					var div = document.getElementById('configMailboxRouting');
					if(null == div) return;
					
					div.innerHTML = o.responseText;
					
					var anim = new YAHOO.util.Anim(div, { opacity: { to: 1.0 } }, 1, YAHOO.util.Easing.easeOut);
					anim.animate();
					
				},
				failure: function(o) {},
				argument:{caller:this}
			}
		);	
	}
	
	this.deleteMailboxRouting = function(id) {
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=config&a=ajaxDeleteRouting&id=' + id, {
				success: function(o) {
					var caller = o.argument.caller;
					caller.mailboxRoutingDialog.hide();
					caller.refreshMailboxRouting();
				},
				failure: function(o) {},
				argument:{caller:this}
			}
		);	
	}
	
	this.mailboxRoutingDialog = null;
	this.showMailboxRouting = function(addressId,target) {
		
		if(null != this.mailboxRoutingDialog) {
			this.mailboxRoutingDialog.hide();
		}
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=config&a=getMailboxRoutingDialog&id=' + addressId, {
				success: function(o) {
					var caller = o.argument.caller;
					var target = o.argument.target;
					
					if(null == caller.mailboxRoutingDialog) {
						caller.mailboxRoutingDialog = new YAHOO.widget.Panel("mailboxRoutingDialog", 
							{ width : "400px",
							  fixedcenter : false,
							  visible : false, 
							  constraintoviewport : true,
							  underlay:"none",
							  modal: false,
							  close: false,
							  draggable: false
							});

						caller.mailboxRoutingDialog.setBody('');
						caller.mailboxRoutingDialog.render(document.body);
					}
					
					caller.mailboxRoutingDialog.hide();
					caller.mailboxRoutingDialog.setBody(o.responseText);
					caller.mailboxRoutingDialog.cfg.setProperty('context',[target,"tl","bl"]);
					caller.mailboxRoutingDialog.show();
					
					ajax.addAddressAutoComplete("routingEntry","routingEntryContainer", true);
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
		var cObj = YAHOO.util.Connect.asyncRequest('POST', DevblocksAppPath+'ajax.php', {
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