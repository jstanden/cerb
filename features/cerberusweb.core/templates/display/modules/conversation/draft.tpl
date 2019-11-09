{$draft_is_writeable = Context_Draft::isWriteableByActor($draft, $active_worker)}

<div {if !$embed}id="draftContainer{$draft->id}"{/if} class="block" style="margin-bottom:10px;">
	{$draft_worker = $draft->getWorker()}

	{if !$embed}
	<div class="toolbar-minmax" style="float:right;display:none;">
		{if $draft_is_writeable && $active_worker->hasPriv('contexts.cerberusweb.contexts.mail.draft.update')}
			{if $draft->is_queued}
				<button type="button" class="cerb-button-edit" data-context="{CerberusContexts::CONTEXT_DRAFT}" data-context-id="{$draft->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span></button>
			{else}
				<button type="button" class="cerb-button-resume"><span class="glyphicons glyphicons-cogwheel"></span></button>
			{/if}
		{/if}

		{if $attachments}
			<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_ATTACHMENT}" data-query="on.draft:(id:{$draft->id})"><span class="glyphicons glyphicons-paperclip"></span></button>
		{/if}

		{$ticket = $draft->getTicket()}

		{if $ticket}
			{$permalink_url = "{devblocks_url full=true}c=profiles&type=ticket&mask={$ticket->mask}{/devblocks_url}/#draft{$draft->id}"}
			<button type="button" onclick="genericAjaxPopup('permalink', 'c=internal&a=showPermalinkPopup&url={$permalink_url|escape:'url'}');" title="{'common.permalink'|devblocks_translate|lower}"><span class="glyphicons glyphicons-link"></span></button>
		{/if}
	</div>
	{/if}

	{if $draft->is_queued}
		{if !empty($draft->queue_delivery_date) && $draft->queue_delivery_date > time()}
			<span class="tag" style="background-color:rgb(120,120,120);color:white;margin-right:5px;">{'message.queued.deliver_in'|devblocks_translate:{$draft->queue_delivery_date|devblocks_prettytime}|lower}</span>
		{else}
			<span class="tag" style="background-color:rgb(120,120,120);color:white;margin-right:5px;">{'message.queued.delivery_immediate'|devblocks_translate|lower}</span>
		{/if}
	{else}
		<span class="tag" style="background-color:rgb(120,120,120);color:white;margin-right:5px;">{'draft'|devblocks_translate|lower}</span>
	{/if}

	<div style="display:inline-block;">
		{if !empty($draft_worker)}<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$draft_worker->id}" style="font-size:1.3em;font-weight:bold;">{$draft_worker->getName()}</a>{else}{/if}
		&nbsp;
		{if $draft_worker->title}
			{$draft_worker->title}
		{/if}
	</div>

	<div style="float:left;margin:0px 5px 5px 0px;">
		<img src="{devblocks_url}c=avatars&context=worker&context_id={$draft_worker->id}{/devblocks_url}?v={$draft_worker->updated}" style="height:64px;width:64px;border-radius:64px;">
	</div>

	<br>

	{if isset($draft->hint_to)}<b>{'message.header.to'|devblocks_translate|capitalize}:</b> {$draft->hint_to}<br>{/if}
	{if $draft->params.cc}<b>{'message.header.cc'|devblocks_translate|capitalize}:</b> {$draft->params.cc}<br>{/if}
	{if $draft->params.bcc}<b>{'message.header.bcc'|devblocks_translate|capitalize}:</b> {$draft->params.bcc}<br>{/if}
	{if $draft->subject}<b>{'message.header.subject'|devblocks_translate|capitalize}:</b> {$draft->subject}<br>{/if}
	{if $draft->queue_delivery_date}
		<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$draft->queue_delivery_date|devblocks_date}<br>
	{elseif $draft->updated}
		<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$draft->updated|devblocks_date}<br>
	{/if}

	<div style="clear:both;"></div>

	{if 'parsedown' == $draft->params.format}
	<div class="emailBodyHtml">{$draft->getContent() nofilter}</div>
	{else}
	<pre class="emailbody" style="padding-top:10px;">{$draft->getContent()|trim|escape|devblocks_hyperlinks nofilter}</pre>
	{/if}

	{if isset($draft->params.file_ids) && is_array($draft->params.file_ids)}
	<div style="margin-top:10px;">
		{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context="{CerberusContexts::CONTEXT_DRAFT}" context_id="{$draft->id}"}
	</div>
	{/if}
</div>

{if is_array($draft_notes) && isset($draft_notes.{$draft->id})}
<div id="draft{$draft->id}_notes" style="background-color:rgb(255,255,255);">
	{include file="devblocks:cerberusweb.core::display/modules/conversation/notes.tpl" message_notes=$draft_notes message_id=$draft->id readonly=false}
</div>
{/if}

{if !$embed}
<script type="text/javascript">
$(function() {
	var $draft = $('#draftContainer{$draft->id}');
	
	$draft.find('.cerb-peek-trigger').cerbPeekTrigger();
	
	$draft.find('button.cerb-button-resume').on('click', function() {
		var evt = jQuery.Event('cerb_reply');
		evt.message_id = '{$draft->params.in_reply_message_id}';
		evt.is_forward = {if $draft->type=='ticket.forward'}1{else}0{/if};
		evt.draft_id = {$draft->id};
		evt.reply_mode = 0;
		evt.is_confirmed = 1;

		$draft.trigger(evt);
	});

	// Edit

	$draft.find('button.cerb-button-edit')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			e.stopPropagation();
			genericAjaxGet('draft{$draft->id}','c=display&a=getDraft&id={$draft->id}');
		})
		.on('cerb-peek-deleted', function(e) {
			e.stopPropagation();
			$('#draft{$draft->id}').remove();

		})
		.on('cerb-peek-closed', function(e) {
		})
		;

	$draft.hover(
		function() {
			$draft.find('div.toolbar-minmax').show();
		},
		function() {
			$draft.find('div.toolbar-minmax').hide();
		}
	);

	$draft.find('.cerb-search-trigger')
		.cerbSearchTrigger()
		;
});
</script>
{/if}