var cDisplayTicketAjax = function(ticket_id) {
	this.ticket_id = ticket_id;

	this.reloadTicketTasks = function(o) {
		genericAjaxGet('core.display.module.tasks_body','c=display&a=reloadTasks&ticket_id=' + displayAjax.ticket_id);
	}
	
	this.reply = function(msgid) {
		var div = document.getElementById('reply' + msgid);
		if(null == div) return;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=display&a=reply&id=' + msgid, {
				success: function(o) {
					var caller = o.argument.caller;
					var id = o.argument.msgid;
					
					var div = document.getElementById('reply' + id);
					if(null == div) return;
					
					div.innerHTML = o.responseText;
					document.location = '#reply' + id;
//					div.style.display = 'block';
				},
				failure: function(o) {},
				argument:{caller:this,msgid:msgid}
		});	
	}
	
	this.forward = function(msgid) {
		var div = document.getElementById('reply' + msgid);
		if(null == div) return;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=display&a=forward&id=' + msgid, {
				success: function(o) {
					var caller = o.argument.caller;
					var id = o.argument.msgid;
					
					var div = document.getElementById('reply' + id);
					if(null == div) return;
					
					div.innerHTML = o.responseText;
					document.location = '#reply' + id;
//					div.style.display = 'block';
				},
				failure: function(o) {},
				argument:{caller:this,msgid:msgid}
		});	
	}
	
	this.comment = function(msgid) {
		var div = document.getElementById('reply' + msgid);
		if(null == div) return;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=display&a=comment&id=' + msgid, {
				success: function(o) {
					var caller = o.argument.caller;
					var id = o.argument.msgid;
					
					var div = document.getElementById('reply' + id);
					if(null == div) return;
					
					div.innerHTML = o.responseText;
					document.location = '#reply' + id;
//					div.style.display = 'block';
				},
				failure: function(o) {},
				argument:{caller:this,msgid:msgid}
		});	
	}
	
}