{$draft_is_writeable = Context_Draft::isWriteableByActor($draft, $active_worker)}

<div {if !$embed}id="draftContainer{$draft->id}"{/if} class="block" style="position:relative;margin-bottom:10px;">
	{$draft_worker = $draft->getWorker()}

	{if !$embed}
	<div class="toolbar-minmax">
		{if $draft_is_writeable && $active_worker->hasPriv('contexts.cerberusweb.contexts.mail.draft.update')}
			<button type="button" class="cerb-button-edit" data-context="{CerberusContexts::CONTEXT_DRAFT}" data-context-id="{$draft->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span></button>
		{/if}

		{if $attachments}
			<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_ATTACHMENT}" data-query="on.draft:(id:{$draft->id})"><span class="glyphicons glyphicons-paperclip"></span></button>
		{/if}

		{$ticket = $draft->getTicket()}

		{if $ticket}
			{$permalink_url = "{devblocks_url full=true}c=profiles&type=ticket&mask={$ticket->mask}{/devblocks_url}/#draft{$draft->id}"}
			<button type="button" onclick="genericAjaxPopup('permalink', 'c=internal&a=invoke&module=records&action=showPermalinkPopup&url={$permalink_url|escape:'url'}');" title="{'common.permalink'|devblocks_translate|lower}"><span class="glyphicons glyphicons-link"></span></button>
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
		{if !empty($draft_worker)}<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$draft_worker->id}" style="font-weight:bold;font-size:1.2em;">{$draft_worker->getName()}</a>{else}{/if}
		&nbsp;
		{if $draft_worker->title}
			{$draft_worker->title}
		{/if}
	</div>

	<div style="float:left;margin:0 10px 10px 0;">
		<img src="{devblocks_url}c=avatars&context=worker&context_id={$draft_worker->id}{/devblocks_url}?v={$draft_worker->updated}" style="height:48px;width:48px;border-radius:48px;">
	</div>

	<div style="display:block;margin-top:2px;overflow:hidden;">
		<div style="line-height:1.4em;">
			{if isset($draft->hint_to)}<b>{'message.header.to'|devblocks_translate|capitalize}:</b> {$draft->getParam('to')}<br>{/if}
			{if $draft->params.cc}<b>{'message.header.cc'|devblocks_translate|capitalize}:</b> {$draft->getParam('cc')}<br>{/if}
			{if $draft->params.bcc}<b>{'message.header.bcc'|devblocks_translate|capitalize}:</b> {$draft->getParam('bcc')}<br>{/if}
			{if $draft->params.subject}<b>{'message.header.subject'|devblocks_translate|capitalize}:</b> {$draft->getParam('subject')}<br>{/if}
			{if $draft->queue_delivery_date}
				<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$draft->queue_delivery_date|devblocks_date} ({$draft->queue_delivery_date|devblocks_prettytime})<br>
			{elseif $draft->updated}
				<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$draft->updated|devblocks_date} ({$draft->updated|devblocks_prettytime})<br>
			{/if}
		</div>
	</div>

	<div style="clear:both;{if $expanded}margin-bottom:1em;{else}margin-bottom:0.5em;{/if}"></div>

	<div class="cerb-draft--content">
		{if 'parsedown' == $draft->params.format}
		<div class="emailBodyHtml" dir="auto">{$draft->getContent() nofilter}</div>
		{else}
		<pre class="emailbody" dir="auto" style="padding-top:10px;">{$draft->getContent()|trim|escape|devblocks_hyperlinks nofilter}</pre>
		{/if}

		{if isset($draft->params.file_ids) && is_array($draft->params.file_ids)}
		<div style="margin-top:10px;">
			{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context="{CerberusContexts::CONTEXT_DRAFT}" context_id="{$draft->id}"}
		</div>
		{/if}

		{$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_DRAFT, $draft->id))|default:[]}
		{if $values}
		{$draft_custom_fields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_DRAFT, $values)}
		{$draft_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_DRAFT, $draft->id, $values)}
		<div style="margin-top:10px;">
			{if $message_custom_fields}
				<fieldset class="properties" style="padding:5px 0;border:0;">
					<legend>{'common.properties'|devblocks_translate|capitalize}</legend>

					<div style="padding:0px 5px;display:flex;flex-flow:row wrap;">
						{foreach from=$draft_custom_fields item=v key=k name=message_custom_fields}
							<div style="flex:0 0 200px;text-overflow:ellipsis;">
								{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
							</div>
						{/foreach}
					</div>
				</fieldset>
			{/if}

			{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/profile_fieldsets.tpl" properties=$draft_custom_fieldsets}
		</div>
		{/if}

		{if !$embed}
		<div style="margin-top:10px;">
			{if $draft_is_writeable && !$draft->is_queued && (!$draft->worker_id || $draft->worker_id == $active_worker->id)}
			<button type="button" class="cerb-button-resume"><span class="glyphicons glyphicons-restart"></span> {'common.resume'|devblocks_translate|capitalize}</button>
			{/if}

			<button type="button" class="cerb-sticky-trigger" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="0" data-edit="context:{CerberusContexts::CONTEXT_DRAFT} context.id:{$draft->id}"><span class="glyphicons glyphicons-comments"></span> {'common.comment'|devblocks_translate|capitalize}</button>
		</div>
		{/if}

		<div id="draft{$draft->id}_notes" class="cerb-comments-thread">
			{if is_array($draft_notes) && isset($draft_notes.{$draft->id})}
				{include file="devblocks:cerberusweb.core::display/modules/conversation/notes.tpl" message_notes=$draft_notes message_id=$draft->id readonly=false}
			{/if}
		</div>
	</div>
</div>

{if !$embed}
<script type="text/javascript">
$(function() {
	var $draft = $('#draftContainer{$draft->id}');
	var $notes = $('#draft{$draft->id}_notes');
	
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

	$draft.find('.cerb-sticky-trigger')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			e.stopPropagation();

			if(e.id && e.comment_html) {
				var $new_note = $('<div id="comment' + e.id + '"/>')
					.addClass('cerb-comments-thread--comment')
					.hide()
				;
				$new_note.html(e.comment_html).prependTo($notes).fadeIn();
			}
		})
		;

	// Edit

	$draft.find('button.cerb-button-edit')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			e.stopPropagation();
			genericAjaxGet('draft{$draft->id}','c=profiles&a=invoke&module=draft&action=get&id={$draft->id}');
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