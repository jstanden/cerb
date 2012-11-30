<fieldset class="peek" style="margin-top:10px;" id="widget{$widget->id}Config">
	<legend>Custom HTML</legend>
	
	<div>
		<textarea name="params[content]" style="width:100%;height:150px;">{$widget->params.content}</textarea>
	</div>
</fieldset>

<script type="text/javascript">
	$('#widget{$widget->id}Config textarea').markItUp(markitupHTMLSettings);
</script>