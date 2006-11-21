<!--
// [JAS]: [TODO] This should move into the plugin
function getCustomize(id) {
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

-->