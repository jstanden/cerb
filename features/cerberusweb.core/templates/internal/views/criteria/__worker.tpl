<b>{'common.worker'|devblocks_translate|capitalize}:</b><br>
<select name="worker_id">
{foreach from=$workers item=worker key=worker_id}
	<option value="{$worker_id}" {if $param && $param->value==$worker_id}selected="selected"{/if}>{$worker->getName()}</option>
{/foreach}
</select>
<br>
<br>