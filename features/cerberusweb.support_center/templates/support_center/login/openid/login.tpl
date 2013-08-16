<form action="{devblocks_url}c=login&a=discover{/devblocks_url}" method="post" id="loginOpenID">

{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<fieldset>
	<legend>Sign on using OpenID</legend>
	
	<b>Log in with one of these providers:</b><br>
	<div>
		<a href="javascript:;" style="float:left;margin-right:5px;border:1px solid rgb(230,230,230);width:100px;height:50px;background:url('{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/providers/google.gif{/devblocks_url}') no-repeat scroll center center;" onclick="$('#loginOpenID input:text[name=openid_url]').val('https://www.google.com/accounts/o8/id').closest('form').submit();"></a>
		<a href="javascript:;" style="float:left;margin-right:5px;border:1px solid rgb(230,230,230);width:100px;height:50px;background:url('{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/providers/yahoo.gif{/devblocks_url}') no-repeat scroll center center;" onclick="$('#loginOpenID input:text[name=openid_url]').val('https://me.yahoo.com').closest('form').submit();"></a>
		<a href="javascript:;" style="float:left;margin-right:5px;border:1px solid rgb(230,230,230);width:100px;height:50px;background:url('{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/providers/verisign_pip.gif{/devblocks_url}') no-repeat scroll center center;" onclick="$('#loginOpenID input:text[name=openid_url]').val('http://pip.verisignlabs.com').closest('form').submit();"></a>
		<a href="javascript:;" style="float:left;margin-right:5px;border:1px solid rgb(230,230,230);width:100px;height:50px;background:url('{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/providers/myopenid.gif{/devblocks_url}') no-repeat scroll center center;" onclick="$('#loginOpenID input:text[name=openid_url]').val('http://myopenid.com').closest('form').submit();"></a>
	</div>
	
	<div style="clear:both;">
		<b>Or enter your own OpenID:</b><br>
		<input type="text" name="openid_url" size="45" class="input_openid">
		<button type="submit">{$translate->_('header.signon')|capitalize}</button>
	</div>
</fieldset>
</form>

{*<a href="{devblocks_url}c=login&a=register{/devblocks_url}">Don't have an account? Create one for free with your OpenID.</a><br>*}
<a href="{devblocks_url}c=login&a=forgot{/devblocks_url}">Lost your OpenID? Click here to recover your account.</a><br>

{include file="devblocks:cerberusweb.support_center::support_center/login/switcher.tpl"}

<script type="text/javascript">
	$(function() {
		$('#loginOpenID input:text').first().focus().select();
	});
</script>
