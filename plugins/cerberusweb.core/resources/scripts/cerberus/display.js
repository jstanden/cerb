var cDisplayTicketAjax = function(ticket_id) {
	this.ticket_id = ticket_id;

	this.showTemplatesPanel = function(target,msgid) {
		var div = document.getElementById('reply' + msgid);
		if(null == div) return;

		genericAjaxPanel('c=display&a=showTemplatesPanel&reply_id='+msgid,target,false,'550px',function(o) {
			var tabView = new YAHOO.widget.TabView();
			
			tabView.addTab( new YAHOO.widget.Tab({
			    label: 'List',
			    dataSrc: DevblocksAppPath+'ajax.php?c=display&a=showTemplateList&reply_id='+msgid,
			    cacheData: true,
			    active: true
			}));
			
			tabView.appendTo('templatePanelOptions');
			
			div.content.focus();
		});
	}

	this.insertReplyTemplate = function(template_id,msgid) {
		var div = document.getElementById('reply' + msgid);
		if(null == div) return;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=display&a=getTemplate&id=' + template_id + '&reply_id='+msgid, {
				success: function(o) {
					var caller = o.argument.caller;
					var id = o.argument.msgid;
					var template_id = o.argument.template_id;
					
					var div = document.getElementById('reply' + id);
					if(null == div) return;
					
					insertAtCursor(div.content, o.responseText);
					div.content.focus();
					
					try {
						genericPanel.hide();
					} catch(e) {}
				},
				failure: function(o) {},
				argument:{caller:this,msgid:msgid,template_id:template_id}
		});
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