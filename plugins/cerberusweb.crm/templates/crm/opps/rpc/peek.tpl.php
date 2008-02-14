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

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">Name: </td>
		<td width="100%">
			<input type="text" name="name" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$opp->name|escape}" autocomplete="off">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">E-mail: </td>
		<td width="100%">
			{if empty($opp->id)}
				<div id="emailautocomplete" style="width:98%;" class="yui-ac">
					<input type="text" name="emails" id="emailinput" value="" style="border:1px solid rgb(180,180,180);padding:2px;" class="yui-ac-input">
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
		<td width="0%" nowrap="nowrap" align="right" valign="top">Campaign: </td>
		<td width="100%">
			<select name="campaign_id" style="border:1px solid rgb(180,180,180);padding:2px;">
				{foreach from=$campaigns item=campaign key=campaign_id}
					<option value="{$campaign_id}" {if $campaign_id==$opp->campaign_id}selected{/if}>{$campaign->name}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">Lead Source: </td>
		<td width="100%">
			<input type="text" name="source" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$opp->source|escape}" autocomplete="off">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">Worker: </td>
		<td width="100%">
			<select name="worker_id" style="border:1px solid rgb(180,180,180);padding:2px;">
				<option value="0">&nbsp;</option>
				{foreach from=$workers item=worker key=worker_id}
					<option value="{$worker_id}" {if $opp->worker_id==$worker_id}selected{/if}>{$worker->getName()}</option>
				{/foreach}
			</select>
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

<button type="button" onclick="genericPanel.hide();genericAjaxPost('formOppPeek', 'view{$view_id}')"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.close')|capitalize}</button>
<br>
</form>
