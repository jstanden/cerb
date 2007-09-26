{if !empty($last_error)}
	<div class="error" style="width:550px;">
		{$last_error}
	</div>
{/if}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="a" value="doContactSend">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td colspan="2">
      	<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
      	<h2 style="margin-bottom:0px;">What e-mail address should we reply to?</h2>
      	</div>
      	<input type="hidden" name="nature" value="{$sNature}">	
		<input name="from" value="{$last_from}" autocomplete="off" style="width:98%;"><br>
		
		<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
      	<h2 style="margin-bottom:0px;">Message:</h2>	
      	</div>
		<textarea name="content" rows="10" cols="60" style="width:98%;">{$last_content}</textarea><br>
		
		{if $captcha_enabled}
			<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
	      	<h2 style="margin-bottom:0px;">Please type the text from the image below:</h2>	
	      	</div>
			<b>Text:</b> <input name="captcha" class="question" value="" size="10" autocomplete="off"><br>
			<img src="{devblocks_url}c=captcha{/devblocks_url}"><br>
		{/if}
		
		<br>
		<b>Logged IP:</b> {$fingerprint.ip}<br>
		<br>
		
		<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top" border="0"> Send Message</button>
		<button type="button" onclick="document.location='{devblocks_url}{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/delete.gif{/devblocks_url}" align="top" border="0"> Discard</button>
		
      </td>
    </tr>
    
  </tbody>
</table>
</form>
<br>
