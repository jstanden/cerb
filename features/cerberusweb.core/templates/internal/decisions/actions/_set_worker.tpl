<select name="{$namePrefix}[worker_id]">
	<option value="0" {if empty($params.worker_id)}selected="selected"{/if}>({'common.nobody'|devblocks_translate|lower})</option>
	{foreach from=$workers item=worker key=worker_id}
	<option value="{$worker_id}" {if $params.worker_id==$worker_id}selected="selected"{/if}>{$worker->getName()}</option>
	{/foreach}
</select>