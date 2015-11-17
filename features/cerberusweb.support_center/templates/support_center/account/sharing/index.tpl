{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<form action="{devblocks_url}c=account&a=doShareUpdate{/devblocks_url}" method="post" id="frmAcctSharing">
<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">

<fieldset>
	<legend>Shared by me...</legend>
	
	{foreach from=$contact_addresses item=contact_address key=contact_address_id}
		Share tickets and history from <b>{$contact_address->email}</b> with these email addresses:<br>
		<input type="hidden" name="share_email[]" value="{$contact_address->id}">
		
	 	<ul style="list-style:none;padding:0px 0px 0px 15px;margin:0px;">
			{foreach from=$shared_by_me item=share}
				{if $share->share_address_id == $contact_address_id}
				<li style="padding-top:5px;">
					<input type="text" name="share_with_{$contact_address->id}[]" class="input_email" size="45" value="{$share->with_address}">
					<button type="button" class="add" style="display:none;" onclick="$ul=$(this).closest('ul');$li=$(this).closest('li').clone();$li.appendTo($ul).find('input:text').val('').focus();$ul.find('button.del').show().last().hide();$(this).hide();">+</button>
					<button type="button" class="del" onclick="$ul=$(this).closest('ul');$(this).closest('li').remove();$ul.find('button.add:last').show();">-</button>
				</li>
				{/if}
			{/foreach}
			<li style="padding-top:5px;">
				<input type="text" name="share_with_{$contact_address->id}[]" class="input_email" size="45" value="">
				<button type="button" class="add" onclick="$ul=$(this).closest('ul');$li=$(this).closest('li').clone();$li.appendTo($ul).find('input:text').val('').focus();$ul.find('button.del').show().last().hide();$(this).hide();"><span class="glyphicons glyphicons-circle-plus"></span></button>
				<button type="button" class="del" onclick="$ul=$(this).closest('ul');$(this).closest('li').remove();$ul.find('button.add:last').show();" style="display:none;"><span class="glyphicons glyphicons-circle-minus"></span></button>
			</li>
		</ul>
		<br>
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