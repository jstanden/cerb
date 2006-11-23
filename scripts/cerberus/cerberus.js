<!--
// [JAS]: [TODO] This should move into the plugin

var cAjaxCalls = function() {
	this.getCustomize = function(id) {
		var div = document.getElementById('customize' + id);
		if(null == div) return;
	
		if(0 != div.innerHTML.length) {
			div.innerHTML = '';
			div.style.display = 'inline';
		} else {
			var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.dashboard&a=customize&id=' + id, {
					success: function(o) {
						var id = o.argument.id;
						var div = document.getElementById('customize' + id);
						if(null == div) return;
						
						div.innerHTML = o.responseText;
						div.style.display = 'block';
					},
					failure: function(o) {},
					argument:{caller:this,id:id}
				}
			);	
		}
	}
	
	this.getSortBy = function(id,sortBy) {
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.dashboard&a=viewSortBy&id=' + id + '&sortBy=' + sortBy, {
				success: function(o) {
					var id = o.argument.id;
					var caller = o.argument.caller;
					caller.getRefresh(id);
				},
				failure: function(o) {},
				argument:{caller:this,id:id}
		});	
	}
	
	this.getRefresh = function(id) {
		var div = document.getElementById('view' + id);
		if(null == div) return;

		var anim = new YAHOO.util.Anim(div, { opacity: { to: 0.2 } }, 1, YAHOO.util.Easing.easeOut);
		anim.animate();
		
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.dashboard&a=viewRefresh&id=' + id, {
				success: function(o) {
					var id = o.argument.id;
					var div = document.getElementById('view' + id);
					if(null == div) return;
					
					div.innerHTML = o.responseText;
					div.style.display = 'block';
					
					var anim = new YAHOO.util.Anim(div, { opacity: { to: 1 } }, 1, YAHOO.util.Easing.easeOut);
					anim.animate();
				},
				failure: function(o) {},
				argument:{caller:this,id:id}
		});	
	}
}

var ajax = new cAjaxCalls();
-->