<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Worklist" class="peek">
		<legend>Display fields for this record:</legend>
		
		<b><a class="cerb-chooser" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-single="true">Behavior ID</a>:</b>
		
		<div style="margin-left:10px;">
			<input type="text" name="params[behavior_id]" value="{$widget->extension_params.behavior_id}" class="placeholders" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="false">
		</div>
	</fieldset>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	
	var $input = $config.find('input[name="params[behavior_id]"]');
	
	if(window.CerbUI && CerbUI.RecordChooser) CerbUI.RecordChooser.pickerLink($config.find('.cerb-chooser')[0], { input: $input });
});
</script>