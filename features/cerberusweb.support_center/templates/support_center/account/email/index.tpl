{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<fieldset>
	<legend>My Email Addresses</legend>
	
	<ul style="margin:0px;padding:0px 0px 0px 15px;list-style:none;">
		{foreach from=$addresses item=address key=address_id}
		<li style="margin-bottom:5px;">
			<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/mail.png{/devblocks_url}" height="16" width="16" align="top">
			{$address->first_name} {$address->last_name}
			&lt;{$address->email}&gt;
			{if $address->id == $active_contact->email_id}
				(<b>Primary</b>)
			{/if}
			(<a href="{devblocks_url}c=account&m=email&url={$address->email|replace:'.':'_dot_'|escape:'url'|replace:'%40':'_at_'}{/devblocks_url}">edit</a>)
		</li>
		{/foreach}
	</ul>
	
	<form action="{devblocks_url}c=account{/devblocks_url}" method="POST" style="margin-top:5px;">
		<input type="hidden" name="a" value="doEmailAdd">
		<b>Link a new email address to my account:</b><br> 
		<input type="text" name="add_email" class="input_email" style="background:url('{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/mail.png{/devblocks_url}') no-repeat scroll 5px 50% #ffffff;padding-left:25px;" size="45" value="">
		<button type="submit">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/add.png{/devblocks_url}" align="top">&nbsp;</button>
	</form>
</fieldset>
