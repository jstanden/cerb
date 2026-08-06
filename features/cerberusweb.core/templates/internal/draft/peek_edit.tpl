{$peek_context = CerberusContexts::CONTEXT_DRAFT}
{$peek_context_id = $draft->id}
{$form_id = uniqid('frm')}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="{$form_id}" name="frmDraftPeek">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="draft">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$draft->id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			{$ticket = $draft->getTicket()}
			{if $ticket}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.on'|devblocks_translate|capitalize}</label>
				<div><a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-context-id="{$ticket->id}">#{$ticket->mask}: {$ticket->subject}</a></div>
			</div>
			{/if}

			{$worker = $draft->getWorker()}
			{if $worker}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'message.header.from'|devblocks_translate|capitalize}</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
					<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$worker->id}{/devblocks_url}?v={$worker->updated}">
					<a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker->id}">{$worker->getName()}</a>
				</div>
			</div>
			{/if}
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'message.header.to'|devblocks_translate|capitalize}</label>
				<div>{$draft->getParam('to')}</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'message.header.subject'|devblocks_translate|capitalize}</label>
				<div>{$draft->getParam('subject')}</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'message.header.date'|devblocks_translate|capitalize}</label>
			<div>{$draft->updated|devblocks_date}</div>
		</div>

		{if $draft->is_queued}
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
			<div>
				<input type="hidden" name="is_queued" id="isQueued_{$form_id}" value="{$draft->is_queued}">
				<div class="cerb-ui-switcher" id="queuedSwitcher_{$form_id}" data-cerb-input="isQueued_{$form_id}">
					<button type="button" data-value="0"{if !$draft->is_queued} class="cerb-ui-switcher--active"{/if}>{'draft'|devblocks_translate|capitalize}</button>
					<button type="button" data-value="1"{if $draft->is_queued} class="cerb-ui-switcher--active"{/if}>{'queued'|devblocks_translate|capitalize}</button>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field" id="sendAt_{$form_id}"{if !$draft->is_queued} style="display:none;"{/if}>
			<label class="cerb-ui-form--label">{'Send at'|devblocks_translate|capitalize}</label>
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
				<input type="text" name="send_at" class="input_date" style="flex:1 1 auto;min-width:0;" value="{$draft->queue_delivery_date|devblocks_date}" placeholder="now">
			</div>
		</div>
		{/if}

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$draft->id}

{if !empty($draft->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="draft"}
{/if}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $active_worker->hasPriv("contexts.{$peek_context}.delete") && !empty($draft)}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open',function() {
		$popup.dialog('option','title','{'common.draft'|devblocks_translate|capitalize}');

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		$popup.find('[name=send_at]').each(function() { if(window.CerbUI && CerbUI.DatePicker) new CerbUI.DatePicker.FormInput(this); });

		if(window.CerbUI && CerbUI.Switcher) {
			$popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				let input = document.getElementById(this.getAttribute('data-cerb-input'));
				let isQueued = (this.id === 'queuedSwitcher_{$form_id}');
				new CerbUI.Switcher(this, {
					value: input ? input.value : null,
					onSelect: function(value) {
						if(input) input.value = value;
						if(isQueued) {
							let $sendAt = $('#sendAt_{$form_id}');
							if(value === '1') { $sendAt.stop(true,true).css('display','flex'); } else { $sendAt.stop(true,true).hide(); }
						}
					}
				});
			});
		}
	});
});
</script>
