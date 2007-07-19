{include file="$path/usermeet/support/header.tpl.php"}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="a" value="doSendMessage">
<table style="text-align: left; width: 550px;" class="search" border="0" cellpadding="5" cellspacing="5">
  <tbody>
  	<!-- 
    <tr>
      <td colspan="2">
      	<h4 style="color:rgb(30,150,30);font-weight:bold;">How can we help?</h4>
      </td>
    </tr>
     -->
    <tr>
      <td colspan="2">
      	<h4>What best describes your situation?</h4>	
		<select name="nature">
			{foreach from=$dispatch item=to key=reason}
			<option value="{$reason|md5}">{$reason}</option>
			{/foreach}
		</select><br>
		
      	<h4>What e-mail address should we reply to?</h4>	
		<input name="from" class="question" value=""><br>
		<i>(example: bob@yourcompany.com)</i><br>
		
      	<h4>Message:</h4>	
		<textarea name="content" rows="10" cols="60" style="width:98%;"></textarea><br>
		
      	<h4>Please type the letters from the image below:</h4>	
		<input name="captcha" class="question" value="" size="10"><br>
		<img src="{devblocks_url}c=captcha{/devblocks_url}"><br>
		
		<br>
		<b>Logged IP:</b> {$fingerprint.ip}<br>
		<br>
		
		<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.support&f=images/check.gif{/devblocks_url}" align="top" border="0"> Send Message</button>
		<button type="button" onclick="document.location='{devblocks_url}{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=usermeet.support&f=images/delete.gif{/devblocks_url}" align="top" border="0"> Discard</button>
		
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

{include file="$path/usermeet/support/footer.tpl.php"}