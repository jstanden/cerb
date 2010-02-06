<form action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="">
<input type="hidden" name="id" value="{$opp->id}">
	
	<table cellpadding="0" cellspacing="0" border="0" width="100%">
		<tr>
			<td width="1%" nowrap="nowrap">
				<button id="btnQuickCompose" type="button" onclick="genericAjaxPanel('c=tickets&a=showComposePeek&view_id={$view->id}&to={$address->email|escape:'url'}',null,false,'600');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/mail_write.gif{/devblocks_url}" align="top"> {'common.quick_compose'|devblocks_translate}</button>
			</td>
			<td width="98%"></td>
			<td width="1%" nowrap="nowrap" align="right">
				<b>History for:</b> 
				{$addy_split = explode('@',$address->email)}
				<label title="{$address->email|escape}"><input type="radio" name="scope" value="email" onclick="this.form.a.value='doOppHistoryScope';this.form.submit();" {if empty($scope) || 'email'==$scope}checked="checked"{/if}> {'common.email'|devblocks_translate|capitalize}</label>
				{if !empty($address->contact_org_id)}<label><input type="radio" name="scope" value="org" onclick="this.form.a.value='doOppHistoryScope';this.form.submit();" {if 'org'==$scope}checked="checked"{/if}> {'contact_org.name'|devblocks_translate|capitalize}</label>{/if}
				{if 2==count($addy_split)}<label><input type="radio" name="scope" value="domain" onclick="this.form.a.value='doOppHistoryScope';this.form.submit();" {if 'domain'==$scope}checked="checked"{/if}> *@{$addy_split.1}</label>{/if}
			</td>
		</tr>
	</table>
</form>

<div id="viewopp_tickets">{$view->render()}</div>
