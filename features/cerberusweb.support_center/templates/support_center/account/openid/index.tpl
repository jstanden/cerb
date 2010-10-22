{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<fieldset>
	<legend>My OpenID Identities</legend>
	
	<ul style="margin:0px;padding:0px 0px 0px 15px;list-style:none;">
		{foreach from=$openids item=openid_url}
		<li style="margin-bottom:5px;">
			<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=css/input_icons/openid.gif{/devblocks_url}" align="top">
			{$openid_url|escape}
			(<a href="{devblocks_url}c=account&m=openid&id={$openid_url|md5|escape}{/devblocks_url}">edit</a>)
		</li>
		{/foreach}
	</ul>
	
	<form action="{devblocks_url}c=account&a=openid{/devblocks_url}" method="POST" style="margin-top:5px;">
		<input type="hidden" name="a" value="doOpenIdAdd">
		<b>Link a new OpenID to my account:</b><br> 
		<input type="text" name="openid_url" class="input_openid" style="background:url('{devblocks_url}c=resource&p=cerberusweb.core&f=css/input_icons/openid.gif{/devblocks_url}') no-repeat scroll 5px 50% #ffffff;padding-left:25px;" size="45" value="">
		<button type="submit">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/add.png{/devblocks_url}" align="top">&nbsp;</button>
	</form>
</fieldset>
