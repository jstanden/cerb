<form method="POST" action="javascript:;" id="tagPanel">
	<input type="hidden" name="c" value="core.display.module.workflow">
	<input type="hidden" name="a" value="saveTagDialog">
	<input type="hidden" name="id" value="{$tag->id}">
	
	<h1>Tag: {$tag->name}</h1>
	<b>Related Terms &amp; Phrases (one per line):</b><br>
	<textarea style="width:98%;height:50px;"></textarea><br>
	
	{if !empty($ticket_id)}
	<br>
	<input type="hidden" name="ticket_id" value="{$ticket_id}">
	<label><input type="checkbox" name="untag" value="1"> Remove '{$tag->name}' from current ticket</label><br>
	{/if}
	
	<br>
	<input type="button" value="{$translate->say('common.save_changes')}" onclick="displayAjax.postShowTag();">
	<input type="button" value="Cancel" onclick="displayAjax.tagDialog.hide();">
</form>