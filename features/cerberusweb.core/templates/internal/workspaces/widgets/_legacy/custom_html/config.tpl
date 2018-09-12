<fieldset class="peek" style="margin-top:10px;" id="widget{$widget->id}Config">
	<legend>Custom HTML</legend>
	
	<b>Display</b> content using this template: 
	<div>
		<textarea name="params[content]" style="width:100%;height:150px;">{$widget->params.content}</textarea>
	</div>
	<br>
</fieldset>

<script type="text/javascript">
$(function() {
	var $widget = $('#widget{$widget->id}Config');
	var $textarea = $widget.find('textarea');
	
	// Placeholders
	
	$widget.find('button.cerb-popupmenu-trigger').click(function() {
		$menu.toggle();
	});
	
	// Syntax editor autocompletion
	
	$textarea
		.cerbCodeEditor()
		;
});
</script>