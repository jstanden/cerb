<b>Store active content in:</b><br>
<select name="active_storage_profile">
	<option value="devblocks.storage.engine.disk" {if 'devblocks.storage.engine.disk'==$active_storage_profile}selected="selected"{/if}>Local filesystem (/storage)</option>
	<option value="devblocks.storage.engine.database" {if 'devblocks.storage.engine.database'==$active_storage_profile}selected="selected"{/if}>Local database</option>
</select><br>
<br>

<b>Archive inactive content to:</b><br>
<select name="archive_storage_profile">
	<option value="devblocks.storage.engine.disk" {if 'devblocks.storage.engine.disk'==$archive_storage_profile}selected="selected"{/if}>Local filesystem (/storage)</option>
	<option value="devblocks.storage.engine.database" {if 'devblocks.storage.engine.database'==$archive_storage_profile}selected="selected"{/if}>Local database</option>
	{foreach from=$storage_profiles item=profile key=profile_id}
		<option value="{$profile_id}" {if $profile_id==$archive_storage_profile}selected="selected"{/if}>{$profile->name|escape}</option>
	{/foreach}
</select><br>
<br>

<b>Archive after:</b><br>
<input type="text" name="archive_after_days" size="4" value="{$archive_after_days}"> days of inactivity<br>
<br>
