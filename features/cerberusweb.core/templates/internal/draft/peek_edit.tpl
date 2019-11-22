{$peek_context = CerberusContexts::CONTEXT_DRAFT}
{$peek_context_id = $draft->id}
{$form_id = uniqid('frm')}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="{$form_id}" name="frmDraftPeek" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="draft">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$draft->id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="0" cellspacing="2" border="0" width="98%" style="margin-bottom:10px;">
	<tbody>
		{$ticket = $draft->getTicket()}
		{if $ticket}
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.on'|devblocks_translate|capitalize}:</b>&nbsp;</td>
			<td width="99%">
				<ul class="bubbles">
					<li>
						<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-context-id="{$ticket->id}">#{$ticket->mask}: {$ticket->subject}</a>
					</li>
				</ul>
			</td>
		</tr>
		{/if}

		{$worker = $draft->getWorker()}
		{if $worker}
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'message.header.from'|devblocks_translate|capitalize}:</b>&nbsp;</td>
			<td width="99%">
				<ul class="bubbles">
					<li>
						<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$worker->id}{/devblocks_url}?v={$worker->updated}">
						<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker->id}">{$worker->getName()}</a>
					</li>
				</ul>
			</td>
		</tr>
		{/if}

		<tr>
			<td width="1%" nowrap="nowrap"><b>{'message.header.to'|devblocks_translate|capitalize}:</b>&nbsp;</td>
			<td width="99%">
				{$draft->getParam('to')}
			</td>
		</tr>

		<tr>
			<td width="1%" nowrap="nowrap"><b>{'message.header.subject'|devblocks_translate|capitalize}:</b>&nbsp;</td>
			<td width="99%">
				{$draft->getParam('subject')}
			</td>
		</tr>

		<tr>
			<td width="1%" nowrap="nowrap"><b>{'message.header.date'|devblocks_translate|capitalize}:</b>&nbsp;</td>
			<td width="99%">
				{$draft->updated|devblocks_date}
			</td>
		</tr>

		{if $draft->is_queued}
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{'common.status'|devblocks_translate|capitalize}:</b>&nbsp;</td>
			<td width="99%">
				<label><input type="radio" name="is_queued" value="0" {if !$draft->is_queued}checked{/if}> {'draft'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="is_queued" value="1" {if $draft->is_queued}checked{/if}> {'queued'|devblocks_translate|capitalize}</label>
			</td>
		</tr>
		{/if}
	</tbody>

	{if $draft->is_queued}
	<tbody class="cerb-tbody-queued" style="display:{if $draft->is_queued}table-row-group{else}none{/if};">
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{'Send at'|devblocks_translate|capitalize}:</b>&nbsp;</td>
			<td width="99%">
				<input type="text" name="send_at" value="{$draft->queue_delivery_date|devblocks_date}" placeholder="now" style="width:90%;">
			</td>
		</tr>
	</tbody>
	{/if}

	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$draft->id}

{if !empty($draft->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this draft?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $active_worker->hasPriv("contexts.{$peek_context}.delete") && !empty($draft)}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title','{'common.draft'|devblocks_translate|capitalize}');

		var $tbody_queued = $popup.find('.cerb-tbody-queued');
		
		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Abstract choosers
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		// Abstract peeks
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Radios
		$popup.find('[name=is_queued]').on('click', function(e) {
			if($(this).val() === '1') {
				$tbody_queued.show();
			} else {
				$tbody_queued.hide();
			}
		});

		// Dates
		$popup.find('[name=send_at]').cerbDateInputHelper();
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>