{if $active_worker->hasPriv('feedback.actions.create')}
<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=feedback&a=showEntry&id=0&view_id={$view->id}',null,false,'500');"><img src="{devblocks_url}c=resource&p=cerberusweb.feedback&f=images/question_and_answer.png{/devblocks_url}" align="top"> {$translate->_('feedback.button.capture')|capitalize}</button>
</form>
{/if}

{include file="$core_tpl/internal/views/search_and_view.tpl" view=$view}