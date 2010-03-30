<div id="draft{$draft->id}">
	<div class="block">
		{$draft_worker = $workers.{$draft->worker_id}}
		<h3 style="display:inline;"><span style="background-color:rgb(248,238,166);color:rgb(222,73,0);">{$translate->_('draft')|lower}</span> {if !empty($draft_worker)}<a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$draft_worker->email|escape:'url'}', this, false, '500');" title="{$worker->email|escape}">{$draft_worker->getName()}</a>{else}{/if}</h3> &nbsp;
		
		{if $draft->worker_id==$active_worker->id && isset($draft->params.in_reply_message_id)}<a href="javascript:;" onclick="displayReply('{$draft->params.in_reply_message_id}',0,{$draft_id});">{$translate->_('Resume')|lower}</a>&nbsp;{/if}		
		{if $draft->worker_id==$active_worker->id}<a href="javascript:;" onclick="if(confirm('Are you sure you want to permanently delete this draft?')) { genericAjaxGet('', 'c=tickets&a=deleteDraft&draft_id={$draft_id}', function(o) { $('#draft{$draft_id}').remove(); } ); } ">{$translate->_('common.delete')|lower}</a>&nbsp;{/if}		
		<br>
		
		{if isset($draft->hint_to)}<b>{$translate->_('message.header.to')|capitalize}:</b> {$draft->hint_to|escape}<br>{/if}
		{if isset($draft->subject)}<b>{$translate->_('message.header.subject')|capitalize}:</b> {$draft->subject|escape}<br>{/if}
		{if isset($draft->updated)}<b>{$translate->_('message.header.date')|capitalize}:</b> {$draft->updated|devblocks_date}<br>{/if}
		<pre>{$draft->body|trim|escape|makehrefs}</pre>
	</div>
	<br>
</div>

