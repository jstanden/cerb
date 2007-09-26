{if !empty($last_error)}
	<div class="error" style="width:550px;">
		{$last_error}
	</div>
{/if}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="a" value="doContactStep3">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td colspan="2">
		<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
      	<h2 style="margin-bottom:0px;">{$situation}</h2>
      	</div>
      
      	{if !empty($situation_params.followups)}
		{foreach from=$situation_params.followups key=question item=long name=situations}
	      	<b>{$question}</b><br>
	      	<input type="hidden" name="followup_q[]" value="{$question}">
	      	{if $long}
	      		<textarea name="followup_a[]" rows="5" cols="60" style="width:98%;"></textarea>
	      	{else}
				<input name="followup_a[]" value="" autocomplete="off" style="width:98%;">
			{/if}
			<br>
			<br>
		{/foreach}
		{/if}
		
		<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top" border="0"> Continue</button>
		<button type="button" onclick="document.location='{devblocks_url}{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/delete.gif{/devblocks_url}" align="top" border="0"> Cancel</button>
		
      </td>
    </tr>
    
  </tbody>
</table>
</form>
<br>
