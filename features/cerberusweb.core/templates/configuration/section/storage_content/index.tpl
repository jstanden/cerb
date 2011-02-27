<h2>Storage Content</h2>

<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:5px;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="">

<fieldset>
	<legend>Database</legend>
	
	Data: <b>{$total_db_data|devblocks_prettybytes:2}</b><br>
	Indexes: <b>{$total_db_indexes|devblocks_prettybytes:2}</b><br>
	Total Disk Space: <b>{$total_db_size|devblocks_prettybytes:2}</b><br>
	<br>
	Running an OPTIMIZE on the database would free about <b>{$total_db_slack|devblocks_prettybytes:2}</b><br>
</fieldset>

{foreach from=$storage_schemas item=schema key=schema_id}
	<div id="schema_{$schema_id|md5}">
	{include file="devblocks:cerberusweb.core::configuration/section/storage_content/rule.tpl"}
	</div>
{/foreach}

</form>
