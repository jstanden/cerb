{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<form action="{devblocks_url}c=account&a=doShareUpdate{/devblocks_url}" method="post" id="frmAcctSharing">

<fieldset>
	<legend>Shared by me...</legend>
	
	{foreach from=$contact_addresses item=contact_address key=contact_address_id}
		Share tickets and history from <b>{$contact_address->email|escape}</b> with these email addresses:<br>
		<input type="hidden" name="share_email[]" value="{$contact_address->id|escape}">
		
	 	<ul style="list-style:none;padding:0px 0px 0px 15px;margin:0px;">
			{foreach from=$shared_by_me item=share}
				{if $share->share_address_id == $contact_address_id}
				<li style="padding-top:5px;">
					<input type="text" name="share_with_{$contact_address->id|escape}[]" class="input_email" style="background:url('{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/mail.png{/devblocks_url}') no-repeat scroll 5px 50% #ffffff;padding-left:25px;" size="45" value="{$share->with_address|escape}">
					<button type="button" class="add" style="display:none;" onclick="$ul=$(this).closest('ul');$li=$(this).closest('li').clone();$li.appendTo($ul).find('input:text').val('').focus();$ul.find('button.del').show().last().hide();$(this).hide();">+</button>
					<button type="button" class="del" onclick="$ul=$(this).closest('ul');$(this).closest('li').remove();$ul.find('button.add:last').show();">-</button>
				</li>
				{/if}
			{/foreach}
			<li style="padding-top:5px;">
				<input type="text" name="share_with_{$contact_address->id|escape}[]" class="input_email" style="background:url('{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/mail.png{/devblocks_url}') no-repeat scroll 5px 50% #ffffff;padding-left:25px;" size="45" value="">
				<button type="button" class="add" onclick="$ul=$(this).closest('ul');$li=$(this).closest('li').clone();$li.appendTo($ul).find('input:text').val('').focus();$ul.find('button.del').show().last().hide();$(this).hide();">+</button>
				<button type="button" class="del" onclick="$ul=$(this).closest('ul');$(this).closest('li').remove();$ul.find('button.add:last').show();" style="display:none;">-</button>
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
			<input type="hidden" name="address_from_id[]" value="{$share->share_address_id|escape}">
			<input type="hidden" name="address_with_id[]" value="{$share->with_address_id|escape}">
			{$options = [1=>'Show',0=>'Hide',2=>'Delete']}
			<select name="share_with_status[]">
				{foreach from=$options item=v key=k} 
				<option value="{$k|escape}" {if $share->is_enabled==$k}selected="selected"{/if}>{$v|escape}</option>
				{/foreach}
			</select>
			<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/mail.png{/devblocks_url}" height="16" width="16" align="top">
			{$share->share_address|escape}
		</li>
		{/foreach}
	</ul>	
</fieldset>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button><br>

</form>