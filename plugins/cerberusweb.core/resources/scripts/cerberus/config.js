var cConfigAjax = function() {
	
	this.cbMailRoutingAdd = function(o) {
		var divName = o.argument.divName;
		var div = document.getElementById(divName);
		if(null == div) return;
		
		var newDiv = document.createElement('div');
		newDiv.innerHTML = o.responseText + "<BR>";
		div.appendChild(newDiv);
	}
	
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
	
}