var cDisplayTicketAjax = function(ticket_id) {
	this.ticket_id = ticket_id;

	this.saveRequesterPanel = function(div,label) {
		YAHOO.util.Connect.setForm(div);
		
		var cObj = YAHOO.util.Connect.asyncRequest('POST', DevblocksAppPath+'ajax.php', {
				success: function(o) {
					if(null != genericPanel) {
						try {
							genericPanel.destroy();
							genericPanel = null;
						} catch(e) {}
					}
					
					var eLabel = document.getElementById(label);
					if(null == eLabel) return;
					
					eLabel.innerHTML = o.responseText;
				},
				failure: function(o) {},
				argument:{div:div,label:label}
		});
	
		YAHOO.util.Connect.setForm(0);
	}

	this.reply = function(msgid,is_forward) {
		var div = document.getElementById('reply' + msgid);
		if(null == div) return;
		is_forward = (null == is_forward || 0 == is_forward) ? 0 : 1;
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=display&a=reply&forward='+is_forward+'&id=' + msgid, {
				success: function(o) {
					var caller = o.argument.caller;
					var id = o.argument.msgid;
					
					var div = document.getElementById('reply' + id);
					if(null == div) return;
					
					div.innerHTML = o.responseText;
					document.location = '#reply' + id;

					var frm_reply = document.getElementById('reply' + id + '_part2');
					
					if(null != frm_reply.content) {
						if(!is_forward) {
							frm_reply.content.focus();
							setElementSelRange(frm_reply.content, 0, 0);
						} else {
							frm_reply.to.focus();
						}
					}
					
					// Form validation
					if(null != document.getElementById('replyForm_to')) {
						var f = new LiveValidation('replyForm_to');
						f.add( Validate.Presence );
						//f.add( Validate.Email );
					}
					
					/*
					if(null != document.getElementById('replyForm_cc')) {
						var f = new LiveValidation('replyForm_cc');
						f.add( Validate.Email );
					}
					
					if(null != document.getElementById('replyForm_bcc')) {
						var f = new LiveValidation('replyForm_bcc');
						f.add( Validate.Email );
					}
					*/
					
					if(null != document.getElementById('replyForm_subject')) {
						var f = new LiveValidation('replyForm_subject');
						f.add( Validate.Presence );
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