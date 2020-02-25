{$peek_context = CerberusContexts::CONTEXT_OPPORTUNITY}
{$peek_context_id = $opp->id}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formOppPeek" name="formOppPeek" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="opportunity">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="opp_id" value="{$opp->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="0" cellspacing="2" border="0" width="98%" style="margin-bottom:10px;">
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{'common.name'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<input type="text" name="name" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$opp->name}" autocomplete="off" autofocus="autofocus" placeholder="Potential sale">
		</td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{'crm.opportunity.amount'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<input type="text" name="currency_amount" size="24" style="border:1px solid rgb(180,180,180);padding:2px;" value="{if $opp}{$opp->getAmountString(false)}{/if}" placeholder="1,500.00" autocomplete="off">
			<select name="currency_id">
				{if is_array($currencies)}
				{foreach from=$currencies item=currency}
				<option value="{$currency->id}" {if $opp->currency_id == $currency->id}selected="selected"{/if}>{$currency->name_plural|default:$currency->name} ({$currency->code})</option>
				{/foreach}
				{/if}
			</select>
		</td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{'common.status'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<label><input type="radio" name="status_id" value="0" onclick="toggleDiv('oppPeekClosedDate','none');" {if !$opp->status_id}checked="checked"{/if}> {'crm.opp.status.open'|devblocks_translate|capitalize}</label>
			<label><input type="radio" name="status_id" value="1" onclick="toggleDiv('oppPeekClosedDate','');" {if 1 == $opp->status_id}checked="checked"{/if}> {'crm.opp.status.closed.won'|devblocks_translate|capitalize}</label>
			<label><input type="radio" name="status_id" value="2" onclick="toggleDiv('oppPeekClosedDate','');" {if 2 == $opp->status_id}checked="checked"{/if}> {'crm.opp.status.closed.lost'|devblocks_translate|capitalize}</label>
		</td>
	</tr>
	
	<tr id="oppPeekClosedDate" {if !$opp->status_id}style="display:none;"{/if}>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{'crm.opportunity.closed_date'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<input type="text" name="closed_date" size="35" class="input_date" value="{if !empty($opp->closed_date)}{$opp->closed_date|devblocks_date}{/if}">
		</td>
	</tr>
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$opp->id}

{include file="devblocks:cerberusweb.core::internal/cards/editors/comment.tpl"}

{if !empty($opp->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this opportunity?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	{if (!$opp->id && $active_worker->hasPriv("contexts.{$peek_context}.create")) || ($opp->id && $active_worker->hasPriv("contexts.{$peek_context}.update"))}
		<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
		{if $active_worker->hasPriv("contexts.{$peek_context}.delete") && !empty($opp)}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
	{else}
		<fieldset class="delete">
			You do not have permission to modify this record.
		</fieldset>
	{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#formOppPeek');
	
	$popup.one('popup_open',function(event,ui) {
		var $frm = $('#formOppPeek');
		
		$popup.dialog('option','title', '{'Opportunity'|devblocks_translate|escape:'javascript' nofilter}');
		
		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Abstract peeks
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		// Abstract choosers
		$popup.find('button.chooser-abstract').cerbChooserTrigger();

		// Validation
		
		$frm.find('input.input_date').cerbDateInputHelper();
		
		$frm.find('button.chooser_worker').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','worker_id', { autocomplete:true });
		});
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
