<h2>Mail Filtering</h2>
{$only_event_ids = 'event.mail.received.app'}

{foreach from=$vas item=va key=va_id}
{if $va->behaviors && !empty($va->behaviors)}
	<h3 style="font-size:150%;margin-bottom:5px;">{$va->name}</h3>
	{include file="devblocks:cerberusweb.core::internal/decisions/assistant/tab.tpl" triggers_by_event=$va->behaviors}
{/if}
{/foreach}