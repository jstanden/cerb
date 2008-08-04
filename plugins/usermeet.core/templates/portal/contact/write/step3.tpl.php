{include file="$path/portal/contact/header.tpl.php"}

{if !empty($last_error)}
	<div class="error" style="width:550px;">
		{$last_error}
	</div>
{/if}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="a" value="doSendMessage">
<table style="text-align: left; width: 550px;" class="search" border="0" cellpadding="5" cellspacing="5">
  <tbody>
    <tr>
      <td colspan="2">
      	<h4>What e-mail address should we reply to?</h4>
      	<input type="hidden" name="nature" value="{$sNature}">	
		<input name="from" value="{$last_from}" autocomplete="off" style="width:98%;"><br>
		
      	<h4>Message:</h4>	
		<textarea name="content" rows="10" cols="60" style="width:98%;">{$last_content|escape}</textarea><br>
		
		{if $captcha_enabled}
	      	<h4>Please type the characters from the image below:</h4>	
			<input name="captcha" class="question" value="" size="10" autocomplete="off"><br>
			<img src="{devblocks_url}c=captcha{/devblocks_url}"><br>
		{/if}
		
		<br>
		<b>Logged IP:</b> {$fingerprint.ip}<br>
		<br>
		
		<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top" border="0"> Send Message</button>
		<button type="button" onclick="document.location='{devblocks_url}{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/delete.gif{/devblocks_url}" align="top" border="0"> Discard</button>
		
      </td>
    </tr>
    
    <tr>
    	<td colspan="2" align="right">
    	<span style="font-size:11px;">
			Powered by <a href="http://www.cerberusweb.com/" target="_blank" style="color:rgb(80,150,0);font-weight:bold;">Cerberus Helpdesk</a>&trade;<br>
		</span>
    	</td>
    </tr>
    
  </tbody>
</table>
</form>
<br>

{include file="$path/portal/contact/footer.tpl.php"}