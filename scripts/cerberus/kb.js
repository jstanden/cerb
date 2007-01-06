var cKbAjax = function() {
	
	this.categoryPanel = null;
	this.showCategoryJump = function(target) {
		
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
	
	this.categoryModifyPanel = null;
	this.showCategoryModify = function(id,parent,target) {
		
		if(null != this.categoryModifyPanel) {
			this.categoryModifyPanel.hide();
		}
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.kb&a=getKbCategoryModifyDialog&id=' + id + '&pid=' + parent, {
				success: function(o) {
					var caller = o.argument.caller;
					var target = o.argument.target;
					
					if(null == caller.categoryModifyPanel) {
						caller.categoryModifyPanel = new YAHOO.widget.Panel("kbCategoryModifyDialog", 
							{ width : "510px",
							  fixedcenter : false,
							  visible : false, 
							  constraintoviewport : true,
							  underlay:"none",
							  modal: true,
							  close: false,
							  draggable: false
							});

						caller.categoryModifyPanel.setBody('');
						caller.categoryModifyPanel.render(document.body);
					}
					
					caller.categoryModifyPanel.hide();
					caller.categoryModifyPanel.setBody(o.responseText);
					caller.categoryModifyPanel.cfg.setProperty('context',[target,"tl","bl"]);
					caller.categoryModifyPanel.show();
				},
				failure: function(o) {},
				argument:{caller:this,target:target}
			}
		);	
	}
	
}