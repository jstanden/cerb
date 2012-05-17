<form action="{devblocks_url}c=contact{/devblocks_url}" method="post">
<input type="hidden" name="a" value="">
<table border="0" cellpadding="0" cellspacing="0">
  <tbody>
    <tr>
      <td colspan="2">
      	<fieldset>
      		<legend>{$translate->_('portal.sc.public.contact.thanks_for_contacting')}</legend>
			
	      	{$translate->_('portal.sc.public.contact.message_received')}<br>
	      	{if !empty($last_opened)}
		      	{assign var=tagged_last_opened value="<b>"|cat:$last_opened|cat:"</b>"}
		      	{'portal.public.your_reference_number'|devblocks_translate:$tagged_last_opened nofilter}<br>
	      	{/if}
      	</fieldset>
      	<h1></h1>
      	
      	
		<button type="button" onclick="document.location='{devblocks_url}{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top" border="0"> {$translate->_('common.ok')|upper}</button>
		
      </td>
    </tr>
    
  </tbody>
</table>
</form>
