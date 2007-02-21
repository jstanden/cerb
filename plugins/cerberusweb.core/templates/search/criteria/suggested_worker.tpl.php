Matching Suggested Workers:<br>
{foreach from=$workers item=worker name=workers}
	<label><input type="checkbox" name="worker_id[]" value="{$worker->id}">{$worker->login}</label><br>
{/foreach}