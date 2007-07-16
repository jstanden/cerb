var cDisplayTicketAjax = function(ticket_id) {
	this.ticket_id = ticket_id;

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
					if(null != div.content) {
						div.content.focus();
						setElementSelRange(div.content, 0, 0);
					}
					
//					div.style.display = 'block';
				},
				failure: function(o) {},
				argument:{caller:this,msgid:msgid}
		});	
	}
	
	this.addNote = function(msgid) {
		var div = document.getElementById('reply' + msgid);
		if(null == div) return;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=display&a=addNote&id=' + msgid, {
				success: function(o) {
					var caller = o.argument.caller;
					var id = o.argument.msgid;
					
					var div = document.getElementById('reply' + id);
					if(null == div) return;
					
					div.innerHTML = o.responseText;
					document.location = '#reply' + id;
					if(null != div.content) {
						div.content.focus();
						setElementSelRange(div.content, 0, 0);
					}
					
//					div.style.display = 'block';
				},
				failure: function(o) {},
				argument:{caller:this,msgid:msgid}
		});	
	}
}