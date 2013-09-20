<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formOppPeek" name="formOppPeek" onsubmit="return false;">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="saveOppPanel">
<input type="hidden" name="opp_id" value="{$opp->id}">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($link_context)}
<input type="hidden" name="link_context" value="{$link_context}">
<input type="hidden" name="link_context_id" value="{$link_context_id}">
{/if}
<input type="hidden" name="do_delete" value="0">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{'crm.opportunity.email_address'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<input type="text" name="email" id="emailinput" value="{$address->email}" class="required email" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{'crm.opportunity.name'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<input type="text" name="name" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$opp->name}" class="required" autocomplete="off">
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
				<input type="text" name="amount" size="10" maxlength="12" style="border:1px solid rgb(180,180,180);padding:2px;" value="{if empty($opp->amount)}0{else}{math equation="floor(x)" x=$opp->amount}{/if}" autocomplete="off">
				 . 
				<input type="text" name="amount_cents" size="3" maxlength="2" style="border:1px solid rgb(180,180,180);padding:2px;" value="{if empty($opp->amount)}00{else}{math equation="(x-floor(x))*100" x=$opp->amount}{/if}" autocomplete="off">
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
		
		{* Watchers *}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.watchers'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				{if empty($opp->id)}
					<button type="button" class="chooser_watcher"><span class="cerb-sprite sprite-view"></span></button>
					<ul class="chooser-container bubbles" style="display:block;"></ul>
				{else}
					{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_OPPORTUNITY, array($opp->id), CerberusContexts::CONTEXT_WORKER)}
					{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_OPPORTUNITY context_id=$opp->id full=true}
				{/if}
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

{* Comment *}
{if !empty($last_comment)}
	{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl" readonly=true comment=$last_comment}
{/if}

<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="5" cols="45" style="width:98%;"></textarea>
	<div class="notify" style="display:none;">
		<b>{'common.notify_watchers_and'|devblocks_translate}:</b>
		<button type="button" class="chooser_notify_worker"><span class="cerb-sprite sprite-view"></span></button>
		<ul class="chooser-container bubbles" style="display:block;"></ul>
	</div>
</fieldset>

{if (empty($opp) && $active_worker->hasPriv('crm.opp.actions.create')) || (!empty($opp) && $active_worker->hasPriv('crm.opp.actions.update_all'))}
	<button type="button" onclick="if($('#formOppPeek').validate().form()) { genericAjaxPopupPostCloseReloadView(null,'formOppPeek','{$view_id}',false,'opp_save'); } "><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate}</button>
	{if $active_worker->hasPriv('crm.opp.actions.delete') && !empty($opp)}<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this opportunity?')) { $('#formOppPeek input[name=do_delete]').val('1'); genericAjaxPopupPostCloseReloadView(null,'formOppPeek','{$view_id}',false,'opp_delete'); } "><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
{else}
	<fieldset class="delete">
		You do not have permission to modify this record.
	</fieldset>
{/if}
<br>

{if !empty($opp)}
<div style="float:right;">
	<a href="{devblocks_url}c=profiles&o=opportunity&id={$opp->id}-{$opp->name|devblocks_permalink}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title', '{'Opportunity'|devblocks_translate}');
		
		$(this).find('button.chooser_watcher').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','add_watcher_ids', { autocomplete:true });
		});
		
		ajax.emailAutoComplete('#emailinput');
		
		$("#formOppPeek").validate();
		$(this).find('textarea[name=comment]').keyup(function() {
			if($(this).val().length > 0) {
				$(this).next('DIV.notify').show();
			} else {
				$(this).next('DIV.notify').hide();
			}
		});
		
		var $frm = $('#formOppPeek');
		
		$frm.find(':input:text:first').focus();
		
		$frm.find('input.input_date').cerbDateInputHelper();
		
		$frm.find('button.chooser_worker').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','worker_id', { autocomplete:true });
		});
		
		$frm.find('button.chooser_notify_worker').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
		});
	});
</script>
