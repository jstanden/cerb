{if !empty($last_error)}
	<div class="error" style="width:550px;">
		{$last_error}
	</div>
{/if}

{if !empty($dispatch)}
	<form action="{devblocks_url}c=contact{/devblocks_url}" method="post">
	<input type="hidden" name="a" value="doContactStep2">
	<table border="0" cellpadding="0" cellspacing="0" width="100%">
	  <tbody>
	    <tr>
	      <td colspan="2">
			<div class="header"><h1>{$translate->_('portal.sc.public.contact.how_can_we_help')}</h1></div>
	      	
			{foreach from=$dispatch item=to key=reason}
				{assign var=dispatchKey value=$reason|md5}
				<label><input type="radio" name="nature" value="{$dispatchKey}" onclick="this.form.submit();"> {$reason}</label><br>
			{/foreach}
			<br>
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top" border="0"> {$translate->_('common.ok')|upper}</button>
	      </td>
	    </tr>
	  </tbody>
	</table>
	</form>
{elseif !empty($default_from)}
	<div class="header"><h2 style="margin-bottom:0px;">{$translate->_('portal.sc.public.contact.contact_us')}</h2></div>
   	{assign var=linked_default_from value="<a href=\""|cat:$default_from|cat:"\">"|cat:$default_from|cat:"</a>"}
   	{'portal.sc.public.contact.write_to_us'|devblocks_translate:$linked_default_from}<br>   	
{/if}


