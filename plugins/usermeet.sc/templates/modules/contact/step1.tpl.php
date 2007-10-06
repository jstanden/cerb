{if !empty($last_error)}
	<div class="error" style="width:550px;">
		{$last_error}
	</div>
{/if}

{if !empty($dispatch)}
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="a" value="doContactStep2">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td colspan="2">
		<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
      	<h2 style="margin-bottom:0px;">Which best describes your situation?</h2>
      	</div>
      	
		{foreach from=$dispatch item=to key=reason}
		{assign var=dispatchKey value=$reason|md5}
			<label><input type="radio" name="nature" value="{$dispatchKey}" onclick="this.form.submit();"> {$reason}</label><br>
		{/foreach}
		<!-- <label><input type="radio" name="nature" value="" {if $displayKey==$last_nature}checked{/if}> None of the above</label><br> -->
		
		<br>
		<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top" border="0"> OK</button>
      </td>
    </tr>
    
  </tbody>
</table>
</form>
{elseif !empty($default_from)}
	<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
   	<h2 style="margin-bottom:0px;">Contact us</h2>
   	</div>
	You can write to us at: <a href="mailto:{$default_from}">{$default_from}</a>
{/if}
<br>

