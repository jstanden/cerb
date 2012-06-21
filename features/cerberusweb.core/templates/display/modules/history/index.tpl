<div align="left">
	<form action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
	<input type="hidden" name="c" value="display">
	<input type="hidden" name="a" value="">
	<input type="hidden" name="id" value="{$ticket->id}">
		<b>History for:</b> 
		{$addy_split = explode('@',$contact->email)}
		<label title="{$contact->email}"><input type="radio" name="scope" value="email" onclick="this.form.a.value='doTicketHistoryScope';this.form.submit();" {if empty($scope) || 'email'==$scope}checked="checked"{/if}> {'ticket.requesters'|devblocks_translate|capitalize}</label>
		{if !empty($contact->contact_org_id)}<label><input type="radio" name="scope" value="org" onclick="this.form.a.value='doTicketHistoryScope';this.form.submit();" {if 'org'==$scope}checked="checked"{/if}> {'contact_org.name'|devblocks_translate|capitalize}</label>{/if}
		{if 2==count($addy_split)}<label><input type="radio" name="scope" value="domain" onclick="this.form.a.value='doTicketHistoryScope';this.form.submit();" {if 'domain'==$scope}checked="checked"{/if}> *@{$addy_split.1}</label>{/if}
	</form>
</div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}
