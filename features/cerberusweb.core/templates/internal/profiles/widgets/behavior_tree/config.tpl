<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Worklist" class="peek">
		<legend>Display fields for this record:</legend>
		
		<b>Behavior ID:</b>
		
		<div style="margin-left:10px;">
			<input type="text" name="params[behavior_id]" value="{$widget->extension_params.behavior}" class="placeholders" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="off">
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
});
</script>