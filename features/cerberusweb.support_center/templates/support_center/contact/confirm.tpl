<form action="{devblocks_url}c=contact{/devblocks_url}" method="post">
<input type="hidden" name="a" value="">
<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">
<table border="0" cellpadding="0" cellspacing="0">
  <tbody>
	<tr>
	  <td colspan="2">
	  	<fieldset>
	  		<legend>{'portal.sc.public.contact.thanks_for_contacting'|devblocks_translate}</legend>
			
		  	{'portal.sc.public.contact.message_received'|devblocks_translate}<br>
		  	{if !empty($last_opened)}
			  	{assign var=tagged_last_opened value="<b>"|cat:$last_opened|cat:"</b>"}
			  	{'portal.public.your_reference_number'|devblocks_translate:$tagged_last_opened nofilter}<br>
		  	{/if}
	  	</fieldset>
	  	<h1></h1>
	  	
		<button type="button" onclick="document.location='{devblocks_url}{/devblocks_url}';"><span class="glyphicons glyphicons-circle-ok"></span> {'common.ok'|devblocks_translate|upper}</button>
		
	  </td>
	</tr>
	
  </tbody>
</table>
</form>
