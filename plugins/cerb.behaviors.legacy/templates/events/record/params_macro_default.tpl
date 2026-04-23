<fieldset style="margin-top:5px;">
	<b>{'common.visibility'|devblocks_translate|capitalize}:</b>
	<br>

	<label><input type="radio" name="is_private" value="0" {if empty($trigger->is_private)}checked="checked"{/if}> {'common.public'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="is_private" value="1" {if !empty($trigger->is_private)}checked="checked"{/if}> {'common.private'|devblocks_translate|capitalize}</label>
</fieldset>