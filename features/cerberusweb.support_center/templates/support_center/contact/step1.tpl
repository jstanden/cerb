{if !empty($last_error)}
<div class="error" style="width:550px;">
	{$last_error}
</div>
{/if}

{if !empty($dispatch)}
<form action="{devblocks_url}c=contact{/devblocks_url}" method="post">
	<input type="hidden" name="a" value="doContactStep2">
	<fieldset>
		<legend>{'portal.sc.public.contact.how_can_we_help'|devblocks_translate}</legend>
		
		{foreach from=$dispatch item=params key=reason}
			{assign var=dispatchKey value=$reason|md5}
			<label><input type="radio" name="nature" value="{$dispatchKey}" onclick="this.form.submit();"> {$reason}</label><br>
		{/foreach}
		<br>
		<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top" border="0"> {'common.ok'|devblocks_translate|upper}</button>
	</fieldset>
</form>
{elseif !empty($default_from)}
	<fieldset>
		<legend>{'portal.sc.public.contact.contact_us'|devblocks_translate}</legend>
		
	   	{assign var=linked_default_from value="<a href=\""|cat:$default_from|cat:"\">"|cat:$default_from|cat:"</a>"}
	   	{'portal.sc.public.contact.write_to_us'|devblocks_translate:$linked_default_from nofilter}<br>   	
	</fieldset>
{/if}


