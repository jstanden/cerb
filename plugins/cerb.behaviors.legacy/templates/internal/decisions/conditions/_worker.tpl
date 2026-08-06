<select name="{$namePrefix}[oper]">
	<option value="in" {if $params.oper=='in'}selected="selected"{/if}>is any of</option>
	<option value="!in" {if $params.oper=='!in'}selected="selected"{/if}>is not any of</option>
</select>
<br>

<div class="cerb-ui-record-chooser chooser_worker unbound">
{if isset($params.worker_id)}
{foreach from=$params.worker_id item=worker_id}
	{$context_worker = $workers.$worker_id}
	{if !empty($context_worker)}
	<li data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$context_worker->id}" data-label="{$context_worker->getName()}"></li>
	{/if}
{/foreach}
{/if}
</div>
