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
      	<h1>What e-mail address should we reply to?</h1>
      	<input type="hidden" name="nature" value="{$sNature}">	
		<input name="from" value="{$last_from}" autocomplete="off" style="width:98%;"><br>

		{if $allow_subjects}
      	<h1>Subject:</h1>
		<input name="subject" value="{$last_subject}" autocomplete="off" style="width:98%;"><br>
		{/if}
		
      	<h1>Message:</h1>	
		<textarea name="content" rows="10" cols="60" style="width:98%;">{$last_content}</textarea><br>
		
		{if $captcha_enabled}
	      	<h1>Please type the text from the image below:</h1>	
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
