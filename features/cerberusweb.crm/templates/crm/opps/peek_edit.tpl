{if $opp && $opp->id}
	{$address = $opp->getPrimaryEmail()}
{/if}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formOppPeek" name="formOppPeek" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="opportunity">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="opp_id" value="{$opp->id}">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($link_context)}
<input type="hidden" name="link_context" value="{$link_context}">
<input type="hidden" name="link_context_id" value="{$link_context_id}">
{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{'common.title'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<input type="text" name="name" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$opp->name}" autocomplete="off" autofocus="autofocus">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{'common.email'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<button type="button" class="chooser-abstract" data-field-name="email_id" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-single="true" data-autocomplete="if-null" data-create="if-null"><span class="glyphicons glyphicons-search"></span></button>
				
				<ul class="bubbles chooser-container">
					{if $address}
						<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=address&context_id={$address->id}{/devblocks_url}?v={$address->updated}"><input type="hidden" name="email_id" value="{$address->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$address->id}">{$address->getNameWithEmail()}</a></li>
					{/if}
				</ul>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{'common.status'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<label><input type="radio" name="status" value="0" onclick="toggleDiv('oppPeekClosedDate','none');" {if empty($opp->id) || 0==$opp->is_closed}checked="checked"{/if}> {'crm.opp.status.open'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="status" value="1" onclick="toggleDiv('oppPeekClosedDate','');" {if $opp->is_closed && $opp->is_won}checked="checked"{/if}> {'crm.opp.status.closed.won'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="status" value="2" onclick="toggleDiv('oppPeekClosedDate','');" {if $opp->is_closed && !$opp->is_won}checked="checked"{/if}> {'crm.opp.status.closed.lost'|devblocks_translate|capitalize}</label>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{'crm.opportunity.amount'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<input type="text" name="amount" size="32" style="border:1px solid rgb(180,180,180);padding:2px;" value="{$opp->amount|number_format:2}" placeholder="1,500.00" autocomplete="off">
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{'common.created'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<input type="text" name="created_date" size=35 class="input_date" value="{if !empty($opp->created_date)}{$opp->created_date|devblocks_date}{else}now{/if}">
			</td>
		</tr>
		<tr id="oppPeekClosedDate" {if !$opp->is_closed}style="display:none;"{/if}>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{'crm.opportunity.closed_date'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<input type="text" name="closed_date" size="35" class="input_date" value="{if !empty($opp->closed_date)}{$opp->closed_date|devblocks_date}{/if}">
			</td>
		</tr>
		
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_OPPORTUNITY context_id=$opp->id}

{* Comments *}
{include file="devblocks:cerberusweb.core::internal/peek/peek_comments_pager.tpl" comments=$comments}

<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="2" cols="45" style="width:98%;" placeholder="{'comment.notify.at_mention'|devblocks_translate}"></textarea>
</fieldset>

{if !empty($opp->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this opportunity?
	</div>
	
	<button type="button" class="delete red"></span> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"></span> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	{if (empty($opp) && $active_worker->hasPriv('crm.opp.actions.create')) || (!empty($opp) && $active_worker->hasPriv('crm.opp.actions.update_all'))}
		<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
		{if $active_worker->hasPriv('crm.opp.actions.delete') && !empty($opp)}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
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
		var $textarea = $(this).find('textarea[name=comment]');
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
		
		// @mentions
		
		var atwho_workers = {CerberusApplication::getAtMentionsWorkerDictionaryJson() nofilter};

		$textarea.atwho({
			at: '@',
			{literal}displayTpl: '<li>${name} <small style="margin-left:10px;">${title}</small> <small style="margin-left:10px;">@${at_mention}</small></li>',{/literal}
			{literal}insertTpl: '@${at_mention}',{/literal}
			data: atwho_workers,
			searchKey: '_index',
			limit: 10
		});
	});
});
</script>
