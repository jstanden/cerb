var cDisplayTicketAjax = function(ticket_id) {
	this.ticket_id = ticket_id;

	this.saveRequesterPanel = function(div,label) {
		genericAjaxPost(div,'','',
			function(html) {
				if(null != genericPanel) {
					try {
						genericPanel.dialog('close');
						genericPanel = null;
					} catch(e) {}
				}
				
				$('#'+label).html(html);
			}
		);
	}

	this.reply = function(msgid,is_forward) {
		var div = document.getElementById('reply' + msgid);
		if(null == div) return;
		is_forward = (null == is_forward || 0 == is_forward) ? 0 : 1;
		
		genericAjaxGet('', 'c=display&a=reply&forward='+is_forward+'&id=' + msgid,
			function(html) {
				var div = document.getElementById('reply' + msgid);
				if(null == div) return;
				
				$('#reply'+msgid).html(html);
				
				document.location = '#reply' + msgid;
	
				var frm_reply = document.getElementById('reply' + msgid + '_part2');
				
				if(null != frm_reply.content) {
					if(!is_forward) {
						frm_reply.content.focus();
						setElementSelRange(frm_reply.content, 0, 0);
					} else {
						frm_reply.to.focus();
					}
				}
			}
		);
	}
	
	this.addNote = function(msgid) {
		var div = document.getElementById('reply' + msgid);
		if(null == div) return;
		
		genericAjaxGet('','c=display&a=addNote&id=' + msgid,
			function(html) {
				var div = document.getElementById('reply' + msgid);
				if(null == div) return;
				
				$('#reply'+msgid).html(html);
				document.location = '#reply' + msgid;
				
				var frm = document.getElementById('reply' + msgid + '_form');
				if(null != frm && null != frm.content) {
					frm.content.focus();
					setElementSelRange(frm.content, 0, 0);
				}
			}
		);
	}
}