<div style="margin-left:10px;margin-bottom:10px;">
	<select name="{$namePrefix}[mode]">
		<option value="" {if !$params.mode}selected="selected"{/if}> Terminate</option>
		
		{if $params.mode == 'suspend'}{* [TODO] Deprecated *}
		<option value="suspend" {if 'suspend' == $params.mode}selected="selected"{/if}> Resume at this point when new input is received</option>
		{/if}
	</select>
</div>