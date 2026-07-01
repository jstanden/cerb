<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset class="peek">
		<legend>Display this project board</legend>
		
		<b><a class="cerb-chooser" data-context="{CerberusContexts::CONTEXT_PROJECT_BOARD}" data-single="true">ID</a>:</b>
		
		<div style="margin-left:10px;">
			<input type="text" name="params[project_board_id]" value="{$widget->params.project_board_id}" class="placeholders" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="false">
		</div>
	</fieldset>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $config = $('#widget{$widget->id}Config');
	let $input = $config.find('input[name="params[project_board_id]"]');
	
	if(window.CerbUI && CerbUI.RecordChooser) CerbUI.RecordChooser.pickerLink($config.find('.cerb-chooser')[0], { input: $input });
});
</script>