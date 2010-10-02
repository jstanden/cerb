{if !empty($error)}
<div class="ui-widget">
	<div class="ui-state-error ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
		<p>
		<span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
		<strong>{$error}</strong><br>
		</p>
	</div>
</div>
{/if}

<div class="block">
<h2>Sign on using OpenID</h2>

<form action="{devblocks_url}c=login&m=delegate&a=discover{/devblocks_url}" method="post" id="loginOpenID">
<input type="hidden" name="original_path" value="{$original_path|escape}">

<b>Log in with one of these providers:</b><br>
<div>
	<a href="javascript:;" style="float:left;margin-right:5px;border:1px solid rgb(230,230,230);width:100px;height:50px;background:url('{devblocks_url}c=resource&p=cerberusweb.openid&f=images/providers/google.gif{/devblocks_url}') no-repeat scroll center center;" onclick="$('#loginOpenID input:text[name=openid_url]').val('https://www.google.com/accounts/o8/id').closest('form').submit();"></a>
	<a href="javascript:;" style="float:left;margin-right:5px;border:1px solid rgb(230,230,230);width:100px;height:50px;background:url('{devblocks_url}c=resource&p=cerberusweb.openid&f=images/providers/yahoo.gif{/devblocks_url}') no-repeat scroll center center;" onclick="$('#loginOpenID input:text[name=openid_url]').val('https://me.yahoo.com').closest('form').submit();"></a>
	<a href="javascript:;" style="float:left;margin-right:5px;border:1px solid rgb(230,230,230);width:100px;height:50px;background:url('{devblocks_url}c=resource&p=cerberusweb.openid&f=images/providers/verisign_pip.gif{/devblocks_url}') no-repeat scroll center center;" onclick="$('#loginOpenID input:text[name=openid_url]').val('http://pip.verisignlabs.com').closest('form').submit();"></a>
	<a href="javascript:;" style="float:left;margin-right:5px;border:1px solid rgb(230,230,230);width:100px;height:50px;background:url('{devblocks_url}c=resource&p=cerberusweb.openid&f=images/providers/myopenid.gif{/devblocks_url}') no-repeat scroll center center;" onclick="$('#loginOpenID input:text[name=openid_url]').val('http://myopenid.com').closest('form').submit();"></a>
</div>

<div style="clear:both;">
	<b>Or enter your own OpenID:</b><br>
	<input type="text" name="openid_url" size="45" style="background:url('{devblocks_url}c=resource&p=cerberusweb.openid&f=images/openid-inputicon.gif{/devblocks_url}') no-repeat scroll 5px 50% #ffffff;padding-left:25px;">
	<button type="submit">{$translate->_('header.signon')|capitalize}</button>
</div>

</form>
</div>

<script type="text/javascript">
	$(function() {
		$('#loginOpenID input:text :first').focus().select();
	});
</script>