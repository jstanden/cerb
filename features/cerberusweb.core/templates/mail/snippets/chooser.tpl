{*
<form>
	<label><input type="radio" name="scope" value="" checked="checked"> Favorites</label>
	<label><input type="radio" name="scope" value=""> Most Popular</label>
	<label><input type="radio" name="scope" value=""> Most Recent</label>
	<label><input type="radio" name="scope" value=""> Search</label>
</form>
*}

<div id="view{$view->id}">
  	{$view->render()}
</div>

<script language="JavaScript1.2" type="text/javascript">
	genericPanel.one('dialogopen',function(event,ui) {
		genericPanel.dialog('option','title',"Favorite Snippets");
		$('#view{$view->id}').data('context_id','{$context_id}');
		$('#view{$view->id}').data('text_element','{$text_element}');
	} );
</script>
