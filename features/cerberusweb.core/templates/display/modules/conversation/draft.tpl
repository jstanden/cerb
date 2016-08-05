<div id="draft{$draft->id}">
	<div class="block" style="margin-bottom:10px;">
		{$draft_worker = $workers.{$draft->worker_id}}
		
		{if $draft->is_queued}
			{if !empty($draft->queue_delivery_date) && $draft->queue_delivery_date > time()}
				<span class="tag" style="background-color:rgb(120,120,120);color:white;margin-right:5px;">{'message.queued.deliver_in'|devblocks_translate:{$draft->queue_delivery_date|devblocks_prettytime}|lower}</span>
			{else}
				<span class="tag" style="background-color:rgb(120,120,120);color:white;margin-right:5px;">{'message.queued.delivery_immediate'|devblocks_translate|lower}</span>
			{/if}
		{else}
			<span class="tag" style="background-color:rgb(120,120,120);color:white;margin-right:5px;">{'draft'|devblocks_translate|lower}</span>
		{/if}
		
		<h3 style="display:inline;">
			{if !empty($draft_worker)}<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$draft_worker->id}">{$draft_worker->getName()}</a>{else}{/if}
		</h3> &nbsp;
		
		<div style="float:left;margin:0px 5px 5px 0px;">
			<img src="{devblocks_url}c=avatars&context=worker&context_id={$draft_worker->id}{/devblocks_url}?v={$draft_worker->updated}" style="height:64px;width:64px;border-radius:64px;">
		</div>
		
		<br>
		
		{if isset($draft->hint_to)}<b>{'message.header.to'|devblocks_translate|capitalize}:</b> {$draft->hint_to}<br>{/if}
		{if isset($draft->params.cc)}<b>{'message.header.cc'|devblocks_translate|capitalize}:</b> {$draft->params.cc}<br>{/if}
		{if isset($draft->params.bcc)}<b>{'message.header.bcc'|devblocks_translate|capitalize}:</b> {$draft->params.bcc}<br>{/if}
		{if isset($draft->subject)}<b>{'message.header.subject'|devblocks_translate|capitalize}:</b> {$draft->subject}<br>{/if}
		{if !empty($draft->queue_delivery_date)}
			<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$draft->queue_delivery_date|devblocks_date}<br>
		{elseif !empty($draft->updated)}
			<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$draft->updated|devblocks_date}<br>
		{/if}
		<pre class="emailbody" style="padding-top:10px;">{$draft->body|trim|escape|devblocks_hyperlinks nofilter}</pre>
		
		{if !$draft->is_queued}
			<div style="margin-top:10px;">
			{if $draft->worker_id==$active_worker->id && isset($draft->params.in_reply_message_id)}
				<button type="button" onclick="displayReply('{$draft->params.in_reply_message_id}',{if $draft->type=='ticket.forward'}1{else}0{/if},{$draft_id});"><span class="glyphicons glyphicons-share"></span> {'Resume'|devblocks_translate|capitalize}</button>
			{/if}
			
			{if $draft->worker_id==$active_worker->id || $active_worker->hasPriv('core.mail.draft.delete_all')}
				<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this draft?')) { genericAjaxGet('', 'c=profiles&a=handleSectionAction&section=draft&action=deleteDraft&draft_id={$draft_id}', function(o) { $('#draft{$draft_id}').remove(); } ); } "><span class="glyphicons glyphicons-circle-remove" title="{'common.delete'|devblocks_translate|lower}"></span> {'common.delete'|devblocks_translate|capitalize}</button>&nbsp;
			{/if}
			</div>
		{/if}
	</div>
	
	{if is_array($draft_notes) && isset($draft_notes.{$draft->id})}
	{$draft_readonly = !($draft->worker_id==$active_worker->id || $active_worker->hasPriv('core.mail.draft.delete_all'))}
	<div id="draft{$draft->id}_notes" style="background-color:rgb(255,255,255);">
		{include file="devblocks:cerberusweb.core::display/modules/conversation/notes.tpl" message_notes=$draft_notes message_id=$draft->id readonly=false}
	</div>
	{/if}
</div>

<script type="text/javascript">
$(function() {
	var $draft = $('#draft{$draft->id}');
	
	$draft.find('.cerb-peek-trigger').cerbPeekTrigger();
});
</script>