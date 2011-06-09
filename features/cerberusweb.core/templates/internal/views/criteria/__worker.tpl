<b>{'common.worker'|devblocks_translate|capitalize}:</b><br>
<select name="worker_id">
{foreach from=$workers item=worker key=worker_id}
	<option value="{$worker_id}">{$worker->getName()}</option>
{/foreach}
</select>
<br>
<br>