<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=config&a=showStorageProfilePeek&id=0&view_id={$view->id|escape:'url'}',null,true,'500');"><span class="cerb-sprite sprite-add"></span> {$translate->_('Add Storage Profile')|capitalize}</button>
</form>

<div id="view{$view->id}">{$view->render()}</div>

<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:5px;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="">

<div class="block">
	<h3>Database</h3>
	Data: <b>{$total_db_data|devblocks_prettybytes:2}</b><br>
	Indexes: <b>{$total_db_indexes|devblocks_prettybytes:2}</b><br>
	Total Disk Space: <b>{$total_db_size|devblocks_prettybytes:2}</b><br>
	<br>
	Running an OPTIMIZE on the database would free about <b>{$total_db_slack|devblocks_prettybytes:2}</b><br>
</div>
<br>

{foreach from=$storage_schemas item=schema key=schema_id}
	<div id="schema_{$schema_id|md5}">
	{include file="{$core_tpl}configuration/tabs/storage/schemas/display.tpl"}
	</div>
{/foreach}

</form>
