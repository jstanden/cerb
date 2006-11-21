<!--
function getCustomize(id) {
	var div = document.getElementById('customize' + id);
	if(null == div) return;

	if(0 != div.innerHTML.length) {
		div.innerHTML = '';
	} else {
		var cObj = YAHOO.util.Connect.asyncRequest('GET', 'ajax.php?c=core.module.dashboard&a=customize&id=' + id, {
				success: function(o) {
					var id = o.argument.id;
					var div = document.getElementById('customize' + id);
					if(null == div) return;
					
					div.innerHTML = o.responseText;
				},
				failure: function(o) {},
				argument:{caller:this,id:id}
			}
		);	
	}
}

-->