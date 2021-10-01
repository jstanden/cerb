<b>Store active content in:</b><br>
<select name="active_storage_profile">
	<option value="devblocks.storage.engine.disk" {if 'devblocks.storage.engine.disk'==$active_storage_profile}selected="selected"{/if}>Local filesystem (/storage)</option>
	<option value="devblocks.storage.engine.database" {if 'devblocks.storage.engine.database'==$active_storage_profile}selected="selected"{/if}>Local database</option>
</select><br>
<br>