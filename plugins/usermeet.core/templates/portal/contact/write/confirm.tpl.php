{include file="$path/portal/contact/header.tpl.php"}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="a" value="">
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
      	<h4>{$translate->_('portal.contact.write.thanks')}</h4>
      	
      	{$translate->_('portal.contact.write.message_received')}<br>
      	<br>
      	
      	{if !empty($last_opened)}
      	{assign var=tagged_last_opened value="<b>"|cat:$last_opened|cat:"</b>"}
      	{'portal.public.your_reference_number'|devblocks_translate:$tagged_last_opened}<br>
      	<br>
      	{/if}
      	
		<button type="button" onclick="document.location='{devblocks_url}{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top" border="0"> {$translate->_('common.ok')|upper}</button>
		
      </td>
    </tr>
    
    <tr>
    	<td colspan="2" align="right">
    	<span style="font-size:11px;">
    	
    	{assign var=linked_cerberus_helpdesk value="<a href=\"http://www.cerberusweb.com/\" target=\"_blank\" style=\"color:rgb(80,150,0);font-weight:bold;\">"|cat:"Cerberus Helpdesk"|cat:"</a>&trade;"}
    		{'portal.public.powered_by'|devblocks_translate:$linked_cerberus_helpdesk}
			<br>
		</span>
    	</td>
    </tr>
    
  </tbody>
</table>
</form>
<br>

{include file="$path/portal/contact/footer.tpl.php"}
