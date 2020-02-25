<h2>{'common.storage'|devblocks_translate|capitalize}</h2>

<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:5px;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>Database</legend>

	Data: <b>{$total_db_data|devblocks_prettybytes:2}</b><br>
	Indexes: <b>{$total_db_indexes|devblocks_prettybytes:2}</b><br>
	Total Disk Space: <b>{$total_db_size|devblocks_prettybytes:2}</b><br>
</fieldset>

{foreach from=$storage_schemas item=schema key=schema_id}
	<div id="schema_{$schema_id|md5}">
	{include file="devblocks:cerberusweb.core::configuration/section/storage_content/rule.tpl"}
	</div>
{/foreach}

</form>
