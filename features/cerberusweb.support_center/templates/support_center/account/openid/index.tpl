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
	
	<form action="{devblocks_url}c=account&a=openid{/devblocks_url}" method="POST" style="margin-top:5px;" id="myAcctOpenId">
		<input type="hidden" name="a" value="doOpenIdAdd">
		<b>Link a new OpenID to my account:</b><br> 
		<input type="text" name="openid_url" class="input_openid" style="background:url('{devblocks_url}c=resource&p=cerberusweb.core&f=css/input_icons/openid.gif{/devblocks_url}') no-repeat scroll 5px 50% #ffffff;padding-left:25px;" size="45" value="">
		<button type="submit">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/add.png{/devblocks_url}" align="top">&nbsp;</button>
		
		<div>
			<a href="javascript:;" style="float:left;margin-right:5px;border:1px solid rgb(230,230,230);width:100px;height:50px;background:url('{devblocks_url}c=resource&p=cerberusweb.openid&f=images/providers/google.gif{/devblocks_url}') no-repeat scroll center center;" onclick="$('#myAcctOpenId input:text[name=openid_url]').val('https://www.google.com/accounts/o8/id').closest('form').submit();"></a>
			<a href="javascript:;" style="float:left;margin-right:5px;border:1px solid rgb(230,230,230);width:100px;height:50px;background:url('{devblocks_url}c=resource&p=cerberusweb.openid&f=images/providers/yahoo.gif{/devblocks_url}') no-repeat scroll center center;" onclick="$('#myAcctOpenId input:text[name=openid_url]').val('https://me.yahoo.com').closest('form').submit();"></a>
			<a href="javascript:;" style="float:left;margin-right:5px;border:1px solid rgb(230,230,230);width:100px;height:50px;background:url('{devblocks_url}c=resource&p=cerberusweb.openid&f=images/providers/verisign_pip.gif{/devblocks_url}') no-repeat scroll center center;" onclick="$('#myAcctOpenId input:text[name=openid_url]').val('http://pip.verisignlabs.com').closest('form').submit();"></a>
			<a href="javascript:;" style="float:left;margin-right:5px;border:1px solid rgb(230,230,230);width:100px;height:50px;background:url('{devblocks_url}c=resource&p=cerberusweb.openid&f=images/providers/myopenid.gif{/devblocks_url}') no-repeat scroll center center;" onclick="$('#myAcctOpenId input:text[name=openid_url]').val('http://myopenid.com').closest('form').submit();"></a>
		</div>
	</form>
</fieldset>
