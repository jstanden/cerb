<h1>Preferences</h1>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveDefaults">

<b>Timezone:</b><br>
<select name="timezone">
	<option value="">---</option>
</select><br>
<br>

<b>Signature:</b><br>
<textarea name="default_signature" rows="5" cols="50"></textarea><br>
<br>

<b>Reply Box Lines:</b><br>
<input type="text" name="reply_box_lines" size="4" value="10"><br>
<br>

<input type="submit" value="{$translate->_('common.save_changes')}">

</form>