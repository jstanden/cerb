{foreach from=$triggers item=trigger key=trigger_id}
<form id="decisionTree{$trigger_id}" action="javascript:;" onsubmit="return false;">
	{$event = $events.{$trigger->event_point}}
	{include file="devblocks:cerberusweb.core::internal/decisions/tree.tpl" trigger=$trigger event=$event}
</form>
{/foreach}