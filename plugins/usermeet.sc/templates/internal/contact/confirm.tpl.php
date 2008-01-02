<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="a" value="">
<table border="0" cellpadding="0" cellspacing="0">
  <tbody>
    <tr>
      <td colspan="2">
      	<h1>Thanks for contacting us</h1>
      	
      	Your message has been received! We will respond to you as soon as possible.<br>
      	<br>
      	
      	{if !empty($last_opened)}
      	Your reference number is: #<b>{$last_opened}</b><br>
      	<br>
      	{/if}
      	
		<button type="button" onclick="document.location='{devblocks_url}{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top" border="0"> OK</button>
		
      </td>
    </tr>
    
  </tbody>
</table>
</form>
<br>
