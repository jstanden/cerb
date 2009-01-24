<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.crm&f=images/money.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>Opportunity</h1></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formOppPeek" name="formOppPeek" onsubmit="return false;">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="saveOppPanel">
<input type="hidden" name="opp_id" value="{$opp->id}">
<input type="hidden" name="view_id" value="{$view_id}">

<div style="height:250px;overflow:auto;margin:2px;padding:3px;">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.email_address')|capitalize}: </td>
		<td width="100%">
			{if empty($opp->id)}
				<div id="emailautocomplete" style="width:98%;" class="yui-ac">
					<input type="text" name="emails" id="emailinput" value="{$email}" style="border:1px solid rgb(180,180,180);padding:2px;" class="yui-ac-input">
					<div id="emailcontainer" class="yui-ac-container"></div>
					<br>
					<br>
				</div>			
			{elseif !empty($address)}
				{*{$address->first_name} {$address->last_name} &lt;*}<a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$address->email}&view_id=',null,false,'500px',ajax.cbAddressPeek);">{$address->email}</a>{*&gt;*}
			{/if}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.name')|capitalize}: </td>
		<td width="100%">
			<input type="text" name="name" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$opp->name|escape}" autocomplete="off">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.worker_id')|capitalize}: </td>
		<td width="100%">
			<select name="worker_id" style="border:1px solid rgb(180,180,180);padding:2px;">
				<option value="0">&nbsp;</option>
				{foreach from=$workers item=worker key=worker_id name=workers}
					{if $worker_id==$active_worker->id}{math assign=me_worker_id equation="x" x=$smarty.foreach.workers.iteration}{/if}
					<option value="{$worker_id}" {if $opp->worker_id==$worker_id}selected{/if}>{$worker->getName()}</option>
				{/foreach}
			</select>
			{if !empty($me_worker_id)}
				<button type="button" onclick="this.form.worker_id.selectedIndex = {$me_worker_id};">me</button>
			{/if}
		</td>
	</tr>
	{if empty($opp->id)}
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">Comment: </td>
		<td width="100%">
			<textarea name="comment" style="width:98%;height:120px;border:1px solid rgb(180,180,180);padding:2px;"></textarea><br>
		</td>
	</tr>
	{/if}
</table>

<table cellpadding="2" cellspacing="1" border="0">
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
{foreach from=$opp_fields item=f key=f_id}
	<tr>
		<td valign="top" width="1%" nowrap="nowrap">
			<input type="hidden" name="field_ids[]" value="{$f_id}">
			{$f->name}:
		</td>
		<td valign="top" width="99%">
			{if $f->type=='S'}
				<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{$opp_field_values.$f_id|escape}"><br>
			{elseif $f->type=='T'}
				<textarea name="field_{$f_id}" rows="4" cols="50" style="width:98%;">{$opp_field_values.$f_id}</textarea><br>
			{elseif $f->type=='C'}
				<input type="checkbox" name="field_{$f_id}" value="1" {if $opp_field_values.$f_id}checked{/if}><br>
			{elseif $f->type=='D'}
				<select name="field_{$f_id}">{* [TODO] Fix selected *}
					<option value=""></option>
					{foreach from=$f->options item=opt}
					<option value="{$opt|escape}" {if $opt==$opp_field_values.$f_id}selected{/if}>{$opt}</option>
					{/foreach}
				</select><br>
			{elseif $f->type=='E'}
				<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{if !empty($opp_field_values.$f_id)}{$opp_field_values.$f_id|devblocks_date}{/if}"><button type="button" onclick="ajax.getDateChooser('dateCustom{$f_id}',this.form.field_{$f_id});">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
				<div id="dateCustom{$f_id}" style="display:none;position:absolute;z-index:1;"></div>
			{/if}	
		</td>
	</tr>
{/foreach}
</table>

</div>

<button type="button" onclick="genericPanel.hide();genericAjaxPost('formOppPeek', 'view{$view_id}')"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
<br>
</form>
