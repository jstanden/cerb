<fieldset style="margin-top:5px;">
	<b>Visibility:</b>
	<br>

	<label><input type="radio" name="event_params[visibility]" value="" {if empty($trigger->event_params.visibility)}checked="checked"{/if}> {'common.everyone'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="event_params[visibility]" value="bots" {if 'bots' == $trigger->event_params.visibility}checked="checked"{/if}> {'common.bots'|devblocks_translate|capitalize}</label>
</fieldset>