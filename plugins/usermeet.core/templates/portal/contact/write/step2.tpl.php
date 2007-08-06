{include file="$path/portal/contact/header.tpl.php"}

{if !empty($last_error)}
	<div class="error" style="width:550px;">
		{$last_error}
	</div>
{/if}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="a" value="doStep3">
<table style="text-align: left; width: 550px;" class="search" border="0" cellpadding="5" cellspacing="5">
  <tbody>
    <tr>
      <td colspan="2">
      	<h3>{$situation}</h3>
      
      	{if !empty($situation_params.followups)}
		{foreach from=$situation_params.followups key=question item=long name=situations}
	      	<h4>{$question}</h4>
	      	<input type="hidden" name="followup_q[]" value="{$question}">
	      	{if $long}
	      		<textarea name="followup_a[]" rows="5" cols="60" style="width:98%;"></textarea>
	      	{else}
				<input name="followup_a[]" value="" autocomplete="off" style="width:98%;">
			{/if}
			<br>
		{/foreach}
		{/if}
		
		<br>
		<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top" border="0"> Continue</button>
		<button type="button" onclick="document.location='{devblocks_url}{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/delete.gif{/devblocks_url}" align="top" border="0"> Cancel</button>
		
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

{include file="$path/portal/contact/footer.tpl.php"}