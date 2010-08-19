<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formOppPeek" name="formOppPeek" onsubmit="return false;">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="saveOppPanel">
<input type="hidden" name="opp_id" value="{$opp->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.email_address')|capitalize}: </td>
		<td width="100%">
			<input type="text" name="email" id="emailinput" value="{$address->email|escape}" class="required email" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.name')|capitalize}: </td>
		<td width="100%">
			<input type="text" name="name" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$opp->name|escape}" class="required" autocomplete="off">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('common.status')|capitalize}: </td>
		<td width="100%">
			<label><input type="radio" name="status" value="0" onclick="toggleDiv('oppPeekClosedDate','none');" {if empty($opp->id) || 0==$opp->is_closed}checked="checked"{/if}> {'crm.opp.status.open'|devblocks_translate|capitalize}</label>
			<label><input type="radio" name="status" value="1" onclick="toggleDiv('oppPeekClosedDate','');" {if $opp->is_closed && $opp->is_won}checked="checked"{/if}> {'crm.opp.status.closed.won'|devblocks_translate|capitalize}</label>
			<label><input type="radio" name="status" value="2" onclick="toggleDiv('oppPeekClosedDate','');" {if $opp->is_closed && !$opp->is_won}checked="checked"{/if}> {'crm.opp.status.closed.lost'|devblocks_translate|capitalize}</label>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.amount')|capitalize}: </td>
		<td width="100%">
			<input type="text" name="amount" size="10" maxlength="12" style="border:1px solid rgb(180,180,180);padding:2px;" value="{if empty($opp->amount)}0{else}{math equation="floor(x)" x=$opp->amount}{/if}" autocomplete="off">
			 . 
			<input type="text" name="amount_cents" size="3" maxlength="2" style="border:1px solid rgb(180,180,180);padding:2px;" value="{if empty($opp->amount)}00{else}{math equation="(x-floor(x))*100" x=$opp->amount}{/if}" autocomplete="off">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.owners'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<button type="button" class="chooser_worker"><span class="cerb-sprite sprite-add"></span></button>
			{if !empty($context_workers)}
			<ul class="chooser-container bubbles">
				{foreach from=$context_workers item=context_worker}
				<li>{$context_worker->getName()|escape}<input type="hidden" name="worker_id[]" value="{$context_worker->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
				{/foreach}
			</ul>
			{/if}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.created_date')|capitalize}: </td>
		<td width="100%">
			<input type="text" name="created_date" size=35 value="{if !empty($opp->created_date)}{$opp->created_date|devblocks_date}{else}now{/if}"><button type="button" onclick="devblocksAjaxDateChooser(this.form.created_date,'#dateOppCreated');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
			<div id="dateOppCreated"></div>
		</td>
	</tr>
	<tr id="oppPeekClosedDate" {if !$opp->is_closed}style="display:none;"{/if}>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.closed_date')|capitalize}: </td>
		<td width="100%">
			<input type="text" name="closed_date" size="35" value="{if !empty($opp->closed_date)}{$opp->closed_date|devblocks_date}{/if}"><button type="button" onclick="devblocksAjaxDateChooser(this.form.closed_date,'#dateOppClosed');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
			<div id="dateOppClosed"></div>
		</td>
	</tr>
	{if empty($opp->id)}
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{'common.comment'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<textarea name="comment" style="width:98%;height:120px;border:1px solid rgb(180,180,180);padding:2px;"></textarea><br>
		</td>
	</tr>
	{/if}
</table>

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=false}

{* Comment *}
{if !empty($last_comment)}
	<br>
	{include file="file:$core_tpl/internal/comments/comment.tpl" readonly=true comment=$last_comment}
{/if}
<br>

{if $active_worker->hasPriv('crm.opp.actions.create')}
	<button type="button" onclick="if($('#formOppPeek').validate().form()) { genericAjaxPost('formOppPeek', 'view{$view_id}', '', function() { genericAjaxPopupClose('peek', 'opp_save'); } ); } "><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
	{if !empty($opp)}<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this opportunity?')) { this.form.do_delete.value='1';genericAjaxPopupClose('peek');genericAjaxPost('formOppPeek', 'view{$view_id}'); } "><span class="cerb-sprite sprite-delete2"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
{else}
	<div class="error">You do not have permission to modify this record.</div>
{/if}
<br>

{if !empty($opp)}
<div style="float:right;">
	<a href="{devblocks_url}c=crm&a=opps&id={$opp->id}{/devblocks_url}">view full record</a>
</div>
{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title', '{'Opportunity'|devblocks_translate|escape:'quotes'}');
		ajax.emailAutoComplete('#emailinput');
		$("#formOppPeek").validate();
		$('#formOppPeek :input:text:first').focus();
	} );
	$('#formOppPeek button.chooser_worker').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','worker_id');
	});
</script>
