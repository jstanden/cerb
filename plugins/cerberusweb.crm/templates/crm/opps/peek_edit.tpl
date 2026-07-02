{$peek_context = CerberusContexts::CONTEXT_OPPORTUNITY}
{$peek_context_id = $opp->id}
{$form_id = uniqid('formOppPeek')}
{$status_id = $opp->status_id|default:0}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="opportunity">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="opp_id" value="{$opp->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
			<input type="text" name="name" value="{$opp->name}" autocomplete="off" autofocus="autofocus" placeholder="Potential sale">
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field cerb-u-flex-2">
				<label class="cerb-ui-form--label">{'crm.opportunity.amount'|devblocks_translate|capitalize}</label>
				<input type="text" name="currency_amount" value="{if $opp}{$opp->getAmountString(false)}{/if}" placeholder="1,500.00" autocomplete="off">
			</div>
			<div class="cerb-ui-form--field cerb-u-flex-1">
				<label class="cerb-ui-form--label">{'common.currency'|devblocks_translate|capitalize}</label>
				<select name="currency_id" data-cerb-currency>
					{if is_array($currencies)}
					{foreach from=$currencies item=currency}
					<option value="{$currency->id}" {if $opp->currency_id == $currency->id}selected="selected"{/if}>{$currency->name_plural|default:$currency->name} ({$currency->code})</option>
					{/foreach}
					{/if}
				</select>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
			<input type="hidden" name="status_id" id="status{$form_id}" value="{$status_id}">
			<div>
				<div class="cerb-ui-switcher" data-cerb-input="status{$form_id}">
					<button type="button" data-value="0" {if $status_id == 0}class="cerb-ui-switcher--active"{/if}>{'crm.opp.status.open'|devblocks_translate|capitalize}</button>
					<button type="button" data-value="1" {if $status_id == 1}class="cerb-ui-switcher--active"{/if}>{'crm.opp.status.closed.won'|devblocks_translate|capitalize}</button>
					<button type="button" data-value="2" {if $status_id == 2}class="cerb-ui-switcher--active"{/if}>{'crm.opp.status.closed.lost'|devblocks_translate|capitalize}</button>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field" id="closedDate{$form_id}" {if !$status_id}style="display:none;"{/if}>
			<label class="cerb-ui-form--label">{'crm.opportunity.closed_date'|devblocks_translate|capitalize}</label>
			<input type="text" name="closed_date" class="input_date" value="{if !empty($opp->closed_date)}{$opp->closed_date|devblocks_date}{/if}">
		</div>

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$opp->id}

{include file="devblocks:cerberusweb.core::internal/cards/editors/comment.tpl"}

{if !empty($opp->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="opportunity"}
{/if}

<div class="buttons" style="margin-top:10px;">
	{if (!$opp->id && $active_worker->hasPriv("contexts.{$peek_context}.create")) || ($opp->id && $active_worker->hasPriv("contexts.{$peek_context}.update"))}
		<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		{if $active_worker->hasPriv("contexts.{$peek_context}.delete") && !empty($opp->id)}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
	{else}
		<div class="cerb-u-text-muted">You do not have permission to modify this record.</div>
	{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open',function() {
		$popup.dialog('option','title', '{'Opportunity'|devblocks_translate|escape:'javascript' nofilter}');

		// Status switcher (Open / Won / Lost) — reveal the closed-date field for any non-open status
		let statusInput = document.getElementById('status{$form_id}');
		let statusEl = $popup.find('[data-cerb-input="status{$form_id}"]')[0];
		let $closedDate = $popup.find('#closedDate{$form_id}');
		if(statusInput && statusEl && window.CerbUI && CerbUI.Switcher)
			new CerbUI.Switcher(statusEl, {
				value: statusInput.value,
				onSelect: function(value) {
					statusInput.value = value;
					$closedDate.toggle(value !== '0');
				}
			});

		// Currency
		if(window.CerbUI && CerbUI.SelectMenu)
			$popup.find('select[data-cerb-currency]').each(function() { new CerbUI.SelectMenu(this, { filter: true }); });

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Abstract peeks
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Date picker
		$frm.find('input.input_date').each(function() { if(window.CerbUI && CerbUI.DatePicker) new CerbUI.DatePicker.FormInput(this); });

	});
});
</script>
