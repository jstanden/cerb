{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<form action="{devblocks_url}c=account&a=doShareUpdate{/devblocks_url}" method="post" id="frmAcctSharing">
<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">

<fieldset>
	<legend>Shared by me...</legend>
	
	{foreach from=$contact_addresses item=contact_address key=contact_address_id}
		Share tickets and history from <b>{$contact_address->email}</b> with these email addresses: (one per line)<br>
		<input type="hidden" name="share_email[]" value="{$contact_address->id}">
		<textarea name="share_with_{$contact_address->id}" style="width:100%;height:6.5em;">{foreach from=$shared_by_me item=share}{if $share->share_address_id == $contact_address_id}{$share->with_address}
{/if}{/foreach}</textarea>
	{/foreach}
</fieldset>

<fieldset>
	<legend>Shared with me...</legend>
	
 	<ul style="list-style:none;padding:0px 0px 0px 15px;margin:0px;">
		{foreach from=$shared_with_me item=share}
		<li style="padding-top:5px;">
			<input type="hidden" name="address_from_id[]" value="{$share->share_address_id}">
			<input type="hidden" name="address_with_id[]" value="{$share->with_address_id}">
			{$options = [1=>'Show',0=>'Hide',2=>'Delete']}
			<select name="share_with_status[]">
				{foreach from=$options item=v key=k} 
				<option value="{$k}" {if $share->is_enabled==$k}selected="selected"{/if}>{$v}</option>
				{/foreach}
			</select>
			<b>{$share->share_address}</b>
		</li>
		{/foreach}
	</ul>	
</fieldset>

<button type="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button><br>

</form>