{$draft_is_writeable = Context_Draft::isWriteableByActor($draft, $active_worker)}

<div {if !$embed}id="draftContainer{$draft->id}"{/if} class="block" style="position:relative;margin-bottom:10px;">
	{$draft_worker = $draft->getWorker()}

	{if !$embed}
	<div class="toolbar-minmax">
		<button type="button" class="cerb-button-edit" data-context="{CerberusContexts::CONTEXT_DRAFT}" data-context-id="{$draft->id}" title="Open card popup (Shift+Click to edit)"><span class="glyphicons glyphicons-new-window-alt"></span></button>

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
		{if $draft_worker}
			<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$draft_worker->id}" style="font-weight:bold;font-size:1.2em;">{$draft_worker->getName()}</a>
			{if $draft_worker->title}
				{$draft_worker->title}
			{/if}
		{else}
			{if $draft->params.from}
				<span style="font-weight:bold;font-size:1.2em;">{$draft->getParam('from')}</span>
			{/if}
		{/if}
	</div>

	<div style="float:left;margin:0 10px 10px 0;">
		{if $draft_worker}
			<img src="{devblocks_url}c=avatars&context=worker&context_id={$draft_worker->id}{/devblocks_url}?v={$draft_worker->updated}" style="height:48px;width:48px;border-radius:48px;">
		{else}
			<img src="{devblocks_url}c=avatars&context=bot&context_id=0{/devblocks_url}?v={$smarty.const.APP_BUILD}" style="height:48px;width:48px;border-radius:48px;">
		{/if}
	</div>

	<div style="display:block;margin-top:2px;overflow:hidden;">
		<div style="line-height:1.4em;">
			{$to = $draft->hint_to|default:$draft->getParam('to')}
			{if $to}<b>{'message.header.to'|devblocks_translate|capitalize}:</b> {$to}<br>{/if}
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

		{$values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_DRAFT, $draft->id)}
		{$values = array_shift($values)|default:[]}
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
			
			<div data-cerb-toolbar style="display:inline-block;">
			{$draft_dict = DevblocksDictionaryDelegate::instance([
				'caller_name' => 'cerb.toolbar.draft.read',
				
				'draft__context' => CerberusContexts::CONTEXT_DRAFT,
				'draft_id' => $draft->id,

				'worker__context' => CerberusContexts::CONTEXT_WORKER,
				'worker_id' => $active_worker->id
			])}

			{$toolbar = []}
			{$toolbar_draft_read = DAO_Toolbar::getByName('draft.read')}
			{if $toolbar_draft_read}
				{$toolbar = $toolbar_draft_read->getKata($draft_dict)}
			{/if}

			{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}
			</div>
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
	var $toolbar = $draft.find('[data-cerb-toolbar]');
	var $profile_tab = $toolbar.closest('.cerb-profile-layout');
	var $notes = $('#draft{$draft->id}_notes');
	
	$draft.find('.cerb-peek-trigger').cerbPeekTrigger();
	
	$draft.find('button.cerb-button-resume').on('click', $.throttle(500, function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		var evt = jQuery.Event('cerb_reply');
		evt.message_id = '{$draft->params.in_reply_message_id}';
		evt.is_forward = {if $draft->type=='ticket.forward'}1{else}0{/if};
		evt.draft_id = {$draft->id};
		evt.reply_mode = 0;

		$draft.trigger(evt);
	}));

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
	
	var doneFunc = function(e) {
		e.stopPropagation();

		var $target = e.trigger;

		if(!$target.is('.cerb-bot-trigger'))
			return;

		var done_params = new URLSearchParams($target.attr('data-interaction-done'));

		if(!done_params.has('refresh_widgets[]'))
			return;

		var refresh = done_params.getAll('refresh_widgets[]');

		var widget_ids = [];

		if(-1 !== $.inArray('all', refresh)) {
			// Everything
		} else {
			$profile_tab.find('.cerb-profile-widget')
				.filter(function() {
					var $this = $(this);
					var name = $this.attr('data-widget-name');
	
					if(undefined === name)
						return false;
	
					return -1 !== $.inArray(name, refresh);
				})
				.each(function() {
					var $this = $(this);
					var widget_id = parseInt($this.attr('data-widget-id'));
	
					if(widget_id)
						widget_ids.push(widget_id);
				})
			;
		}

		var evt = $.Event('cerb-widgets-refresh', {
			widget_ids: widget_ids,
			refresh_options: { }
		});

		$profile_tab.triggerHandler(evt);
	};

	$toolbar.cerbToolbar({
		caller: {
			name: 'cerb.toolbar.draft.read',
			params: {
				draft_id: '{$draft->id}',
				selected_text: ''
			}
		},
		start: function(formData) {
			formData.set('caller[params][selected_text]', document.getSelection());
		},
		done: doneFunc
	});
});
</script>
{/if}