<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="a" value="">
<table border="0" cellpadding="0" cellspacing="0">
  <tbody>
    <tr>
      <td colspan="2">
      	<h1>{$translate->_('portal.sc.public.contact.thanks_for_contacting')}</h1>
      	
      	{$translate->_('portal.sc.public.contact.message_received')}<br>
      	<br>
      	
      	{if !empty($last_opened)}
      	{assign var=tagged_last_opened value="<b>"|cat:$last_opened|cat:"</b>"}
      	{'portal.public.your_reference_number'|devblocks_translate:$tagged_last_opened}<br>
      	<br>
      	{/if}
      	
		<button type="button" onclick="document.location='{devblocks_url}{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top" border="0"> {$translate->_('common.ok')|upper}</button>
		
      </td>
    </tr>
    
  </tbody>
</table>
</form>
<br>
