var cConfigAjax = function() {
	
	this.getPop3Account = function(id) {
		var frm = document.getElementById('configPop3');
		if(null == frm) return;

		var anim = new YAHOO.util.Anim(frm, { opacity: { to: 0.2 } }, 1, YAHOO.util.Easing.easeOut);
		anim.animate();
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.configuration&a=getPop3Account&id=' + id, {
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
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.configuration&a=getMailbox&id=' + id, {
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
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.configuration&a=getWorker&id=' + id, {
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
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.configuration&a=getTeam&id=' + id, {
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
	
}