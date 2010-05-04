<div id="draft{$draft->id}">
	<div class="block">
		{$draft_worker = $workers.{$draft->worker_id}}
		<h3 style="display:inline;">
			{if $draft->is_queued}
				<span style="background-color:rgb(219,255,190);color:rgb(50,120,50);">{$translate->_('queued')|lower}</span>
			{else} 
				<span style="background-color:rgb(248,238,166);color:rgb(222,73,0);">{$translate->_('draft')|lower}</span>
			{/if} 
			{if !empty($draft_worker)}<a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$draft_worker->email|escape:'url'}', null, false, '500');" title="{$worker->email|escape}">{$draft_worker->getName()}</a>{else}{/if}
		</h3> &nbsp;
		
		{if !$draft->is_queued}
			{if $draft->worker_id==$active_worker->id && isset($draft->params.in_reply_message_id)}<a href="javascript:;" onclick="displayReply('{$draft->params.in_reply_message_id}',{if $draft->type=='ticket.forward'}1{else}0{/if},{$draft_id});">{$translate->_('Resume')|lower}</a>&nbsp;{/if}		
			{if $draft->worker_id==$active_worker->id}<a href="javascript:;" onclick="if(confirm('Are you sure you want to permanently delete this draft?')) { genericAjaxGet('', 'c=tickets&a=deleteDraft&draft_id={$draft_id}', function(o) { $('#draft{$draft_id}').remove(); } ); } ">{$translate->_('common.delete')|lower}</a>&nbsp;{/if}		
		{/if}
		<br>
		
		{if isset($draft->hint_to)}<b>{$translate->_('message.header.to')|capitalize}:</b> {$draft->hint_to|escape}<br>{/if}
		{if isset($draft->params.cc)}<b>{$translate->_('message.header.cc')|capitalize}:</b> {$draft->params.cc|escape}<br>{/if}
		{if isset($draft->params.bcc)}<b>{$translate->_('message.header.bcc')|capitalize}:</b> {$draft->params.bcc|escape}<br>{/if}
		{if isset($draft->subject)}<b>{$translate->_('message.header.subject')|capitalize}:</b> {$draft->subject|escape}<br>{/if}
		{if isset($draft->updated)}<b>{$translate->_('message.header.date')|capitalize}:</b> {$draft->updated|devblocks_date}<br>{/if}
		<pre class="emailbody" style="padding-top:10px;">{$draft->body|trim|escape|devblocks_hyperlinks}</pre>
	</div>
	<br>
</div>

